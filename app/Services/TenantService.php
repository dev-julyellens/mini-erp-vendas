<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CompanyContext;
use App\Models\Company;
use App\Repositories\CompanyRepository;

/**
 * Camada de tenant: empresa ativa = tenant isolado por company_id.
 */
final class TenantService
{
    private CompanyRepository $companies;

    public function __construct(?CompanyRepository $companies = null)
    {
        $this->companies = $companies ?? new CompanyRepository();
    }

    public function currentTenantId(): int
    {
        return CompanyContext::requireId();
    }

    public function currentTenant(): ?Company
    {
        return $this->companies->findById($this->currentTenantId());
    }

    public function assertTenantAccess(int $userId, int $companyId): void
    {
        if (!$this->companies->userHasAccess($userId, $companyId))
        {
            throw new \RuntimeException('Acesso negado ao tenant.');
        }
    }
}
