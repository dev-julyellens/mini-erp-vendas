<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Logger;
use App\Core\PlanLimitExceededException;
use App\Core\ValidationException;
use App\Helpers\ApiRequest;
use App\Helpers\AppConfig;
use App\Helpers\Flash;
use App\Helpers\Redirect;

final class WebExceptionHandlerMiddleware
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
            $path = $_SERVER['REQUEST_URI'] ?? '/';
            if (ApiRequest::isApiPath($path))
            {
                throw $e;
            }

            if ($e instanceof ValidationException)
            {
                http_response_code(422);
                if (AppConfig::isDebug())
                {
                    echo '<h1>Erro de validação</h1><pre>'
                        . htmlspecialchars(json_encode($e->getErrors(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8')
                        . '</pre>';
                }
                else
                {
                    echo '<h1>Dados inválidos</h1><p>Verifique os campos e tente novamente.</p>';
                }

                return;
            }

            if ($e instanceof PlanLimitExceededException)
            {
                Flash::error($e->getMessage());
                Redirect::to('/subscription');

                return;
            }

            Logger::exception($e, 'Unhandled web exception');

            try
            {
                (new \App\Services\NotificationService())->notifyCriticalError(
                    $e->getMessage(),
                    'Web'
                );
            }
            catch (\Throwable $ignored)
            {
            }

            http_response_code(500);
            if (AppConfig::isDebug())
            {
                echo '<h1>Erro interno</h1><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
            }
            else
            {
                echo '<h1>Erro interno</h1><p>Ocorreu um erro inesperado. Tente novamente mais tarde.</p>';
            }
        });
    }
}
