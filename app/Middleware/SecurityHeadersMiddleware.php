<?php

declare(strict_types=1);

namespace App\Middleware;

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

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net 'unsafe-inline'",
            "style-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        header('Content-Security-Policy: ' . $csp);
    }
}
