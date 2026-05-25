<?php

declare(strict_types=1);

namespace App\Helpers;

final class Money
{
    public static function mul(string $unitPrice, int $quantity): string
    {
        if (function_exists('bcmul'))
        {
            return bcmul($unitPrice, (string) $quantity, 2);
        }

        return number_format((float) $unitPrice * $quantity, 2, '.', '');
    }

    public static function add(string $a, string $b): string
    {
        if (function_exists('bcadd'))
        {
            return bcadd($a, $b, 2);
        }

        return number_format((float) $a + (float) $b, 2, '.', '');
    }

    public static function sub(string $a, string $b): string
    {
        if (function_exists('bcsub'))
        {
            return bcsub($a, $b, 2);
        }

        return number_format((float) $a - (float) $b, 2, '.', '');
    }

    public static function compare(string $a, string $b): int
    {
        if (function_exists('bccomp'))
        {
            return bccomp($a, $b, 2);
        }

        $fa = (float) $a;
        $fb = (float) $b;

        return $fa <=> $fb;
    }

    public static function normalizeDecimal(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', '', $value) ?? $value);
        if (str_contains($value, ',') && str_contains($value, '.'))
        {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        elseif (str_contains($value, ','))
        {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    public static function validatePositive(string $value): bool
    {
        $value = self::normalizeDecimal($value);

        return (bool) preg_match('/^\d+(\.\d{1,2})?$/', $value) && (float) $value > 0;
    }

    public static function validateNonNegativeInt(string $value): bool
    {
        return (bool) preg_match('/^\d+$/', $value) && (int) $value >= 0;
    }

    /**
     * Divide um valor em N parcelas; a última absorve centavos residuais.
     *
     * @return list<string>
     */
    public static function splitAmount(string $total, int $parts): array
    {
        if ($parts < 1)
        {
            throw new \InvalidArgumentException('parts must be >= 1');
        }

        if ($parts === 1)
        {
            return [$total];
        }

        if (function_exists('bcdiv') && function_exists('bcmul') && function_exists('bcsub'))
        {
            $base = bcdiv($total, (string) $parts, 2);
            $amounts = [];
            for ($i = 1; $i < $parts; $i++)
            {
                $amounts[] = $base;
            }
            $allocated = bcmul($base, (string) ($parts - 1), 2);
            $amounts[] = bcsub($total, $allocated, 2);

            return $amounts;
        }

        $base = round((float) $total / $parts, 2);
        $amounts = array_fill(0, $parts - 1, number_format($base, 2, '.', ''));
        $allocated = $base * ($parts - 1);
        $amounts[] = number_format((float) $total - $allocated, 2, '.', '');

        return $amounts;
    }
}
