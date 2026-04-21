<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    /**
     * @param array<string, string> $errors field => message
     */
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
