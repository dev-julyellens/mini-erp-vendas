<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\PathHelper;

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
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if (in_array($routeKey, self::PUBLIC_ROUTES, true))
        {
            if (Auth::check() && in_array($routeKey, ['GET /login', 'GET /forgot-password'], true))
            {
                self::redirect('/');
            }
            return;
        }

        if (!Auth::check())
        {
            self::denyUnauthenticated($path);
        }

        if ($method === 'POST' && !self::isApiPath($path) && !Csrf::validateRequest())
        {
            self::denyInvalidCsrf($path);
        }
    }

    private static function isApiPath(string $path): bool
    {
        return strpos($path, '/api/') === 0;
    }

    private static function denyUnauthenticated(string $path): void
    {
        if (self::isApiPath($path))
        {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['intended_url'] = PathHelper::requestPath()
            . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
                ? '?' . $_SERVER['QUERY_STRING']
                : '');

        self::redirect('/login');
    }

    private static function denyInvalidCsrf(string $path): void
    {
        if (self::isApiPath($path))
        {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(403);
        echo '403 - Token de segurança inválido. Atualize a página e tente novamente.';
        exit;
    }

    private static function redirect(string $path): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string) $config['base_url'], '/');
        if ($path !== '' && $path[0] === '/')
        {
            $path = substr($path, 1);
        }
        header('Location: ' . $base . '/' . $path);
        exit;
    }
}
