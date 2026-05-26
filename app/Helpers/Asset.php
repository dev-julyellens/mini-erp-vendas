<?php

declare(strict_types=1);

namespace App\Helpers;

final class Asset
{
    private static ?string $publicRoot = null;

    public static function versionedUrl(string $baseUrl, string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $url = rtrim($baseUrl, '/') . '/' . $relativePath;
        $fullPath = self::publicRoot() . '/' . $relativePath;

        if (!is_file($fullPath))
        {
            return $url;
        }

        return $url . '?v=' . filemtime($fullPath);
    }

    private static function publicRoot(): string
    {
        if (self::$publicRoot === null)
        {
            self::$publicRoot = dirname(__DIR__, 2) . '/public';
        }

        return self::$publicRoot;
    }
}
