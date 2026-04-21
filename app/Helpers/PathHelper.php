<?php

declare(strict_types=1);

namespace App\Helpers;

final class PathHelper
{
    public static function requestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = dirname($scriptName);
        if ($dir !== '/' && $dir !== '.' && strpos($path, $dir) === 0) {
            $path = substr($path, strlen($dir)) ?: '/';
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        if ($path === '/index.php' || substr($path, -10) === '/index.php') {
            $path = '/';
        }

        return $path === '' ? '/' : $path;
    }
}
