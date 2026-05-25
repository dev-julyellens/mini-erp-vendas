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
        $user = $service->resolveUserFromToken($token);
        if ($user === null)
        {
            ApiResponse::error('Token JWT inválido ou expirado.', 401);
        }

        Auth::setJwtUser($user);
        ApiMiddleware::attachUserToLog($user->id);
    }
}
