<?php

declare(strict_types=1);

namespace App\Core;

final class ApiException extends \RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors
     */
    public function __construct(string $message, int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message, $statusCode);
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        $code = $this->getCode();
        return $code >= 400 && $code < 600 ? $code : 400;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
