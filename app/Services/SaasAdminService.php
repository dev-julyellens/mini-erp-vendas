<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Audit;
use App\Core\ValidationException;
use App\Repositories\CompanyRepository;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;

final class SaasAdminService
{
    private PlanRepository $plans;
    private SubscriptionRepository $subscriptions;
    private CompanyRepository $companies;
    private PlatformAdminService $platformAdmin;

    public function __construct(
        ?PlanRepository $plans = null,
        ?SubscriptionRepository $subscriptions = null,
        ?CompanyRepository $companies = null,
        ?PlatformAdminService $platformAdmin = null
    )
    {
        $this->plans = $plans ?? new PlanRepository();
        $this->subscriptions = $subscriptions ?? new SubscriptionRepository();
        $this->companies = $companies ?? new CompanyRepository();
        $this->platformAdmin = $platformAdmin ?? new PlatformAdminService();
    }

    /**
     * @return array{
     *   companies_total: int,
     *   companies_active: int,
     *   users_total: int,
     *   subscriptions_active: int,
     *   subscriptions_trialing: int
     * }
     */
    public function dashboardMetrics(): array
    {
        $this->platformAdmin->assertPlatformAdmin();

        $db = Database::getConnection();

        return [
            'companies_total' => (int) $db->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
            'companies_active' => (int) $db->query('SELECT COUNT(*) FROM companies WHERE active = TRUE')->fetchColumn(),
            'users_total' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'subscriptions_active' => (int) $db->query(
                "SELECT COUNT(*) FROM subscriptions WHERE status = 'active'"
            )->fetchColumn(),
            'subscriptions_trialing' => (int) $db->query(
                "SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'"
            )->fetchColumn(),
        ];
    }

    /**
     * @return list<\App\Models\Plan>
     */
    public function listPlans(): array
    {
        $this->platformAdmin->assertPlatformAdmin();

        return $this->plans->listAll();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function listCompanySubscriptions(int $page, int $perPage, ?string $search): array
    {
        $this->platformAdmin->assertPlatformAdmin();

        return $this->subscriptions->paginateAdmin($page, $perPage, $search);
    }

    public function assignPlanToCompany(int $companyId, string $planCode): void
    {
        $this->platformAdmin->assertPlatformAdmin();

        if ($this->companies->findById($companyId, false) === null)
        {
            throw new ValidationException(['company_id' => 'Empresa não encontrada.']);
        }

        (new SubscriptionService())->subscribeCompany($companyId, $planCode);
        Audit::record('alterar_plano', 'usuarios', $companyId, null, ['plan_code' => $planCode]);
    }
}
