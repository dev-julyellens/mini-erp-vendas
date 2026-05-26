<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validações reutilizáveis (DRY) para services.
 */
final class Validator
{
    /**
     * @return array{value: string, errors: array<string, string>}
     */
    public static function requiredString(
        mixed $value,
        string $field,
        string $message,
        int $maxLength = 255
    ): array
    {
        $sanitized = InputSanitizer::string($value, $maxLength);
        $errors = [];
        if ($sanitized === '')
        {
            $errors[$field] = $message;
        }

        return ['value' => $sanitized, 'errors' => $errors];
    }

    /**
     * @return array{value: string, errors: array<string, string>}
     */
    public static function email(mixed $value, string $field = 'email', string $message = 'Valid email is required.'): array
    {
        $sanitized = InputSanitizer::email($value);
        $errors = [];
        if ($sanitized === '' || !filter_var($sanitized, FILTER_VALIDATE_EMAIL))
        {
            $errors[$field] = $message;
        }

        return ['value' => $sanitized, 'errors' => $errors];
    }

    /**
     * @param array<string, string> $a
     * @param array<string, string> $b
     * @return array<string, string>
     */
    public static function mergeErrors(array $a, array $b): array
    {
        return array_merge($a, $b);
    }
}
