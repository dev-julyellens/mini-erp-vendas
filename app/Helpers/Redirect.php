<?php

declare(strict_types=1);

namespace App\Helpers;

final class Redirect
{
    public static function to(string $path): void
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string) $config['base_url'], '/');
        if ($path !== '' && $path[0] === '/')
        {
            $path = substr($path, 1);
        }
        header('Location: ' . $base . '/' . $path);
        exit;
    }

    public static function sanitizeIntendedUrl(mixed $url): string
    {
        if (!is_string($url) || $url === '' || $url === '/login' || $url === '/forgot-password')
        {
            return '/';
        }

        if (!str_starts_with($url, '/') || str_starts_with($url, '//'))
        {
            return '/';
        }

        if (preg_match('/[\r\n\x00]|:\/\/|@/u', $url) === 1)
        {
            return '/';
        }

        if (strlen($url) > 512)
        {
            return '/';
        }

        return $url;
    }
}
