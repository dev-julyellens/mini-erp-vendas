<?php

declare(strict_types=1);

namespace App\Middleware;

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
        if (strpos($path, '/api/') === 0)
        {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['success' => false, 'message' => 'Sem permissão para esta ação.'],
                JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        http_response_code(403);
        echo '403 - Você não tem permissão para acessar este recurso.';
        exit;
    }
}
