<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

use App\Integrations\Payment\Gateways\MockPixGateway;
use App\Helpers\PixConfig;
use InvalidArgumentException;

final class GatewayFactory
{
    public static function make(?string $gateway = null): PaymentGatewayInterface
    {
        $name = $gateway ?? PixConfig::defaultGateway();

        return match ($name)
        {
            'mock' => new MockPixGateway(),
            default => throw new InvalidArgumentException('Gateway PIX não configurado: ' . $name),
        };
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        return ['mock'];
    }
}
