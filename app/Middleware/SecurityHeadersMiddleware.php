<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\AppConfig;

final class SecurityHeadersMiddleware
{
    public static function handle(): void
    {
        if (headers_sent())
        {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-XSS-Protection: 0');

        if (AppConfig::isProduction() && self::requestIsHttps())
        {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $scriptSrc = [
            "'self'",
            'https://cdn.jsdelivr.net',
            'https://cdn.datatables.net',
        ];

        $cspParts = [
            "default-src 'self'",
            'script-src ' . implode(' ', $scriptSrc),
            "style-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' https://cdn.jsdelivr.net data:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if (AppConfig::isProduction() && self::requestIsHttps())
        {
            $cspParts[] = 'upgrade-insecure-requests';
        }

        header('Content-Security-Policy: ' . implode('; ', $cspParts));
    }

    private static function requestIsHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        {
            return true;
        }

        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return $proto === 'https';
    }
}
