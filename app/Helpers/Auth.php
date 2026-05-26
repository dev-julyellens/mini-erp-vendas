<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use App\Repositories\UserRepository;

final class Auth
{
    private const SESSION_KEY = 'auth_user';
    private const PENDING_USER_KEY = 'pending_auth_user_id';

    private static ?User $jwtUser = null;

    public static function check(): bool
    {
        if (self::$jwtUser !== null)
        {
            return true;
        }

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
        if (self::$jwtUser !== null)
        {
            return self::$jwtUser;
        }

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
        $current = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($current))
        {
            return;
        }

        $companyId = isset($current['company_id']) ? (int) $current['company_id'] : null;
        $companyName = isset($current['company_name']) ? (string) $current['company_name'] : null;
        $companyRole = isset($current['company_role']) ? (string) $current['company_role'] : null;
        $fresh = $user->toSessionArray($companyId, $companyName, $companyRole);

        if (
            $current['role'] !== $fresh['role']
            || $current['name'] !== $fresh['name']
            || $current['email'] !== $fresh['email']
        )
        {
            $_SESSION[self::SESSION_KEY] = $fresh;
        }
    }

    public static function login(
        User $user,
        ?int $companyId = null,
        ?string $companyName = null,
        ?string $companyRole = null
    ): void
    {
        self::$jwtUser = null;
        CompanyContext::clearJwt();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $user->toSessionArray($companyId, $companyName, $companyRole);
        unset($_SESSION[self::PENDING_USER_KEY]);
        Csrf::regenerate();
    }

    public static function setCompany(int $companyId, string $companyName, ?string $companyRole = null): void
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($data) || !isset($data['id']))
        {
            return;
        }

        $data['company_id'] = $companyId;
        $data['company_name'] = trim($companyName);
        if ($companyRole !== null && $companyRole !== '')
        {
            $data['company_role'] = $companyRole;
        }
        else
        {
            unset($data['company_role']);
        }
        $_SESSION[self::SESSION_KEY] = $data;
    }

    public static function setCompanyRole(?string $companyRole): void
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($data))
        {
            return;
        }

        if ($companyRole !== null && $companyRole !== '')
        {
            $data['company_role'] = $companyRole;
        }
        else
        {
            unset($data['company_role']);
        }
        $_SESSION[self::SESSION_KEY] = $data;
    }

    public static function setPendingUserId(int $userId): void
    {
        $_SESSION[self::PENDING_USER_KEY] = $userId;
    }

    public static function pullPendingUserId(): ?int
    {
        if (!isset($_SESSION[self::PENDING_USER_KEY]))
        {
            return null;
        }

        $id = (int) $_SESSION[self::PENDING_USER_KEY];
        unset($_SESSION[self::PENDING_USER_KEY]);

        return $id > 0 ? $id : null;
    }

    public static function peekPendingUserId(): ?int
    {
        if (!isset($_SESSION[self::PENDING_USER_KEY]))
        {
            return null;
        }

        $id = (int) $_SESSION[self::PENDING_USER_KEY];

        return $id > 0 ? $id : null;
    }

    public static function setJwtUser(User $user, int $companyId, string $companyName): void
    {
        self::$jwtUser = $user;
        CompanyContext::setJwtCompanyId($companyId);
        $_SESSION[self::SESSION_KEY] = $user->toSessionArray($companyId, $companyName);
    }

    public static function logout(): void
    {
        self::$jwtUser = null;
        CompanyContext::clearJwt();
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::PENDING_USER_KEY]);
        Csrf::regenerate();

        if (session_status() === PHP_SESSION_ACTIVE)
        {
            session_regenerate_id(true);
        }
    }

    /**
     * @return array{
     *   id: int,
     *   name: string,
     *   email: string,
     *   role: string,
     *   company_id?: int,
     *   company_name?: string
     * }|null
     */
    public static function sessionSnapshot(): ?array
    {
        $data = $_SESSION[self::SESSION_KEY] ?? null;
        return is_array($data) ? $data : null;
    }
}
