<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use App\Repositories\UserRepository;

final class Auth
{
    private const SESSION_KEY = 'auth_user';

    public static function check(): bool
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;

        return is_array($data) && isset($data['id']);
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? $user->id : null;
    }

    public static function user(): ?User
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($data) || !isset($data['id']))
        {
            return null;
        }

        $repo = new UserRepository();
        $user = $repo->findById((int) $data['id']);
        if ($user === null || !$user->active)
        {
            self::logout();
            return null;
        }

        self::syncSessionFromUser($user);

        return $user;
    }

    private static function syncSessionFromUser(User $user): void
    {
        $fresh = $user->toSessionArray();
        $current = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($current))
        {
            $_SESSION[self::SESSION_KEY] = $fresh;
            return;
        }

        if (
            $current['role'] !== $fresh['role']
            || $current['name'] !== $fresh['name']
            || $current['email'] !== $fresh['email']
        )
        {
            $_SESSION[self::SESSION_KEY] = $fresh;
        }
    }

    public static function login(User $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $user->toSessionArray();
        Csrf::regenerate();
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        Csrf::regenerate();

        if (session_status() === PHP_SESSION_ACTIVE)
        {
            session_regenerate_id(true);
        }
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function sessionSnapshot(): ?array
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($data) ? $data : null;
    }
}
