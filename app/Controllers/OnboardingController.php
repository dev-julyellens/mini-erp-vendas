<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\CompanyContext;
use App\Helpers\Flash;
use App\Repositories\CompanyRepository;
use App\Services\OnboardingService;
use App\Services\PlanService;

final class OnboardingController extends Controller
{
    public function showCompany(): void
    {
        $company = (new CompanyRepository())->findById(CompanyContext::requireId());
        if ($company === null)
        {
            Flash::error('Empresa não encontrada.');
            $this->redirect('/select-company');
        }

        if ($company->hasCompletedOnboarding())
        {
            $this->redirect('/');
        }

        $this->view('onboarding/company', [
            'company' => $company,
            'errors' => [],
            'old' => [],
            'flash' => Flash::pull(),
        ], 'layouts/auth');
    }

    public function storeCompany(): void
    {
        $service = new OnboardingService();
        try
        {
            $service->saveCompanyProfile(
                (string) ($_POST['name'] ?? ''),
                isset($_POST['tax_id']) ? (string) $_POST['tax_id'] : null,
                (string) ($_POST['slug'] ?? '')
            );
            Flash::success('Dados da empresa salvos. Escolha seu plano.');
            $this->redirect('/onboarding/plan');
        }
        catch (ValidationException $e)
        {
            $company = (new CompanyRepository())->findById(CompanyContext::requireId());
            $this->view('onboarding/company', [
                'company' => $company,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ], 'layouts/auth');
        }
    }

    public function showPlan(): void
    {
        $service = new OnboardingService();
        if (!$service->needsOnboarding() && $service->currentStep() === OnboardingService::STEP_COMPLETED)
        {
            $this->redirect('/');
        }

        if ($service->currentStep() === OnboardingService::STEP_COMPANY)
        {
            $this->redirect('/onboarding');
        }

        $this->view('onboarding/plan', [
            'plans' => (new PlanService())->listPublicPlans(),
            'errors' => [],
            'flash' => Flash::pull(),
        ], 'layouts/auth');
    }

    public function storePlan(): void
    {
        $service = new OnboardingService();
        try
        {
            $service->selectPlan((string) ($_POST['plan_code'] ?? ''));
            Flash::success('Plano ativado. Bem-vindo ao sistema!');
            $this->redirect('/');
        }
        catch (ValidationException $e)
        {
            $this->view('onboarding/plan', [
                'plans' => (new PlanService())->listPublicPlans(),
                'errors' => $e->getErrors(),
                'flash' => Flash::pull(),
            ], 'layouts/auth');
        }
    }
}
