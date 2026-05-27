<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations;

use App\Integrations\Payment\Gateways\MercadoPagoPixGateway;
use PHPUnit\Framework\TestCase;

final class MercadoPagoPixGatewayTest extends TestCase
{
    public function testMapsApprovedToPaid(): void
    {
        self::assertSame('paid', MercadoPagoPixGateway::mapPaymentStatus('approved'));
    }

    public function testMapsCancelledStatuses(): void
    {
        self::assertSame('canceled', MercadoPagoPixGateway::mapPaymentStatus('cancelled'));
        self::assertSame('expired', MercadoPagoPixGateway::mapPaymentStatus('rejected'));
    }

    public function testMapsUnknownToPending(): void
    {
        self::assertSame('pending', MercadoPagoPixGateway::mapPaymentStatus('in_process'));
    }
}
