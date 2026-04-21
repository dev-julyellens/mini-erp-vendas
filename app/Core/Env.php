<?php

declare(strict_types=1);

namespace App\Core;

final class Env
{
    /**
     * Loads KEY=value pairs from a .env file into putenv() / $_ENV / $_SERVER.
     * Supports optional spaces around '=' and lines like: DB_HOST = 127.0.0.1
     */
    public static function load(string $path): void
    {
        if (!is_readable($path))
        {
            return;
        }

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
