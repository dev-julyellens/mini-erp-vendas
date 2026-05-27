<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PermissionRepository;

final class PermissionService
{
    public const ROLE_ADMIN = 'admin';

    /** @var array<string, list<string>> */
    private static array $cache = [];

    private PermissionRepository $permissions;

    public function __construct(?PermissionRepository $permissions = null)
    {
        $this->permissions = $permissions ?? new PermissionRepository();
    }

    public function isAdminRole(string $role): bool
    {
        return $role === self::ROLE_ADMIN;
    }

    public function can(string $role, string $module, string $action): bool
    {
        if ($this->isAdminRole($role))
        {
            return true;
        }

        $keys = $this->permissionKeysForRole($role);

        return in_array($module . '.' . $action, $keys, true);
    }

    public function canViewModule(string $role, string $module): bool
    {
        return $this->can($role, $module, 'visualizar');
    }

    /** Hub de relatórios: exige visualizar vendas, estoque ou financeiro. */
    public function canAccessReportsHub(string $role): bool
    {
        if ($this->isAdminRole($role))
        {
            return true;
        }

        return $this->canViewModule($role, 'vendas')
            || $this->canViewModule($role, 'estoque')
            || $this->canViewModule($role, 'financeiro');
    }

    /**
     * @return list<string>
     */
    public function permissionKeysForRole(string $role): array
    {
        if ($this->isAdminRole($role))
        {
            return $this->allPermissionKeys();
        }

        if (!isset(self::$cache[$role]))
        {
            self::$cache[$role] = $this->permissions->findPermissionKeysByRole($role);
        }

        return self::$cache[$role];
    }

    public function authorizeRoute(string $role, string $method, string $path): bool
    {
        if ($this->isAdminRole($role))
        {
            return true;
        }

        if ($method === 'GET' && $path === '/reports')
        {
            return $this->canAccessReportsHub($role);
        }

        $required = RoutePermissionMap::resolve($method, $path);
        if ($required === null)
        {
            return true;
        }

        return $this->can($role, $required['module'], $required['action']);
    }

    /**
     * @return list<string>
     */
    private function allPermissionKeys(): array
    {
        $modules = ['produtos', 'clientes', 'vendas', 'estoque', 'financeiro', 'usuarios'];
        $actions = ['visualizar', 'criar', 'editar', 'excluir'];
        $keys = [];
        foreach ($modules as $module)
        {
            foreach ($actions as $action)
            {
                $keys[] = $module . '.' . $action;
            }
        }

        return $keys;
    }
}
