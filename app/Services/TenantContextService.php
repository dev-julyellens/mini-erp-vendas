<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Repositories\UserCompanyRepository;

/**
 * Carrega e mantém o contexto multiempresa na sessão (empresa ativa + papel na empresa).
 */
final class TenantContextService
{
    private UserCompanyRepository $userCompanies;
    private CompanyRoleService $roles;

    public function __construct(
        ?UserCompanyRepository $userCompanies = null,
        ?CompanyRoleService $roles = null
    )
    {
        $this->userCompanies = $userCompanies ?? new UserCompanyRepository();
        $this->roles = $roles ?? new CompanyRoleService();
    }

    public function refreshCompanyRoleInSession(): void
    {
        $snapshot = Auth::sessionSnapshot();
        if ($snapshot === null || !isset($snapshot['id'], $snapshot['company_id']))
        {
            return;
        }

        $userId = (int) $snapshot['id'];
        $companyId = (int) $snapshot['company_id'];
        if ($companyId <= 0)
        {
            return;
        }

        $companyRole = $this->userCompanies->getRoleForUserInCompany($userId, $companyId);
        Auth::setCompanyRole($companyRole);
    }

    public function resolveEffectiveAclRole(): string
    {
        $user = Auth::user();
        if ($user === null)
        {
            return '';
        }

        $snapshot = Auth::sessionSnapshot();
        $companyRole = is_array($snapshot) && isset($snapshot['company_role'])
            ? (string) $snapshot['company_role']
            : null;

        if ($companyRole === null && CompanyContext::hasSelected())
        {
            $this->refreshCompanyRoleInSession();
            $snapshot = Auth::sessionSnapshot();
            $companyRole = is_array($snapshot) && isset($snapshot['company_role'])
                ? (string) $snapshot['company_role']
                : null;
        }

        return $this->roles->effectiveAclRole($user->role, $companyRole);
    }
}
