<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\PermissionService;
use App\Services\TenantContextService;

final class Permission
{
    private static ?PermissionService $service = null;

    public static function can(string $module, string $action): bool
    {
        if (!Auth::check())
        {
            return false;
        }

        $role = self::effectiveRole();

        return $role !== '' && self::service()->can($role, $module, $action);
    }

    public static function canView(string $module): bool
    {
        return self::can($module, 'visualizar');
    }

    public static function canAccessReportsHub(): bool
    {
        if (!Auth::check())
        {
            return false;
        }

        $role = self::effectiveRole();

        return $role !== '' && self::service()->canAccessReportsHub($role);
    }

    private static function service(): PermissionService
    {
        if (self::$service === null)
        {
            self::$service = new PermissionService();
        }

        return self::$service;
    }

    private static function effectiveRole(): string
    {
        return (new TenantContextService())->resolveEffectiveAclRole();
    }
}
