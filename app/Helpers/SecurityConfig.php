<?php

declare(strict_types=1);

namespace App\Helpers;

final class SecurityConfig
{
    private const DEFAULT_IDLE_TIMEOUT = 1800;
    private const DEFAULT_ABSOLUTE_TIMEOUT = 28800;
    private const DEFAULT_MIN_PASSWORD_LENGTH = 8;

    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    public static function sessionIdleTimeout(): int
    {
        $value = self::security()['session_idle_timeout'] ?? self::DEFAULT_IDLE_TIMEOUT;

        return max(300, (int) $value);
    }

    public static function sessionAbsoluteTimeout(): int
    {
        $value = self::security()['session_absolute_timeout'] ?? self::DEFAULT_ABSOLUTE_TIMEOUT;

        return max(600, (int) $value);
    }

    public static function minPasswordLength(): int
    {
        $value = self::security()['password_min_length'] ?? self::DEFAULT_MIN_PASSWORD_LENGTH;

        return max(8, (int) $value);
    }

    public static function requirePasswordComplexity(): bool
    {
        $value = self::security()['password_require_complexity'] ?? true;

        return (bool) $value;
    }

    public static function lgpdPolicyVersion(): string
    {
        $value = self::security()['lgpd_policy_version'] ?? '2026-05-01';

        return trim((string) $value) !== '' ? trim((string) $value) : '2026-05-01';
    }

    public static function maskSensitiveDataInLists(): bool
    {
        $value = self::security()['mask_sensitive_data'] ?? true;

        return (bool) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function security(): array
    {
        if (self::$config === null)
        {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            $security = $app['security'] ?? [];
            self::$config = is_array($security) ? $security : [];
        }

        return self::$config;
    }
}
