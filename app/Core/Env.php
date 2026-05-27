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
     *
     * @throws \RuntimeException Se Dotenv não estiver instalado ou o .env for inválido
     */
    public static function load(string $path): void
    {
        if (!is_readable($path))
        {
            return;
        }

        if (!class_exists(Dotenv::class))
        {
            throw new \RuntimeException(
                'Dependência vlucas/phpdotenv ausente. Execute composer install na raiz do projeto.'
            );
        }

        $directory = dirname($path);
        $filename = basename($path);

        try
        {
            Dotenv::createImmutable($directory, $filename)->load();
        }
        catch (ExceptionInterface $e)
        {
            throw new \RuntimeException(
                'Arquivo .env inválido (' . $path . '): ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
