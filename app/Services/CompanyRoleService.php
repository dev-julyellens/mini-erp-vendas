<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Papéis por empresa e mapeamento para ACL global do ERP.
 */
final class CompanyRoleService
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EMPLOYEE = 'employee';

    /** @var list<string> */
    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_EMPLOYEE,
    ];

    public static function isValid(string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    /**
     * Papel efetivo para checagem ACL: admin global ou mapeamento do papel na empresa.
     */
    public function effectiveAclRole(string $globalRole, ?string $companyRole): string
    {
        if ($globalRole === PermissionService::ROLE_ADMIN)
        {
            return PermissionService::ROLE_ADMIN;
        }

        if ($companyRole === null || $companyRole === '')
        {
            return $globalRole;
        }

        return match ($companyRole)
        {
            self::ROLE_OWNER, self::ROLE_ADMIN => PermissionService::ROLE_ADMIN,
            self::ROLE_MANAGER => 'vendedor',
            self::ROLE_EMPLOYEE => 'estoque',
            default => $globalRole,
        };
    }

    public function label(string $role): string
    {
        return match ($role)
        {
            self::ROLE_OWNER => 'Proprietário',
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_MANAGER => 'Gerente',
            self::ROLE_EMPLOYEE => 'Colaborador',
            default => $role,
        };
    }
}
