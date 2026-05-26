<?php

declare(strict_types=1);

namespace App\Helpers;

final class ChartA11yHelper
{
    /**
     * @param list<string> $labels
     * @param list<array{label?: string, data?: list<float|int>, currency?: bool}> $datasets
     * @return list<string>
     */
    public static function columnHeaders(array $labels, array $datasets): array
    {
        if ($labels === [])
        {
            return [];
        }

        $headers = ['Período'];
        foreach ($datasets as $dataset)
        {
            $headers[] = (string) ($dataset['label'] ?? 'Valor');
        }

        return $headers;
    }

    /**
     * @param list<string> $labels
     * @param list<array{label?: string, data?: list<float|int>, currency?: bool}> $datasets
     * @return list<list<string>>
     */
    public static function tableRows(array $labels, array $datasets): array
    {
        $rows = [];
        foreach ($labels as $index => $label)
        {
            $row = [(string) $label];
            foreach ($datasets as $dataset)
            {
                $data = $dataset['data'] ?? [];
                $value = $data[$index] ?? 0;
                $row[] = self::formatValue($value, !empty($dataset['currency']));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public static function formatValue(float|int $value, bool $currency): string
    {
        if ($currency)
        {
            return 'R$ ' . number_format((float) $value, 2, ',', '.');
        }

        return number_format((float) $value, 0, ',', '.');
    }
}
