<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Repositories\PlanRepository;

final class PlanService
{
    private PlanRepository $plans;

    public function __construct(?PlanRepository $plans = null)
    {
        $this->plans = $plans ?? new PlanRepository();
    }

    /**
     * @return list<Plan>
     */
    public function listPublicPlans(): array
    {
        return $this->plans->listActive();
    }

    public function findByCode(string $code): ?Plan
    {
        return $this->plans->findByCode($code);
    }

    /**
     * @return array<string, int>
     */
    public function limitsForPlan(int $planId): array
    {
        return $this->plans->limitsForPlan($planId);
    }
}
