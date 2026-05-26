<?php

declare(strict_types=1);

namespace App\Helpers;

final class MailConfig
{
    public static function driver(): string
    {
        $config = self::config();

        return (string) ($config['driver'] ?? 'log');
    }

    public static function fromAddress(): string
    {
        $config = self::config();

        return (string) ($config['from_address'] ?? 'noreply@localhost');
    }

    public static function fromName(): string
    {
        $config = self::config();

        return (string) ($config['from_name'] ?? 'Mini ERP de Vendas');
    }

    public static function smtpHost(): string
    {
        $config = self::config();

        return (string) ($config['smtp_host'] ?? '');
    }

    public static function smtpPort(): int
    {
        $config = self::config();

        return (int) ($config['smtp_port'] ?? 587);
    }

    public static function smtpUser(): string
    {
        $config = self::config();

        return (string) ($config['smtp_user'] ?? '');
    }

    public static function smtpPassword(): string
    {
        $config = self::config();

        return (string) ($config['smtp_password'] ?? '');
    }

    public static function smtpEncryption(): string
    {
        $config = self::config();

        return (string) ($config['smtp_encryption'] ?? 'tls');
    }

    public static function smtpAuth(): bool
    {
        $config = self::config();

        return (bool) ($config['smtp_auth'] ?? true);
    }

    public static function smtpDebug(): bool
    {
        $config = self::config();

        return (bool) ($config['smtp_debug'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    private static function config(): array
    {
        static $mail = null;
        if ($mail === null)
        {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            $mail = is_array($app['mail'] ?? null) ? $app['mail'] : [];
        }

        return $mail;
    }
}
