<?php

declare(strict_types=1);

namespace App\Helpers;

final class Flash
{
    public static function success(string $message): void
    {
        $_SESSION['flash']['success'] = $message;
    }

    public static function error(string $message): void
    {
        $_SESSION['flash']['error'] = $message;
    }

    public static function warning(string $message): void
    {
        $_SESSION['flash']['warning'] = $message;
    }

    public static function info(string $message): void
    {
        $_SESSION['flash']['info'] = $message;
    }

    /**
     * @return array{success?: string, error?: string, warning?: string, info?: string}
     */
    public static function pull(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
