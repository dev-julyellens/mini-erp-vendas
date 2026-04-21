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

    /**
     * @return array{success?: string, error?: string}
     */
    public static function pull(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
