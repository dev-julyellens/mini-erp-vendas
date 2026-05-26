<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Env;
use App\Core\SecurityBootstrap;

final class AppConfig
{
    public static function isDebug(): bool
    {
        $flag = Env::get('APP_DEBUG');
        if ($flag === null || $flag === '')
        {
            return false;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isProduction(): bool
    {
        $env = Env::get('APP_ENV');
        if ($env === null || $env === '')
        {
            return false;
        }

        $normalized = strtolower(trim($env));

        return in_array($normalized, ['production', 'prod'], true);
    }

    /**
     * Webhook PIX mock só em desenvolvimento ou com flag explícita (staging).
     */
    public static function allowsMockPixWebhook(): bool
    {
        if (self::isDebug())
        {
            return true;
        }

        $flag = Env::get('PIX_MOCK_WEBHOOK_ENABLED');
        if ($flag === null || $flag === '')
        {
            return false;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    public static function jwtSecret(): string
    {
        $secret = Env::get('JWT_SECRET');
        if ($secret !== null && $secret !== '')
        {
            return $secret;
        }

        if (self::isDebug())
        {
            return SecurityBootstrap::jwtDevFallback();
        }

        return '';
    }
}
