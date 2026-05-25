<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Helpers\CompanyContext;

trait CompanyScope
{
    protected function companyId(): int
    {
        return CompanyContext::requireId();
    }

    /**
     * @return array{company_id: int}
     */
    protected function companyParams(): array
    {
        return ['company_id' => $this->companyId()];
    }
}
