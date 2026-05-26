<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface;

final class Env
{
    /**
     * Lê variável carregada do .env (ordem: $_ENV, $_SERVER, getenv).
     * Use após {@see load()}.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV))
        {
            return (string) $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER))
        {
            return (string) $_SERVER[$key];
        }

        $value = getenv($key);
        if ($value !== false)
        {
            return $value;
        }

        return $default;
    }

    /**
     * Carrega variáveis de ambiente via vlucas/phpdotenv.
     * Usa load() (não safeLoad) para o .env prevalecer sobre defaults do Apache/XAMPP.
     * Em caso de sintaxe inválida, usa parser legado.
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
                Dotenv::createImmutable($directory, $filename)->load();

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
        $raw = file_get_contents($path);
        if ($raw === false)
        {
            return;
        }

        if (str_starts_with($raw, "\xEF\xBB\xBF"))
        {
            $raw = substr($raw, 3);
        }

        $lines = preg_split('/\R/', $raw) ?: [];

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
