<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Helpers\Money;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\Services\PaymentService;
use PHPUnit\Framework\TestCase;
use Tests\Support\OrderIntegrationTrait;
use Tests\Support\RequiresPostgresTrait;

final class PaymentServiceIntegrationTest extends TestCase
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

    public function testPartialThenFullPaymentUpdatesReceivableAndOrderStatus(): void
    {
        $pdo = Database::getConnection();
        $customerId = $this->findCustomerId($pdo, self::COMPANY_ID);
        $productId = $this->findProductWithStock($pdo, self::COMPANY_ID);

        $orderId = $this->placeTestOrder($customerId, [
            ['product_id' => $productId, 'quantity' => 1],
        ]);

        $order = (new OrderRepository($pdo))->findById($orderId);
        $this->assertNotNull($order);
        $this->assertSame(OrderService::STATUS_PENDING, $order->status);

        $ar = (new AccountsReceivableRepository($pdo))->findByOrderId($orderId);
        $this->assertNotNull($ar);
        $this->assertSame('pending', $ar->status);

        $total = $ar->amount;
        $partial = Money::compare($total, '2.00') >= 0
            ? Money::sub($total, '1.00')
            : '0.01';

        $paymentService = new PaymentService();
        $paymentService->receive($ar->id, $partial, 'pix', null, null, self::USER_ID);

        $arPartial = (new AccountsReceivableRepository($pdo))->findById($ar->id);
        $this->assertNotNull($arPartial);
        $this->assertSame('partial', $arPartial->status);

        $orderPending = (new OrderRepository($pdo))->findById($orderId);
        $this->assertNotNull($orderPending);
        $this->assertSame(OrderService::STATUS_PENDING, $orderPending->status);

        $remaining = Money::sub($total, $partial);
        $paymentService->receive($ar->id, $remaining, 'dinheiro', null, null, self::USER_ID);

        $arPaid = (new AccountsReceivableRepository($pdo))->findById($ar->id);
        $this->assertNotNull($arPaid);
        $this->assertSame('paid', $arPaid->status);

        $orderPaid = (new OrderRepository($pdo))->findById($orderId);
        $this->assertNotNull($orderPaid);
        $this->assertSame(OrderService::STATUS_PAID, $orderPaid->status);
    }

    public function testReceiveRejectsAmountAboveRemaining(): void
    {
        $pdo = Database::getConnection();
        $customerId = $this->findCustomerId($pdo, self::COMPANY_ID);
        $productId = $this->findProductWithStock($pdo, self::COMPANY_ID);

        $orderId = $this->placeTestOrder($customerId, [
            ['product_id' => $productId, 'quantity' => 1],
        ]);

        $ar = (new AccountsReceivableRepository($pdo))->findByOrderId($orderId);
        $this->assertNotNull($ar);

        $this->expectException(ValidationException::class);
        (new PaymentService())->receive(
            $ar->id,
            Money::add($ar->amount, '1.00'),
            'pix',
            null,
            null,
            self::USER_ID
        );
    }
}
