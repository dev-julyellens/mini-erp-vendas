<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Helpers\Money;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderCancelService;
use App\Services\OrderService;
use App\Services\PaymentService;
use PHPUnit\Framework\TestCase;
use Tests\Support\OrderIntegrationTrait;
use Tests\Support\RequiresPostgresTrait;

final class OrderCancelServiceIntegrationTest extends TestCase
{
    use RequiresPostgresTrait;
    use OrderIntegrationTrait;

    private const COMPANY_ID = 1;

    private const USER_ID = 1;

    protected function setUp(): void
    {
        $this->requirePostgres();
        CompanyContext::setJwtCompanyId(self::COMPANY_ID);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestOrders(Database::getConnection());
        $this->resetTestContext();
        parent::tearDown();
    }

    public function testCancelPendingOrderRestoresStock(): void
    {
        $pdo = Database::getConnection();
        $productRepo = new ProductRepository($pdo);
        $customerId = $this->findCustomerId($pdo, self::COMPANY_ID);
        $productId = $this->findProductWithStock($pdo, self::COMPANY_ID, 2);

        $productBefore = $productRepo->findById($productId);
        $this->assertNotNull($productBefore);
        $stockBefore = $productBefore->stock;

        $orderId = $this->placeTestOrder($customerId, [
            ['product_id' => $productId, 'quantity' => 1],
        ]);

        $afterSale = $productRepo->findById($productId);
        $this->assertNotNull($afterSale);
        $this->assertSame($stockBefore - 1, $afterSale->stock);

        (new OrderCancelService())->cancel($orderId, self::USER_ID);

        $order = (new OrderRepository($pdo))->findById($orderId);
        $this->assertNotNull($order);
        $this->assertSame('canceled', $order->status);

        $afterCancel = $productRepo->findById($productId);
        $this->assertNotNull($afterCancel);
        $this->assertSame($stockBefore, $afterCancel->stock);

        $ar = (new AccountsReceivableRepository($pdo))->findByOrderId($orderId);
        $this->assertNotNull($ar);
        $this->assertSame('canceled', $ar->status);
    }

    public function testCancelRejectedWhenReceivableHasPayment(): void
    {
        $pdo = Database::getConnection();
        $customerId = $this->findCustomerId($pdo, self::COMPANY_ID);
        $productId = $this->findProductWithStock($pdo, self::COMPANY_ID);

        $orderId = $this->placeTestOrder($customerId, [
            ['product_id' => $productId, 'quantity' => 1],
        ]);

        $ar = (new AccountsReceivableRepository($pdo))->findByOrderId($orderId);
        $this->assertNotNull($ar);

        $partial = Money::compare($ar->amount, '2.00') >= 0
            ? '1.00'
            : '0.01';

        (new PaymentService())->receive(
            $ar->id,
            $partial,
            'pix',
            null,
            null,
            self::USER_ID
        );

        $this->expectException(ValidationException::class);
        (new OrderCancelService())->cancel($orderId, self::USER_ID);
    }

    public function testCancelAlreadyCanceledOrderThrows(): void
    {
        $pdo = Database::getConnection();
        $customerId = $this->findCustomerId($pdo, self::COMPANY_ID);
        $productId = $this->findProductWithStock($pdo, self::COMPANY_ID);

        $orderId = $this->placeTestOrder($customerId, [
            ['product_id' => $productId, 'quantity' => 1],
        ]);

        $cancelService = new OrderCancelService();
        $cancelService->cancel($orderId, self::USER_ID);

        $this->expectException(ValidationException::class);
        $cancelService->cancel($orderId, self::USER_ID);
    }
}
