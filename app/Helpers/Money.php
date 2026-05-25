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
        $value = trim(str_replace([' ', "\u{00A0}"], '', $value));
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
}
