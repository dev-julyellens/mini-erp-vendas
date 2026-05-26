<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Helpers\Auth;
use App\Helpers\PathHelper;
use App\Services\PermissionService;

final class PermissionMiddleware
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'GET /login',
        'POST /login',
        'GET /forgot-password',
        'POST /forgot-password',
        'GET /reset-password',
        'POST /reset-password',
        'POST /logout',
        'GET /',
        'GET /notifications',
        'POST /notifications/open',
        'POST /notifications/read',
        'POST /notifications/read-all',
        'GET /lgpd/consent',
        'POST /lgpd/consent',
        'GET /select-company',
        'POST /select-company',
        'GET /onboarding',
        'POST /onboarding/company',
        'GET /onboarding/plan',
        'POST /onboarding/plan',
        'GET /subscription',
        'POST /subscription/pay',
        'POST /subscription/change-plan',
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if (in_array($routeKey, self::SKIP_ROUTES, true))
        {
            return;
        }

        if (!Auth::check())
        {
            return;
        }

        $user = Auth::user();
        if ($user === null)
        {
            return;
        }

        $service = new PermissionService();
        if ($service->authorizeRoute($user->role, $method, $path))
        {
            return;
        }

        self::denyForbidden($path);
    }

    private static function denyForbidden(string $path): void
    {
        if (ApiRequest::isApiPath($path))
        {
            ApiResponse::error('Sem permissão para esta ação.', 403);
        }

        http_response_code(403);
        echo '403 - Você não tem permissão para acessar este recurso.';
        exit;
    }
}
