<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\PathHelper;
use App\Services\TenantContextService;

/**
 * Garante company_role na sessão quando há empresa ativa.
 */
final class TenantContextMiddleware
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'GET /login',
        'POST /login',
        'GET /forgot-password',
        'POST /forgot-password',
        'GET /reset-password',
        'POST /reset-password',
        'GET /select-company',
        'POST /select-company',
        'POST /api/auth/login',
        'POST /webhooks/pix/mock',
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if (in_array($routeKey, self::SKIP_ROUTES, true) || !Auth::check())
        {
            return;
        }

        if (!CompanyContext::hasSelected())
        {
            return;
        }

        (new TenantContextService())->refreshCompanyRoleInSession();
    }
}
