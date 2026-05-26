<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Helpers\Flash;
use App\Services\PlanLimitService;
use App\Services\PlanService;
use App\Services\SubscriptionBillingService;
use App\Services\SubscriptionService;
use App\Repositories\SubscriptionInvoiceRepository;

final class SubscriptionController extends Controller
{
    public function show(): void
    {
        $companyId = CompanyContext::requireId();
        $subscription = (new SubscriptionService())->current();
        $invoices = (new SubscriptionInvoiceRepository())->listByCompany($companyId);
        $pending = (new SubscriptionInvoiceRepository())->findLatestPending($companyId);
        $limits = (new PlanLimitService())->allUsageForTenant($companyId);
        $plans = (new PlanService())->listPublicPlans();

        $this->view('subscription/show', [
            'subscription' => $subscription,
            'invoices' => $invoices,
            'pendingInvoice' => $pending,
            'limits' => $limits,
            'plans' => $plans,
            'flash' => Flash::pull(),
        ]);
    }

    public function pay(): void
    {
        $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
        if ($invoiceId <= 0)
        {
            Flash::error('Fatura inválida.');
            $this->redirect('/subscription');
        }

        try
        {
            (new SubscriptionBillingService())->payInvoiceForCompany(
                $invoiceId,
                CompanyContext::requireId()
            );
            Flash::success('Pagamento registrado. Assinatura reativada.');
        }
        catch (ValidationException $e)
        {
            Flash::error($e->getErrors()['invoice_id'] ?? 'Não foi possível processar o pagamento.');
        }
        catch (\Throwable $e)
        {
            Flash::error('Não foi possível processar o pagamento.');
        }

        $this->redirect('/subscription');
    }

    public function changePlan(): void
    {
        try
        {
            $companyId = CompanyContext::requireId();
            (new SubscriptionService())->subscribeCompany(
                $companyId,
                (string) ($_POST['plan_code'] ?? '')
            );
            Flash::success('Plano atualizado com sucesso.');
        }
        catch (ValidationException $e)
        {
            Flash::error($e->getErrors()['plan_code'] ?? 'Não foi possível alterar o plano.');
        }

        $this->redirect('/subscription');
    }
}
