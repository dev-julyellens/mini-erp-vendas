<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Auth;

/**
 * Acesso administrativo da plataforma (gestão global de empresas/usuários/SaaS).
 */
final class PlatformAdminService
{
    public function assertPlatformAdmin(): void
    {
        $user = Auth::user();
        if ($user === null || $user->role !== PermissionService::ROLE_ADMIN)
        {
            throw new ValidationException(['access' => 'Acesso restrito a administradores da plataforma.']);
        }
    }

    public function isPlatformAdmin(): bool
    {
        $user = Auth::user();

        return $user !== null && $user->role === PermissionService::ROLE_ADMIN;
    }
}
