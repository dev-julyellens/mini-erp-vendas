<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use PDO;
use PDOException;

final class OrderService
{
    /** Venda quitada (conta a receber totalmente recebida). */
    public const STATUS_PAID = 'paid';

    /** Venda registrada; aguardando recebimento financeiro. */
    public const STATUS_PENDING = 'pending';

    /**
     * Atualiza o pedido para `paid` quando a conta a receber vinculada estiver quitada.
     */
    public function syncPaymentStatusFromReceivable(int $orderId, ?PDO $pdo = null): void
    {
        $accountRepo = new AccountsReceivableRepository($pdo);
        $ar = $accountRepo->findByOrderId($orderId);
        if ($ar === null || $ar->status !== 'paid')
        {
            return;
        }

        $orderRepo = new OrderRepository($pdo);
        $order = $orderRepo->findById($orderId);
        if ($order !== null && $order->status === self::STATUS_PENDING)
        {
            $orderRepo->markPaid($orderId);
        }
    }

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

        $customerRepo = new CustomerRepository();
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

        try
        {
            /** @var array{
             *     order_id: int,
             *     prepared: list<array<string, mixed>>,
             *     stockAudit: list<array{product_id: int, stock_before: int, quantity: int}>,
             *     total: string,
             *     installmentRows: list<array<string, mixed>>,
             *     installmentCount: int,
             *     dueDate: string,
             *     arId: int
             * } $tx
             */
            $tx = Database::transaction(function (PDO $pdo) use ($customerId, $normalized, $installmentCount): array
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

                $orderId = $orderRepo->insert($customerId, $total, self::STATUS_PENDING);

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

                return [
                    'order_id' => $orderId,
                    'prepared' => $prepared,
                    'stockAudit' => $stockAudit,
                    'total' => $total,
                    'installmentRows' => $installmentRows,
                    'installmentCount' => $installmentCount,
                    'dueDate' => $dueDate,
                    'arId' => $arId,
                ];
            });

            $orderId = $tx['order_id'];
            $prepared = $tx['prepared'];
            $stockAudit = $tx['stockAudit'];
            $total = $tx['total'];
            $installmentRows = $tx['installmentRows'];
            $installmentCount = $tx['installmentCount'];
            $dueDate = $tx['dueDate'];
            $arId = $tx['arId'];

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
                'status' => self::STATUS_PENDING,
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

            Logger::info('Venda registrada.', [
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'total' => $total,
            ]);

            return $orderId;
        }
        catch (\Throwable $e)
        {
            if (!$e instanceof ValidationException)
            {
                Logger::exception($e, 'Falha ao registrar venda.', ['customer_id' => $customerId]);
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
