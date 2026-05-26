<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use App\Helpers\Auth;
use App\Models\Order;
use App\Core\Database;
use App\Helpers\Audit;
use App\Core\ValidationException;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\StockMovementRepository;

final class OrderCancelService
{
    public function cancel(int $orderId, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ($userId === null)
        {
            throw new ValidationException(['auth' => 'É necessário estar autenticado para cancelar a venda.']);
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try
        {
            $orderRepo = new OrderRepository($pdo);
            $itemRepo = new OrderItemRepository($pdo);
            $productRepo = new ProductRepository($pdo);
            $stockService = new StockService(
                new StockMovementRepository($pdo),
                $productRepo,
                $pdo
            );

            $order = $orderRepo->findByIdForUpdate($orderId);
            if ($order === null)
            {
                throw new ValidationException(['order_id' => 'Pedido não encontrado.']);
            }

            $this->assertCancelable($order);

            $items = $itemRepo->findByOrderId($orderId);
            if ($items === [])
            {
                throw new ValidationException(['order_id' => 'O pedido não possui itens.']);
            }

            /** @var list<array{product_id: int, quantity: int, stock_before: int}> $stockAudit */
            $stockAudit = [];

            foreach ($items as $line)
            {
                $productId = $line->product_id;
                $quantity = $line->quantity;

                $product = $productRepo->findById($productId, false);
                $stockBefore = $product !== null ? $product->stock : 0;

                if ($product !== null && !$product->isService())
                {
                    $stockService->registerReturn(
                        $productId,
                        $quantity,
                        $orderId,
                        'Devolução por cancelamento da venda #' . $orderId,
                        false,
                        $pdo
                    );

                    $stockAudit[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                    ];
                }
            }

            $orderRepo->markCanceled($orderId, $userId);

            $installmentService = new InstallmentService();
            $installmentsCanceled = $installmentService->cancelByOrderId($orderId, $pdo);

            $arService = new AccountsReceivableService();
            $arCanceled = $arService->cancelByOrderId($orderId, $pdo);

            $pdo->commit();

            $this->recordAudit($order, $orderId, $userId, $items, $stockAudit);

            try
            {
                (new \App\Services\NotificationService(null, $pdo))->notifyOrderCanceled($order);
            }
            catch (\Throwable $ignored)
            {
            }

            if ($installmentsCanceled !== null)
            {
                Audit::record(
                    'cancelamento_parcelas',
                    'financeiro',
                    $orderId,
                    ['order_id' => $orderId],
                    ['status' => 'canceled', 'order_id' => $orderId, 'count' => $installmentsCanceled['count']],
                    $userId
                );
            }

            if ($arCanceled !== null)
            {
                Audit::record(
                    'cancelamento_conta_receber',
                    'financeiro',
                    $arCanceled['ar_id'],
                    ['status' => $arCanceled['old_status'], 'order_id' => $orderId],
                    ['status' => 'canceled', 'order_id' => $orderId],
                    $userId
                );
            }
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

    private function assertCancelable(Order $order): void
    {
        if ($order->status === 'canceled')
        {
            throw new ValidationException(['status' => 'Esta venda já está cancelada.']);
        }

        if ($order->status === 'refunded')
        {
            throw new ValidationException(['status' => 'Vendas estornadas não podem ser canceladas.']);
        }

        if (!in_array($order->status, ['pending', 'paid'], true))
        {
            throw new ValidationException(['status' => 'Esta venda não pode ser cancelada.']);
        }
    }

    /**
     * @param list<\App\Models\OrderItem> $items
     * @param list<array{product_id: int, quantity: int, stock_before: int}> $stockAudit
     */
    private function recordAudit(
        Order $order,
        int $orderId,
        int $userId,
        array $items,
        array $stockAudit
    ): void
    {
        $itemPayload = [];
        foreach ($items as $line)
        {
            $itemPayload[] = [
                'product_id' => $line->product_id,
                'product_name' => $line->product_name,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'subtotal' => $line->subtotal,
            ];
        }

        Audit::record(
            'cancelamento_venda',
            'vendas',
            $orderId,
            [
                'status' => $order->status,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
            ],
            [
                'status' => 'canceled',
                'canceled_by' => $userId,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
                'items' => $itemPayload,
            ],
            $userId
        );

        foreach ($stockAudit as $stockLine)
        {
            $productId = (int) $stockLine['product_id'];
            $qty = (int) $stockLine['quantity'];
            $before = (int) $stockLine['stock_before'];

            Audit::record(
                'entrada_estoque',
                'estoque',
                $productId,
                ['product_id' => $productId, 'stock' => $before],
                [
                    'product_id' => $productId,
                    'stock' => $before + $qty,
                    'order_id' => $orderId,
                    'quantity' => $qty,
                    'reason' => 'cancelamento_venda',
                ],
                $userId
            );
        }
    }
}
