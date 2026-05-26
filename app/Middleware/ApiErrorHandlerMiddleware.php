<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiException;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\ApiResponse;
use App\Helpers\AppConfig;

final class ApiErrorHandlerMiddleware
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered)
        {
            return;
        }

        self::$registered = true;

        set_exception_handler(static function (\Throwable $e): void
        {
            if ($e instanceof ValidationException)
            {
                ApiResponse::error('Erro de validação.', 422, $e->getErrors());
            }

            if ($e instanceof ApiException)
            {
                ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getErrors());
            }

            Logger::exception($e, 'API unhandled exception');

            try
            {
                (new \App\Services\NotificationService())->notifyCriticalError(
                    $e->getMessage(),
                    'API'
                );
            }
            catch (\Throwable $ignored)
            {
            }

            $message = AppConfig::isDebug() ? $e->getMessage() : 'Erro interno do servidor.';
            ApiResponse::error($message, 500);
        });
    }
}
