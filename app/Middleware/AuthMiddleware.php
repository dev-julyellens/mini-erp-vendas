<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\Csrf;
use App\Helpers\PathHelper;
use App\Helpers\Redirect;

final class AuthMiddleware
{
    /** @var list<string> */
    private const PUBLIC_ROUTES = [
        'GET /login',
        'POST /login',
        'GET /forgot-password',
        'POST /forgot-password',
        'GET /reset-password',
        'POST /reset-password',
        'GET /select-company',
        'POST /select-company',
        'POST /api/auth/login',
    ];

    /** @var list<string> */
    private const COMPANY_OPTIONAL_ROUTES = [
        'GET /select-company',
        'POST /select-company',
        'POST /logout',
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if ($method === 'POST' && !self::isApiPath($path) && !Csrf::validateRequest())
        {
            self::denyInvalidCsrf($path);
        }

        if (in_array($routeKey, self::PUBLIC_ROUTES, true))
        {
            if (Auth::check() && in_array($routeKey, ['GET /login', 'GET /forgot-password'], true))
            {
                if (!CompanyContext::hasSelected() || Auth::peekPendingUserId() !== null)
                {
                    Redirect::to('/select-company');
                }
                Redirect::to('/');
            }
            return;
        }

        if (!Auth::check())
        {
            self::denyUnauthenticated($path);
        }

        if (
            !in_array($routeKey, self::COMPANY_OPTIONAL_ROUTES, true)
            && Auth::peekPendingUserId() !== null
        )
        {
            Redirect::to('/select-company');
        }

        if (
            !in_array($routeKey, self::COMPANY_OPTIONAL_ROUTES, true)
            && !CompanyContext::hasSelected()
        )
        {
            Redirect::to('/select-company');
        }

        $user = Auth::user();
        if ($user !== null && self::isApiPath($path))
        {
            ApiMiddleware::attachUserToLog($user->id);
        }
    }

    private static function isApiPath(string $path): bool
    {
        return ApiRequest::isApiPath($path);
    }

    private static function denyUnauthenticated(string $path): void
    {
        if (self::isApiPath($path))
        {
            ApiResponse::error('Não autenticado.', 401);
        }

        $_SESSION['intended_url'] = PathHelper::requestPath()
            . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
                ? '?' . $_SERVER['QUERY_STRING']
                : '');

        Redirect::to('/login');
    }

    private static function denyInvalidCsrf(string $path): void
    {
        if (self::isApiPath($path))
        {
            ApiResponse::error('Token CSRF inválido.', 403);
        }

        http_response_code(403);
        echo '403 - Token de segurança inválido. Atualize a página e tente novamente.';
        exit;
    }
}
