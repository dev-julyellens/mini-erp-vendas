<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\ApiRequest;

final class ApiPayloadService
{
    /**
     * @return array<string, mixed>
     */
    public function requireJsonObject(): array
    {
        $payload = ApiRequest::jsonBody();
        if ($payload === null)
        {
            $raw = ApiRequest::rawBody();
            if ($raw === '')
            {
                throw new ValidationException(['body' => 'Corpo da requisição vazio.']);
            }

            throw new ValidationException(['body' => 'JSON inválido.']);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $requiredFields
     * @return array<string, string>
     */
    public function validateRequired(array $payload, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field)
        {
            if (!array_key_exists($field, $payload) || $this->isEmptyValue($payload[$field]))
            {
                $errors[$field] = 'Campo obrigatório.';
            }
        }

        return $errors;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null)
        {
            return true;
        }

        if (is_string($value))
        {
            return trim($value) === '';
        }

        return false;
    }
}
