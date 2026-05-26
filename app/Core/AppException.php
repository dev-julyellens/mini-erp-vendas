<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Exceção base da aplicação para erros de domínio previsíveis.
 */
class AppException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'app_error',
        int $code = 0,
        ?\Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
