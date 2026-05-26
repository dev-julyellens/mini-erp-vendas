<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;

final class SubscriptionService
{
    private SubscriptionRepository $subscriptions;
    private PlanRepository $plans;

    public function __construct(
        ?SubscriptionRepository $subscriptions = null,
        ?PlanRepository $plans = null
    )
    {
        $this->subscriptions = $subscriptions ?? new SubscriptionRepository();
        $this->plans = $plans ?? new PlanRepository();
    }

    public function current(): ?Subscription
    {
        if (!CompanyContext::hasSelected())
        {
            return null;
        }

        return $this->subscriptions->findByCompanyId(CompanyContext::requireId());
    }

    public function assertActiveSubscription(): void
    {
        $subscription = $this->current();
        if ($subscription === null || !$subscription->isUsable())
        {
            throw new ValidationException([
                'subscription' => 'Assinatura inativa ou expirada. Regularize em Assinatura.',
            ]);
        }
    }

    public function subscribeCompany(int $companyId, string $planCode): Subscription
    {
        $plan = $this->plans->findByCode($planCode);
        if ($plan === null)
        {
            throw new ValidationException(['plan_code' => 'Plano inválido.']);
        }

        $existing = $this->subscriptions->findByCompanyId($companyId);
        if ($existing !== null)
        {
            $this->changePlan($companyId, $plan);

            return $this->subscriptions->findByCompanyId($companyId)
                ?? throw new \RuntimeException('Assinatura não encontrada após troca de plano.');
        }

        return $this->createSubscription($companyId, $plan);
    }

    public function changePlan(int $companyId, Plan $plan): void
    {
        $status = $plan->trial_days > 0 ? 'trialing' : 'active';
        $this->subscriptions->updatePlan($companyId, $plan->id, $status);
    }

    private function createSubscription(int $companyId, Plan $plan): Subscription
    {
        $now = time();
        $periodStart = date('Y-m-d H:i:s', $now);
        $trialEnds = $plan->trial_days > 0
            ? date('Y-m-d H:i:s', $now + $plan->trial_days * 86400)
            : null;
        $periodEnd = $trialEnds ?? date('Y-m-d H:i:s', strtotime('+1 month', $now));
        $status = $plan->trial_days > 0 ? 'trialing' : 'active';

        $this->subscriptions->create(
            $companyId,
            $plan->id,
            $status,
            $periodStart,
            $periodEnd,
            $trialEnds
        );

        $subscription = $this->subscriptions->findByCompanyId($companyId);
        if ($subscription === null)
        {
            throw new \RuntimeException('Falha ao criar assinatura.');
        }

        return $subscription;
    }

    public function markPastDue(int $companyId): void
    {
        $this->subscriptions->updateStatus($companyId, 'past_due');
    }

    public function activate(int $companyId): void
    {
        $this->subscriptions->updateStatus($companyId, 'active');
    }
}
