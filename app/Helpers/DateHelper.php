<?php

declare(strict_types=1);

namespace App\Helpers;

final class DateHelper
{
    private const BR_DATE = 'd/m/Y';
    private const BR_DATETIME = 'd/m/Y H:i';

    private static ?\DateTimeZone $timezone = null;

    public static function toBrDate(string $value): string
    {
        $dt = self::parse($value);

        return $dt !== null ? $dt->format(self::BR_DATE) : $value;
    }

    public static function toBrDateTime(string $value): string
    {
        $dt = self::parse($value);

        return $dt !== null ? $dt->format(self::BR_DATETIME) : $value;
    }

    public static function nowBr(): string
    {
        return (new \DateTimeImmutable('now', self::timezone()))->format(self::BR_DATETIME);
    }

    private static function timezone(): \DateTimeZone
    {
        if (self::$timezone === null)
        {
            $config = require dirname(__DIR__, 2) . '/config/app.php';
            $tz = isset($config['timezone']) ? (string) $config['timezone'] : 'America/Sao_Paulo';
            try
            {
                self::$timezone = new \DateTimeZone($tz);
            }
            catch (\Throwable $e)
            {
                self::$timezone = new \DateTimeZone('America/Sao_Paulo');
            }
        }

        return self::$timezone;
    }

    private static function parse(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '')
        {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1)
        {
            $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, self::timezone());

            return $dt !== false ? $dt : null;
        }

        try
        {
            return new \DateTimeImmutable($value, self::timezone());
        }
        catch (\Throwable $e)
        {
            return null;
        }
    }
}
