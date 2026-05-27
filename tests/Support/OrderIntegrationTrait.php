<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use PDO;

trait OrderIntegrationTrait
{
    /** @var list<int> */
    private array $orderIdsToCleanup = [];

    protected function findCustomerId(PDO $pdo, int $companyId): int
    {
        $customers = (new CustomerRepository($pdo))->paginate(1, 1);
        $customerId = $customers['items'][0]->id ?? 0;
        $this->assertGreaterThan(0, $customerId, 'Seed deve ter cliente.');

        return $customerId;
    }

    protected function findProductWithStock(PDO $pdo, int $companyId, int $minStock = 1): int
    {
        $stmt = $pdo->prepare(
            "SELECT id FROM products
             WHERE company_id = :company_id AND type = 'product' AND stock >= :min_stock
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['company_id' => $companyId, 'min_stock' => $minStock]);
        $productId = (int) $stmt->fetchColumn();
        $this->assertGreaterThan(0, $productId, 'Seed deve ter produto com estoque suficiente.');

        return $productId;
    }

    /**
     * @param list<array{product_id: int, quantity: int}> $items
     */
    protected function placeTestOrder(int $customerId, array $items): int
    {
        $orderId = (new OrderService())->placeOrder($customerId, $items);
        $this->orderIdsToCleanup[] = $orderId;

        return $orderId;
    }

    protected function deleteOrderCascade(PDO $pdo, int $orderId): void
    {
        $pdo->prepare(
            'DELETE FROM payments WHERE accounts_receivable_id IN (
                SELECT id FROM accounts_receivable WHERE order_id = :order_id
            )'
        )->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM cash_flow WHERE reference_type = \'payment\' AND reference_id IN (
            SELECT id FROM payments WHERE accounts_receivable_id IN (
                SELECT id FROM accounts_receivable WHERE order_id = :order_id
            )
        )')->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM installments WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM accounts_receivable WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare(
            "DELETE FROM stock_movements WHERE reference_type = 'order' AND reference_id = :order_id"
        )->execute(['order_id' => $orderId]);

        $pdo->prepare(
            "DELETE FROM stock_movements WHERE reference_type = 'manual' AND product_id IN (
                SELECT product_id FROM order_items WHERE order_id = :order_id
            )"
        )->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM orders WHERE id = :order_id')
            ->execute(['order_id' => $orderId]);
    }

    protected function cleanupTestOrders(PDO $pdo): void
    {
        foreach (array_reverse($this->orderIdsToCleanup) as $orderId)
        {
            $this->deleteOrderCascade($pdo, $orderId);
        }
        $this->orderIdsToCleanup = [];
    }
}
