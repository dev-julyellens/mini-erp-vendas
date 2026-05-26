<?php

declare(strict_types=1);

namespace App\Helpers;

final class DataMask
{
    public static function email(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@'))
        {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        if ($local === '')
        {
            return '***@' . $domain;
        }

        $visible = strlen($local) <= 1 ? '*' : substr($local, 0, 1);

        return $visible . '***@' . $domain;
    }

    public static function phone(?string $phone): string
    {
        if ($phone === null || trim($phone) === '')
        {
            return '—';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4)
        {
            return '****';
        }

        $suffix = substr($digits, -4);
        $prefix = substr($digits, 0, 2);

        return '(' . $prefix . ') *****-' . $suffix;
    }
}
