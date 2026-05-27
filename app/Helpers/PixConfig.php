<?php

declare(strict_types=1);

namespace App\Helpers;

final class PixConfig
{
    public static function defaultGateway(): string
    {
        $config = self::config();

        return (string) ($config['default_gateway'] ?? 'mock');
    }

    public static function chargeTtlSeconds(): int
    {
        $config = self::config();

        return max(300, (int) ($config['charge_ttl_seconds'] ?? 3600));
    }

    public static function webhookSecret(): string
    {
        $config = self::config();

        return (string) ($config['webhook_secret'] ?? '');
    }

    public static function merchantName(): string
    {
        $config = self::config();

        return (string) ($config['merchant_name'] ?? 'Mini ERP');
    }

    public static function merchantCity(): string
    {
        $config = self::config();

        return (string) ($config['merchant_city'] ?? 'Brasil');
    }

    public static function isEnabled(): bool
    {
        $config = self::config();

        return (bool) ($config['enabled'] ?? true);
    }

    public static function mercadoPagoAccessToken(): string
    {
        $config = self::config();

        return trim((string) ($config['mercadopago_access_token'] ?? ''));
    }

    public static function mercadoPagoWebhookSecret(): string
    {
        $config = self::config();

        return trim((string) ($config['mercadopago_webhook_secret'] ?? ''));
    }

    public static function mercadoPagoPayerEmail(): string
    {
        $config = self::config();
        $email = trim((string) ($config['mercadopago_payer_email'] ?? ''));

        return $email !== '' ? $email : 'noreply@mercadopago.com';
    }

    public static function isMercadoPagoConfigured(): bool
    {
        return self::mercadoPagoAccessToken() !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private static function config(): array
    {
        static $cached = null;
        if ($cached !== null)
        {
            return $cached;
        }

        $path = dirname(__DIR__, 2) . '/config/app.php';
        $app = is_file($path) ? require $path : [];

        $cached = is_array($app['pix'] ?? null) ? $app['pix'] : [];

        return $cached;
    }
}
