<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Helpers\Money;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderService;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequiresPostgresTrait;

final class OrderServiceIntegrationTest extends TestCase
{
    use RequiresPostgresTrait;

    private const COMPANY_ID = 1;

    /** @var list<int> */
    private array $orderIdsToCleanup = [];

    private ?int $productIdToRestore = null;

    private ?string $originalProductPrice = null;

    protected function setUp(): void
    {
        $this->requirePostgres();
        CompanyContext::setJwtCompanyId(self::COMPANY_ID);
    }

    protected function tearDown(): void
    {
        $pdo = Database::getConnection();
        foreach (array_reverse($this->orderIdsToCleanup) as $orderId)
        {
            $this->deleteOrderCascade($pdo, $orderId);
        }
        $this->orderIdsToCleanup = [];

        if ($this->productIdToRestore !== null && $this->originalProductPrice !== null)
        {
            $pdo->prepare(
                'UPDATE products SET price = :price WHERE id = :id AND company_id = :company_id'
            )->execute([
                'price' => $this->originalProductPrice,
                'id' => $this->productIdToRestore,
                'company_id' => self::COMPANY_ID,
            ]);
        }

        $this->resetTestContext();
        parent::tearDown();
    }

    public function testPlaceOrderPreservesHistoricalUnitPrice(): void
    {
        $pdo = Database::getConnection();
        $productRepo = new ProductRepository($pdo);

        $stmt = $pdo->prepare(
            "SELECT id FROM products
             WHERE company_id = :company_id AND type = 'product' AND stock > 0
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute(['company_id' => self::COMPANY_ID]);
        $productId = (int) $stmt->fetchColumn();
        $this->assertGreaterThan(0, $productId, 'Seed deve ter produto com estoque.');

        $product = $productRepo->findById($productId);
        $this->assertNotNull($product);

        $customerRepo = new CustomerRepository($pdo);
        $customers = $customerRepo->paginate(1, 1);
        $customerId = $customers['items'][0]->id ?? 0;
        $this->assertGreaterThan(0, $customerId);

        $priceAtSale = $product->price;
        $this->productIdToRestore = $product->id;
        $this->originalProductPrice = $priceAtSale;
        $stockBefore = $product->stock;

        $service = new OrderService();
        $orderId = $service->placeOrder($customerId, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $this->orderIdsToCleanup[] = $orderId;

        $items = (new OrderItemRepository($pdo))->findByOrderId($orderId);
        $this->assertCount(1, $items);
        $this->assertSame($priceAtSale, $items[0]->unit_price);

        $newPrice = Money::add($priceAtSale, '100.00');
        $stmt = $pdo->prepare(
            'UPDATE products SET price = :price WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'price' => $newPrice,
            'id' => $product->id,
            'company_id' => self::COMPANY_ID,
        ]);

        $itemsAfterPriceChange = (new OrderItemRepository($pdo))->findByOrderId($orderId);
        $this->assertSame($priceAtSale, $itemsAfterPriceChange[0]->unit_price);

        $productAfter = $productRepo->findById($product->id);
        $this->assertNotNull($productAfter);
        $this->assertSame($newPrice, $productAfter->price);
        if ($stockBefore > 0)
        {
            $this->assertSame($stockBefore - 1, $productAfter->stock);
        }

        $order = (new OrderRepository($pdo))->findById($orderId);
        $this->assertNotNull($order);
        $this->assertSame(OrderService::STATUS_PAID, $order->status);
    }

    public function testPlaceOrderRejectsUnknownCustomer(): void
    {
        $service = new OrderService();

        $this->expectException(ValidationException::class);
        $service->placeOrder(999999999, [
            ['product_id' => 1, 'quantity' => 1],
        ]);
    }

    private function deleteOrderCascade(\PDO $pdo, int $orderId): void
    {
        $pdo->prepare(
            'DELETE FROM payments WHERE accounts_receivable_id IN (
                SELECT id FROM accounts_receivable WHERE order_id = :order_id
            )'
        )->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM installments WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM accounts_receivable WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare(
            "DELETE FROM stock_movements WHERE reference_type = 'order' AND reference_id = :order_id"
        )->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $pdo->prepare('DELETE FROM orders WHERE id = :order_id')
            ->execute(['order_id' => $orderId]);
    }
}
