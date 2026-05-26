<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\CompanyContext;
use App\Models\UserCompany;
use App\Repositories\CompanyRepository;
use App\Repositories\UserCompanyRepository;
use App\Repositories\UserRepository;

final class UserCompanyService
{
    private UserCompanyRepository $links;
    private UserRepository $users;
    private CompanyRepository $companies;
    private CompanyRoleService $roles;
    private PlatformAdminService $platformAdmin;

    public function __construct(
        ?UserCompanyRepository $links = null,
        ?UserRepository $users = null,
        ?CompanyRepository $companies = null,
        ?CompanyRoleService $roles = null,
        ?PlatformAdminService $platformAdmin = null
    )
    {
        $this->links = $links ?? new UserCompanyRepository();
        $this->users = $users ?? new UserRepository();
        $this->companies = $companies ?? new CompanyRepository();
        $this->roles = $roles ?? new CompanyRoleService();
        $this->platformAdmin = $platformAdmin ?? new PlatformAdminService();
    }

    /**
     * @return array{items: list<UserCompany>, total: int}
     */
    public function list(
        int $page,
        int $perPage,
        ?string $search,
        ?string $role,
        ?string $status,
        ?int $companyId = null
    ): array
    {
        if (!$this->platformAdmin->isPlatformAdmin() && $companyId === null)
        {
            $companyId = CompanyContext::requireId();
        }

        $activeOnly = match ($status)
        {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        return $this->links->paginate($page, $perPage, $companyId, null, $search, $role, $activeOnly);
    }

    public function attach(int $userId, int $companyId, string $role): void
    {
        $this->assertCanManageLinks($companyId);
        $this->validateLink($userId, $companyId, $role);

        $this->links->attach($userId, $companyId, $role);
        Audit::record('vincular', 'usuarios', $userId, null, [
            'company_id' => $companyId,
            'role' => $role,
        ]);
    }

    public function updateRole(int $userId, int $companyId, string $role): void
    {
        $this->assertCanManageLinks($companyId);
        if (!CompanyRoleService::isValid($role))
        {
            throw new ValidationException(['role' => 'Papel na empresa inválido.']);
        }

        if ($this->links->find($userId, $companyId) === null)
        {
            throw new ValidationException(['link' => 'Vínculo não encontrado.']);
        }

        $this->links->updateRole($userId, $companyId, $role);
        Audit::record('editar', 'usuarios', $userId, null, ['company_id' => $companyId, 'role' => $role]);
    }

    public function setLinkActive(int $userId, int $companyId, bool $active): void
    {
        $this->assertCanManageLinks($companyId);

        if ($this->links->find($userId, $companyId) === null)
        {
            throw new ValidationException(['link' => 'Vínculo não encontrado.']);
        }

        if (!$active && $this->links->countActiveCompaniesForUser($userId) <= 1)
        {
            throw new ValidationException(['link' => 'O usuário precisa de ao menos um vínculo ativo.']);
        }

        $this->links->setActive($userId, $companyId, $active);
        Audit::record($active ? 'ativar_vinculo' : 'desativar_vinculo', 'usuarios', $userId, null, [
            'company_id' => $companyId,
        ]);
    }

    public function detach(int $userId, int $companyId): void
    {
        $this->assertCanManageLinks($companyId);

        if ($this->links->find($userId, $companyId) === null)
        {
            throw new ValidationException(['link' => 'Vínculo não encontrado.']);
        }

        if ($this->links->countActiveCompaniesForUser($userId) <= 1)
        {
            throw new ValidationException(['link' => 'Não é possível remover o único vínculo do usuário.']);
        }

        $this->links->detach($userId, $companyId);
        Audit::record('desvincular', 'usuarios', $userId, null, ['company_id' => $companyId]);
    }

    private function validateLink(int $userId, int $companyId, string $role): void
    {
        if ($this->users->findById($userId) === null)
        {
            throw new ValidationException(['user_id' => 'Usuário não encontrado.']);
        }

        if ($this->companies->findById($companyId, false) === null)
        {
            throw new ValidationException(['company_id' => 'Empresa não encontrada.']);
        }

        if (!CompanyRoleService::isValid($role))
        {
            throw new ValidationException(['role' => 'Papel na empresa inválido.']);
        }
    }

    private function assertCanManageLinks(int $companyId): void
    {
        if ($this->platformAdmin->isPlatformAdmin())
        {
            return;
        }

        if (CompanyContext::id() !== $companyId)
        {
            throw new ValidationException(['access' => 'Sem permissão para gerenciar vínculos desta empresa.']);
        }
    }
}
