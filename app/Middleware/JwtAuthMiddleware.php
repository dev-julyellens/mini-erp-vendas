<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Helpers\Auth;
use App\Services\ApiAuthService;

final class JwtAuthMiddleware
{
    public static function handle(string $method, string $path): void
    {
        if (!ApiRequest::isApiPath($path))
        {
            return;
        }

        if (Auth::check())
        {
            return;
        }

        $token = ApiRequest::bearerToken();
        if ($token === null)
        {
            return;
        }

        $service = new ApiAuthService();
        $resolved = $service->resolveUserFromToken($token);
        if ($resolved === null)
        {
            ApiResponse::error('Token JWT inválido ou expirado.', 401);
        }

        Auth::setJwtUser(
            $resolved['user'],
            $resolved['company_id'],
            $resolved['company_name']
        );
        ApiMiddleware::attachUserToLog($resolved['user']->id);
    }
}
