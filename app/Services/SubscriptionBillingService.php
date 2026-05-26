<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Subscription;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionInvoiceRepository;
use App\Repositories\SubscriptionRepository;

/**
 * Cobrança recorrente da plataforma (SaaS), separada do PIX de clientes do ERP.
 */
final class SubscriptionBillingService
{
    private SubscriptionRepository $subscriptions;
    private SubscriptionInvoiceRepository $invoices;
    private PlanRepository $plans;

    public function __construct(
        ?SubscriptionRepository $subscriptions = null,
        ?SubscriptionInvoiceRepository $invoices = null,
        ?PlanRepository $plans = null
    )
    {
        $this->subscriptions = $subscriptions ?? new SubscriptionRepository();
        $this->invoices = $invoices ?? new SubscriptionInvoiceRepository();
        $this->plans = $plans ?? new PlanRepository();
    }

    /**
     * Gera faturas pendentes para assinaturas com período vencido.
     *
     * @return int Quantidade de faturas geradas
     */
    public function processDueRenewals(): int
    {
        $due = $this->subscriptions->listDueForRenewal(date('Y-m-d H:i:s'));
        $generated = 0;

        foreach ($due as $subscription)
        {
            if ($this->generateRenewalInvoice($subscription))
            {
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * Simula pagamento de fatura (mock / ambiente de desenvolvimento).
     */
    public function payInvoice(int $invoiceId): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try
        {
            $this->invoices->markPaid($invoiceId);

            $stmt = $pdo->prepare(
                'SELECT si.company_id, s.id AS subscription_id
                 FROM subscription_invoices si
                 INNER JOIN subscriptions s ON s.id = si.subscription_id
                 WHERE si.id = :id'
            );
            $stmt->execute(['id' => $invoiceId]);
            $row = $stmt->fetch();
            if ($row === false)
            {
                throw new \RuntimeException('Fatura não encontrada.');
            }

            $companyId = (int) $row['company_id'];
            $periodStart = date('Y-m-d H:i:s');
            $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
            $this->subscriptions->renewPeriod($companyId, $periodStart, $periodEnd, 'active');
            $pdo->commit();
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function generateRenewalInvoice(Subscription $subscription): bool
    {
        $plan = $this->plans->findById($subscription->plan_id);
        if ($plan === null)
        {
            return false;
        }

        $amount = $plan->price_monthly;
        if ((float) $amount <= 0)
        {
            $periodStart = date('Y-m-d H:i:s');
            $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
            $this->subscriptions->renewPeriod(
                $subscription->company_id,
                $periodStart,
                $periodEnd,
                'active'
            );

            return false;
        }

        $periodStart = $subscription->current_period_end;
        $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($periodStart)));
        $dueAt = date('Y-m-d H:i:s', strtotime($periodStart));

        $this->invoices->create(
            $subscription->id,
            $subscription->company_id,
            $amount,
            $periodStart,
            $periodEnd,
            $dueAt
        );
        $this->subscriptions->markPastDue($subscription->company_id);

        return true;
    }
}
