<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use PDO;
use PDOException;

final class OrderService
{
    /** Venda concluída na criação (baixa de estoque + conta a receber). */
    public const STATUS_PAID = 'paid';

    /** Reservado para rascunho/orçamento futuro; não usado em placeOrder(). */
    public const STATUS_PENDING = 'pending';

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     */
    public function placeOrder(int $customerId, array $lines, int $installmentCount = 1): int
    {
        $normalized = $this->normalizeLines($lines);
        $installmentCount = InstallmentService::normalizeInstallmentCount($installmentCount);

        if ($installmentCount >= InstallmentService::MIN_COUNT)
        {
            (new InstallmentService())->assertValidCount($installmentCount);
        }

        $pdo = Database::getConnection();
        $customerRepo = new CustomerRepository($pdo);
        if ($customerRepo->findById($customerId) === null)
        {
            throw new ValidationException(['customer_id' => 'Cliente não encontrado.']);
        }

        usort(
            $normalized,
            static function (array $a, array $b): int
            {
                return $a['product_id'] <=> $b['product_id'];
            }
        );

        $pdo->beginTransaction();

        try
        {
            $productRepo = new ProductRepository($pdo);
            $orderRepo = new OrderRepository($pdo);
            $itemRepo = new OrderItemRepository($pdo);
            $stockService = new StockService(
                new StockMovementRepository($pdo),
                $productRepo,
                $pdo
            );

            $prepared = [];
            /** @var list<array{product_id: int, stock_before: int, quantity: int}> $stockAudit */
            $stockAudit = [];
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

                if ($quantity <= 0)
                {
                    throw new ValidationException(['items' => 'A quantidade deve ser positiva.']);
                }

                if (!$product->isService() && $product->stock < $quantity)
                {
                    throw new ValidationException([
                        'items' => sprintf(
                            'Estoque insuficiente para "%s". Disponível: %d, solicitado: %d.',
                            $product->name,
                            $product->stock,
                            $quantity
                        ),
                    ]);
                }

                /*
                 * Historical pricing (audit / sales integrity):
                 * The product catalog price is copied into `order_items.unit_price` at the moment
                 * the sale is finalized. Future catalog price edits must not rewrite past sales;
                 * reports and order details always reflect what was charged when the order was placed.
                 */
                $unitPriceAtSaleMoment = $product->price;
                $subtotal = Money::mul($unitPriceAtSaleMoment, $quantity);
                $total = Money::add($total, $subtotal);

                $prepared[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceAtSaleMoment,
                    'subtotal' => $subtotal,
                ];
                if (!$product->isService())
                {
                    $stockAudit[] = [
                        'product_id' => $productId,
                        'stock_before' => $product->stock,
                        'quantity' => $quantity,
                    ];
                }
            }

            $orderId = $orderRepo->insert($customerId, $total, self::STATUS_PAID);

            foreach ($prepared as $row)
            {
                $itemRepo->insert(
                    $orderId,
                    (int) $row['product_id'],
                    (int) $row['quantity'],
                    (string) $row['unit_price'],
                    (string) $row['subtotal']
                );
            }

            foreach ($stockAudit as $stockLine)
            {
                $stockService->apply(
                    'saida',
                    (int) $stockLine['product_id'],
                    (int) $stockLine['quantity'],
                    'order',
                    $orderId,
                    'Saída por venda #' . $orderId,
                    null,
                    false,
                    false
                );
            }

            $installmentService = new InstallmentService();
            $dueDate = (new \DateTimeImmutable('today'))
                ->modify('+' . AccountsReceivableService::DEFAULT_DUE_DAYS . ' days')
                ->format('Y-m-d');
            $installmentRows = [];

            if ($installmentCount >= InstallmentService::MIN_COUNT)
            {
                $installmentService->assertValidCount($installmentCount);
                $installmentRows = $installmentService->generateForOrder($orderId, $total, $installmentCount, $pdo);
                $dueDate = $installmentService->firstDueDate($installmentCount);
            }

            $arService = new AccountsReceivableService();
            $arId = $arService->createFromApprovedOrder($orderId, $customerId, $total, $pdo, $dueDate);

            $pdo->commit();

            Audit::record('conta_receber', 'financeiro', $arId, null, [
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'amount' => $total,
                'due_date' => $dueDate,
                'status' => 'pending',
                'installment_count' => $installmentCount >= InstallmentService::MIN_COUNT ? $installmentCount : 1,
            ]);

            if ($installmentRows !== [])
            {
                Audit::record('parcelamento', 'financeiro', $orderId, null, [
                    'order_id' => $orderId,
                    'installment_count' => $installmentCount,
                    'installments' => $installmentRows,
                ]);
            }

            Audit::record('venda', 'vendas', $orderId, null, [
                'customer_id' => $customerId,
                'total_amount' => $total,
                'status' => 'paid',
                'items' => $prepared,
            ]);

            foreach ($stockAudit as $stockLine)
            {
                $productId = (int) $stockLine['product_id'];
                $qty = (int) $stockLine['quantity'];
                $before = (int) $stockLine['stock_before'];
                Audit::record(
                    'saida_estoque',
                    'estoque',
                    $productId,
                    ['product_id' => $productId, 'stock' => $before],
                    ['product_id' => $productId, 'stock' => $before - $qty, 'order_id' => $orderId, 'quantity' => $qty]
                );
            }

            return $orderId;
        }
        catch (ValidationException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
        catch (PDOException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<int, array{product_id?: mixed, quantity?: mixed}> $lines
     * @return list<array{product_id: int, quantity: int}>
     */
    private function normalizeLines(array $lines): array
    {
        if ($lines === [])
        {
            throw new ValidationException(['items' => 'A venda deve conter pelo menos um item.']);
        }

        /** @var array<int, int> $merged */
        $merged = [];

        foreach ($lines as $line)
        {
            $productId = (int) ($line['product_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0)
            {
                throw new ValidationException(['items' => 'Cada linha precisa de um produto válido e quantidade positiva.']);
            }

            if (!isset($merged[$productId]))
            {
                $merged[$productId] = 0;
            }
            $merged[$productId] += $quantity;
        }

        $out = [];
        foreach ($merged as $pid => $qty)
        {
            $out[] = ['product_id' => $pid, 'quantity' => $qty];
        }

        return $out;
    }
}
