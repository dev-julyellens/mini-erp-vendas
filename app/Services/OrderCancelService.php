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
            throw new ValidationException(['auth' => 'User must be authenticated to cancel a sale.']);
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
                throw new ValidationException(['order_id' => 'Order not found.']);
            }

            $this->assertCancelable($order);

            $items = $itemRepo->findByOrderId($orderId);
            if ($items === [])
            {
                throw new ValidationException(['order_id' => 'Order has no items.']);
            }

            /** @var list<array{product_id: int, quantity: int, stock_before: int}> $stockAudit */
            $stockAudit = [];

            foreach ($items as $line)
            {
                $productId = $line->product_id;
                $quantity = $line->quantity;

                $product = $productRepo->findById($productId, false);
                $stockBefore = $product !== null ? $product->stock : 0;

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

            $orderRepo->markCanceled($orderId, $userId);

            $pdo->commit();

            $this->recordAudit($order, $orderId, $userId, $items, $stockAudit);
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
            throw new ValidationException(['status' => 'This sale is already canceled.']);
        }

        if ($order->status === 'refunded')
        {
            throw new ValidationException(['status' => 'Refunded sales cannot be canceled.']);
        }

        if (!in_array($order->status, ['pending', 'paid'], true))
        {
            throw new ValidationException(['status' => 'This sale cannot be canceled.']);
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
