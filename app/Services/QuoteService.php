<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\QuoteItemRepository;
use App\Repositories\QuoteRepository;
use PDO;

final class QuoteService
{
    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     */
    public function create(
        int $customerId,
        array $lines,
        ?string $validUntil = null,
        ?string $notes = null,
        ?int $userId = null
    ): int
    {
        $userId = $userId ?? Auth::id();
        $normalized = $this->normalizeLines($lines);
        $customerRepo = new CustomerRepository();
        if ($customerRepo->findById($customerId) === null)
        {
            throw new ValidationException(['customer_id' => 'Cliente não encontrado.']);
        }

        if ($validUntil !== null && $validUntil !== '')
        {
            try
            {
                new \DateTimeImmutable($validUntil);
            }
            catch (\Throwable $e)
            {
                throw new ValidationException(['valid_until' => 'Data de validade inválida.']);
            }
        }
        else
        {
            $validUntil = null;
        }

        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '')
        {
            $notes = null;
        }

        $quoteId = Database::transaction(function (PDO $pdo) use (
            $customerId,
            $normalized,
            $validUntil,
            $notes,
            $userId
        ): int
        {
            $productRepo = new ProductRepository($pdo);
            $quoteRepo = new QuoteRepository($pdo);
            $itemRepo = new QuoteItemRepository($pdo);

            $prepared = [];
            $total = '0.00';

            foreach ($normalized as $line)
            {
                $productId = (int) $line['product_id'];
                $quantity = (int) $line['quantity'];
                $product = $productRepo->findById($productId, true);
                if ($product === null)
                {
                    throw new ValidationException(['items' => 'Produto não encontrado: ' . $productId]);
                }

                $unitPrice = $product->price;
                $subtotal = Money::mul($unitPrice, $quantity);
                $total = Money::add($total, $subtotal);
                $prepared[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            $quoteId = $quoteRepo->insert($customerId, $total, 'draft', $validUntil, $notes, $userId);

            foreach ($prepared as $row)
            {
                $itemRepo->insert(
                    $quoteId,
                    (int) $row['product_id'],
                    (int) $row['quantity'],
                    (string) $row['unit_price'],
                    (string) $row['subtotal']
                );
            }

            return $quoteId;
        });

        $quote = (new QuoteRepository())->findById($quoteId);
        Audit::record('orcamento', 'vendas', $quoteId, null, [
            'customer_id' => $customerId,
            'total' => $quote !== null ? $quote->total_amount : '0',
            'status' => 'draft',
        ], $userId);

        Logger::info('Orçamento criado.', ['quote_id' => $quoteId, 'customer_id' => $customerId]);

        return $quoteId;
    }

    public function convertToOrder(int $quoteId, int $installmentCount = 1, ?int $userId = null): int
    {
        $userId = $userId ?? Auth::id();

        $result = Database::transaction(function (PDO $pdo) use ($quoteId, $installmentCount): array
        {
            $quoteRepo = new QuoteRepository($pdo);
            $quote = $quoteRepo->findByIdForUpdate($quoteId);
            if ($quote === null)
            {
                throw new ValidationException(['quote_id' => 'Orçamento não encontrado.']);
            }
            if (!$quote->canConvert())
            {
                throw new ValidationException(['status' => 'Este orçamento não pode ser convertido em venda.']);
            }

            $items = (new QuoteItemRepository($pdo))->findByQuoteId($quoteId);
            if ($items === [])
            {
                throw new ValidationException(['items' => 'O orçamento não possui itens.']);
            }

            $lines = [];
            foreach ($items as $item)
            {
                $lines[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            }

            $orderId = (new OrderService())->placeOrder($quote->customer_id, $lines, $installmentCount);
            if (!$quoteRepo->markConverted($quoteId, $orderId))
            {
                throw new ValidationException([
                    'status' => 'O orçamento já foi alterado. A venda #' . $orderId . ' foi criada — verifique manualmente.',
                ]);
            }

            return ['order_id' => $orderId, 'previous_status' => $quote->status];
        });

        Audit::record(
            'orcamento_conversao',
            'vendas',
            $quoteId,
            ['status' => $result['previous_status']],
            ['status' => 'converted', 'order_id' => $result['order_id']],
            $userId
        );

        Logger::info('Orçamento convertido em venda.', [
            'quote_id' => $quoteId,
            'order_id' => $result['order_id'],
        ]);

        return $result['order_id'];
    }

    public function cancel(int $quoteId, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        $previousStatus = Database::transaction(function (PDO $pdo) use ($quoteId): string
        {
            $quoteRepo = new QuoteRepository($pdo);
            $quote = $quoteRepo->findByIdForUpdate($quoteId);
            if ($quote === null)
            {
                throw new ValidationException(['quote_id' => 'Orçamento não encontrado.']);
            }
            if (!$quote->canCancel())
            {
                throw new ValidationException(['status' => 'Este orçamento não pode ser cancelado.']);
            }
            $previousStatus = $quote->status;
            $quoteRepo->markStatus($quoteId, 'canceled');

            return $previousStatus;
        });

        Audit::record(
            'orcamento',
            'vendas',
            $quoteId,
            ['status' => $previousStatus],
            ['status' => 'canceled'],
            $userId
        );
    }

    public function markSent(int $quoteId, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();

        $previousStatus = Database::transaction(function (PDO $pdo) use ($quoteId): string
        {
            $quoteRepo = new QuoteRepository($pdo);
            $quote = $quoteRepo->findByIdForUpdate($quoteId);
            if ($quote === null)
            {
                throw new ValidationException(['quote_id' => 'Orçamento não encontrado.']);
            }
            if ($quote->status !== 'draft')
            {
                throw new ValidationException(['status' => 'Somente rascunhos podem ser marcados como enviados.']);
            }
            if (!$quoteRepo->markSentFromDraft($quoteId))
            {
                throw new ValidationException(['status' => 'Não foi possível atualizar o status do orçamento.']);
            }

            return $quote->status;
        });

        Audit::record('orcamento', 'vendas', $quoteId, ['status' => $previousStatus], ['status' => 'sent'], $userId);
    }

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     * @return list<array{product_id: int, quantity: int}>
     */
    private function normalizeLines(array $lines): array
    {
        if ($lines === [])
        {
            throw new ValidationException(['items' => 'O orçamento deve conter pelo menos um item.']);
        }

        /** @var array<int, int> $merged */
        $merged = [];
        foreach ($lines as $line)
        {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($productId <= 0 || $quantity <= 0)
            {
                throw new ValidationException(['items' => 'Cada linha precisa de produto e quantidade válidos.']);
            }
            $merged[$productId] = ($merged[$productId] ?? 0) + $quantity;
        }

        $out = [];
        foreach ($merged as $pid => $qty)
        {
            $out[] = ['product_id' => $pid, 'quantity' => $qty];
        }

        return $out;
    }
}
