<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface;

final class Env
{
    /**
     * Carrega variáveis de ambiente via vlucas/phpdotenv.
     * Em caso de .env com sintaxe legada (ex.: valores com espaço sem aspas), usa fallback.
     */
    public static function load(string $path): void
    {
        if (!is_readable($path))
        {
            return;
        }

        $directory = dirname($path);
        $filename = basename($path);

        if (class_exists(Dotenv::class))
        {
            try
            {
                Dotenv::createImmutable($directory, $filename)->safeLoad();

                return;
            }
            catch (ExceptionInterface)
            {
                // .env legado: delega ao parser anterior
            }
        }

        self::loadLegacy($path);
    }

    /**
     * @deprecated Mantido apenas como fallback sem Composer.
     */
    private static function loadLegacy(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false)
        {
            return;
        }

        foreach ($lines as $line)
        {
            $line = trim($line);
            if ($line === '' || $line[0] === '#')
            {
                continue;
            }

            if (!preg_match('/^(?:export\s+)?([\w.-]+)\s*=\s*(.*)$/', $line, $m))
            {
                continue;
            }

            $name = $m[1];
            $value = trim($m[2]);

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'"))
            {
                $quote = $value[0];
                if (substr($value, -1) === $quote)
                {
                    $value = substr($value, 1, -1);
                    $value = str_replace(['\\' . $quote, '\\\\'], [$quote, '\\'], $value);
                }
            }
            else
            {
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false)
                {
                    $value = trim(substr($value, 0, $hashPos));
                }
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
