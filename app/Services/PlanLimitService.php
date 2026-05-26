<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\PlanLimitExceededException;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Repositories\CompanyRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SubscriptionRepository;

final class PlanLimitService
{
    /** @var list<string> */
    public const LIMIT_KEYS = [
        'customers_max',
        'products_max',
        'users_max',
        'orders_month_max',
    ];

    private SubscriptionRepository $subscriptions;
    private PlanService $plans;
    private CustomerRepository $customers;
    private ProductRepository $products;
    private CompanyRepository $companies;
    private OrderRepository $orders;

    public function __construct(
        ?SubscriptionRepository $subscriptions = null,
        ?PlanService $plans = null,
        ?CustomerRepository $customers = null,
        ?ProductRepository $products = null,
        ?CompanyRepository $companies = null,
        ?OrderRepository $orders = null
    )
    {
        $this->subscriptions = $subscriptions ?? new SubscriptionRepository();
        $this->plans = $plans ?? new PlanService();
        $this->customers = $customers ?? new CustomerRepository();
        $this->products = $products ?? new ProductRepository();
        $this->companies = $companies ?? new CompanyRepository();
        $this->orders = $orders ?? new OrderRepository();
    }

    public function assertCanCreate(string $limitKey): void
    {
        $companyId = CompanyContext::requireId();
        $limit = $this->resolveLimit($companyId, $limitKey);
        if ($limit < 0)
        {
            return;
        }

        $current = $this->currentUsage($companyId, $limitKey);
        if ($current >= $limit)
        {
            throw new PlanLimitExceededException($limitKey, $limit, $current);
        }
    }

    /**
     * @throws ValidationException
     */
    public function assertCanCreateAsValidation(string $limitKey): void
    {
        try
        {
            $this->assertCanCreate($limitKey);
        }
        catch (PlanLimitExceededException $e)
        {
            throw new ValidationException($e->toFieldErrors());
        }
    }

    /**
     * @return array{limit: int, current: int, unlimited: bool}
     */
    public function usageSummary(int $companyId, string $limitKey): array
    {
        $limit = $this->resolveLimit($companyId, $limitKey);
        $current = $this->currentUsage($companyId, $limitKey);

        return [
            'limit' => $limit,
            'current' => $current,
            'unlimited' => $limit < 0,
        ];
    }

    /**
     * @return array<string, array{limit: int, current: int, unlimited: bool}>
     */
    public function allUsageForTenant(int $companyId): array
    {
        $summary = [];
        foreach (self::LIMIT_KEYS as $key)
        {
            $summary[$key] = $this->usageSummary($companyId, $key);
        }

        return $summary;
    }

    private function resolveLimit(int $companyId, string $limitKey): int
    {
        $subscription = $this->subscriptions->findByCompanyId($companyId);
        if ($subscription === null || !$subscription->isUsable())
        {
            return 0;
        }

        $limits = $this->plans->limitsForPlan($subscription->plan_id);

        return $limits[$limitKey] ?? -1;
    }

    private function currentUsage(int $companyId, string $limitKey): int
    {
        return match ($limitKey)
        {
            'customers_max' => $this->customers->countAll(),
            'products_max' => $this->products->countAll(),
            'users_max' => $this->companies->countUsers($companyId),
            'orders_month_max' => $this->orders->countCurrentMonth(),
            default => 0,
        };
    }
}
