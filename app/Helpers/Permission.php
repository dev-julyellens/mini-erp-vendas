<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\PermissionService;

final class Permission
{
    private static ?PermissionService $service = null;

    public static function can(string $module, string $action): bool
    {
        $snapshot = Auth::sessionSnapshot();
        if ($snapshot === null || !isset($snapshot['role']))
        {
            return false;
        }

        return self::service()->can((string) $snapshot['role'], $module, $action);
    }

    public static function canView(string $module): bool
    {
        return self::can($module, 'visualizar');
    }

    private static function service(): PermissionService
    {
        if (self::$service === null)
        {
            self::$service = new PermissionService();
        }

        return self::$service;
    }
}
