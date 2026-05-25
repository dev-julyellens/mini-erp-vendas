<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Services\ApiLogService;
use App\Services\ApiRateLimitService;

final class ApiMiddleware
{
    private static ?int $logId = null;

    public static function handle(string $method, string $path): void
    {
        if (!ApiRequest::isApiPath($path))
        {
            return;
        }

        self::enforceRateLimit($method, $path);
        self::startRequestLog($method, $path);
        self::registerShutdownLogger();
    }

    public static function logId(): ?int
    {
        return self::$logId;
    }

    public static function attachUserToLog(?int $userId): void
    {
        if (self::$logId === null || self::$logId <= 0 || $userId === null)
        {
            return;
        }

        $service = new ApiLogService();
        $service->attachUser(self::$logId, $userId);
    }

    private static function enforceRateLimit(string $method, string $path): void
    {
        $service = new ApiRateLimitService();

        if ($path === '/api/auth/login' && $method === 'POST')
        {
            $result = $service->checkLogin($method, $path);
        }
        else
        {
            $result = $service->check($method, $path);
        }

        if ($result['allowed'])
        {
            return;
        }

        header('Retry-After: ' . max(1, $result['retry_after']));
        ApiResponse::error(
            'Limite de requisições excedido. Tente novamente em ' . max(1, $result['retry_after']) . ' segundos.',
            429
        );
    }

    private static function startRequestLog(string $method, string $path): void
    {
        $payload = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true))
        {
            $payload = ApiRequest::jsonBody();
            if ($payload === null && ApiRequest::rawBody() !== '')
            {
                $payload = ['raw' => '[unparsed body]'];
            }
        }

        $service = new ApiLogService();
        self::$logId = $service->start($method, $path, null, $payload);
    }

    private static function registerShutdownLogger(): void
    {
        $logId = self::$logId;

        register_shutdown_function(static function () use ($logId): void
        {
            if ($logId === null || $logId <= 0)
            {
                return;
            }

            $statusCode = http_response_code();
            if ($statusCode === false)
            {
                $statusCode = 200;
            }

            $service = new ApiLogService();
            $service->finish($logId, (int) $statusCode);
        });
    }
}
