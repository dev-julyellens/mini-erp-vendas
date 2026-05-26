<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\CompanyRepository;
use App\Services\PlatformAdminService;
use App\Services\SaasAdminService;

final class SaasAdminController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $this->assertAdmin();
        $service = new SaasAdminService();

        $this->view('admin/saas/index', [
            'metrics' => $service->dashboardMetrics(),
            'plans' => $service->listPlans(),
            'flash' => Flash::pull(),
        ]);
    }

    public function subscriptions(): void
    {
        $this->assertAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = isset($_GET['q']) ? (string) $_GET['q'] : null;

        $result = (new SaasAdminService())->listCompanySubscriptions(
            $page,
            self::PER_PAGE,
            $search
        );

        $this->view('admin/saas/subscriptions', [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'search' => $search ?? '',
            'companies' => (new CompanyRepository())->listForSelect(),
            'plans' => (new SaasAdminService())->listPlans(),
            'flash' => Flash::pull(),
        ]);
    }

    public function assignPlan(): void
    {
        $this->assertAdmin();
        try
        {
            (new SaasAdminService())->assignPlanToCompany(
                (int) ($_POST['company_id'] ?? 0),
                (string) ($_POST['plan_code'] ?? '')
            );
            Flash::success('Plano vinculado à empresa.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirect('/admin/saas/subscriptions');
    }

    private function assertAdmin(): void
    {
        try
        {
            (new PlatformAdminService())->assertPlatformAdmin();
        }
        catch (ValidationException $e)
        {
            Flash::error($e->getErrors()['access'] ?? 'Acesso negado.');
            $this->redirect('/');
        }
    }
}
