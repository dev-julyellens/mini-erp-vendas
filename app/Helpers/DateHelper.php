<?php

declare(strict_types=1);

namespace App\Helpers;

final class DateHelper
{
    public static function toBrDateTime(string $value): string
    {
        try {
            $dt = new \DateTimeImmutable($value);
            return $dt->format('d/m/Y H:i:s');
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
