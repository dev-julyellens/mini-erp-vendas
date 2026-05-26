<?php

declare(strict_types=1);

namespace App\Helpers;

final class InputSanitizer
{
    public static function string(mixed $value, int $maxLength = 500): string
    {
        if (!is_string($value) && !is_numeric($value))
        {
            return '';
        }

        $text = trim(strip_tags((string) $value));
        if ($maxLength > 0 && strlen($text) > $maxLength)
        {
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }

    public static function email(mixed $value): string
    {
        $email = strtolower(self::string($value, 255));

        return filter_var($email, FILTER_SANITIZE_EMAIL) !== false ? $email : '';
    }

    public static function phone(mixed $value): ?string
    {
        if ($value === null || $value === '')
        {
            return null;
        }

        $digits = preg_replace('/\D+/', '', self::string($value, 30));
        if ($digits === null || $digits === '')
        {
            return null;
        }

        return strlen($digits) <= 20 ? $digits : substr($digits, 0, 20);
    }

    public static function positiveInt(mixed $value): ?int
    {
        if (!is_numeric($value))
        {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
