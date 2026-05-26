<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validação de filtros de data (YYYY-MM-DD) para API e relatórios.
 */
final class DateFilter
{
    /**
     * @return array<string, string> Erros por campo (vazio se válido)
     */
    public static function validateOptionalIsoDate(string $value, string $field): array
    {
        if ($value === '')
        {
            return [];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value))
        {
            return [$field => 'Use o formato AAAA-MM-DD.'];
        }

        $parts = explode('-', $value);
        if (!checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]))
        {
            return [$field => 'Data inválida.'];
        }

        return [];
    }
}
