<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\Auth;
use App\Services\AccessLogService;

final class AccessLogMiddleware
{
    private static ?int $logId = null;

    /** @var list<string> */
    private const SKIP_PATH_PREFIXES = [
        '/assets/',
    ];

    public static function handle(string $method, string $path): void
    {
        if (self::shouldSkip($path))
        {
            return;
        }

        $userId = Auth::id();
        if ($userId === null && !ApiRequest::isApiPath($path))
        {
            return;
        }

        $service = new AccessLogService();
        self::$logId = $service->start($userId, $method, $path);
        self::registerShutdown();
    }

    private static function shouldSkip(string $path): bool
    {
        foreach (self::SKIP_PATH_PREFIXES as $prefix)
        {
            if (str_starts_with($path, $prefix))
            {
                return true;
            }
        }

        return false;
    }

    private static function registerShutdown(): void
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

            $service = new AccessLogService();
            $service->finish($logId, (int) $statusCode);
        });
    }
}
