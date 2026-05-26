<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;

final class CompanyAuthService
{
    private CompanyRepository $companies;

    public function __construct(?CompanyRepository $companies = null)
    {
        $this->companies = $companies ?? new CompanyRepository();
    }

    /**
     * @return list<Company>
     */
    public function listAccessibleCompanies(int $userId): array
    {
        return $this->companies->listActiveForUser($userId);
    }

    public function completeLoginWithCompany(User $user, int $companyId): void
    {
        if (!$this->companies->userHasAccess($user->id, $companyId))
        {
            throw new ValidationException(['company_id' => 'Você não tem acesso a esta empresa.']);
        }

        $company = $this->companies->findById($companyId);
        if ($company === null)
        {
            throw new ValidationException(['company_id' => 'Empresa inválida ou inativa.']);
        }

        Auth::login($user, $company->id, $company->name);
        (new SessionService())->initializeOnLogin();
    }

    /**
     * @return array{auto: bool, company?: Company, companies?: list<Company>}
     */
    public function resolvePostCredentialFlow(int $userId): array
    {
        $companies = $this->listAccessibleCompanies($userId);
        if ($companies === [])
        {
            throw new ValidationException(['login' => 'Usuário sem empresa vinculada. Contate o administrador.']);
        }

        if (count($companies) === 1)
        {
            return ['auto' => true, 'company' => $companies[0]];
        }

        return ['auto' => false, 'companies' => $companies];
    }

    public function switchCompany(int $userId, int $companyId): void
    {
        if (!$this->companies->userHasAccess($userId, $companyId))
        {
            throw new ValidationException(['company_id' => 'Você não tem acesso a esta empresa.']);
        }

        $company = $this->companies->findById($companyId);
        if ($company === null)
        {
            throw new ValidationException(['company_id' => 'Empresa inválida ou inativa.']);
        }

        Auth::setCompany($company->id, $company->name);
    }

    public function requireSelectedCompany(): void
    {
        if (!CompanyContext::hasSelected())
        {
            throw new ValidationException(['company' => 'Selecione uma empresa para continuar.']);
        }
    }
}
