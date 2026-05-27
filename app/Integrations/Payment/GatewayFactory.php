<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

use App\Integrations\Payment\Gateways\MercadoPagoPixGateway;
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
            'mercadopago' => self::mercadoPago(),
            default => throw new InvalidArgumentException('Gateway PIX não configurado: ' . $name),
        };
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        $gateways = ['mock'];
        if (PixConfig::isMercadoPagoConfigured())
        {
            $gateways[] = 'mercadopago';
        }

        return $gateways;
    }

    private static function mercadoPago(): PaymentGatewayInterface
    {
        if (!PixConfig::isMercadoPagoConfigured())
        {
            throw new InvalidArgumentException(
                'Mercado Pago não configurado. Defina PIX_MERCADOPAGO_ACCESS_TOKEN no .env.'
            );
        }

        return new MercadoPagoPixGateway();
    }
}
