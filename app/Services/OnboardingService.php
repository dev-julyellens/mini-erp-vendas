<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\InputSanitizer;
use App\Models\Company;
use App\Repositories\CompanyRepository;

final class OnboardingService
{
    public const STEP_COMPANY = 'company_profile';
    public const STEP_PLAN = 'plan_selection';
    public const STEP_COMPLETED = 'completed';

    private CompanyRepository $companies;
    private SubscriptionService $subscriptions;

    public function __construct(
        ?CompanyRepository $companies = null,
        ?SubscriptionService $subscriptions = null
    )
    {
        $this->companies = $companies ?? new CompanyRepository();
        $this->subscriptions = $subscriptions ?? new SubscriptionService();
    }

    public function needsOnboarding(?Company $company = null): bool
    {
        $company ??= $this->companies->findById(CompanyContext::requireId());
        if ($company === null)
        {
            return false;
        }

        return !$company->hasCompletedOnboarding();
    }

    public function currentStep(): string
    {
        $company = $this->companies->findById(CompanyContext::requireId());
        if ($company === null)
        {
            return self::STEP_COMPLETED;
        }

        if ($company->hasCompletedOnboarding())
        {
            return self::STEP_COMPLETED;
        }

        return $company->onboarding_step !== ''
            ? $company->onboarding_step
            : self::STEP_COMPANY;
    }

    public function saveCompanyProfile(string $name, ?string $taxId, string $slug): void
    {
        $companyId = CompanyContext::requireId();
        $errors = [];

        $name = InputSanitizer::string($name, 255);
        if ($name === '')
        {
            $errors['name'] = 'Nome da empresa é obrigatório.';
        }

        $slug = $this->normalizeSlug($slug !== '' ? $slug : $name);
        if ($slug === '')
        {
            $errors['slug'] = 'Identificador (slug) inválido.';
        }
        elseif ($this->companies->slugExists($slug, $companyId))
        {
            $errors['slug'] = 'Este identificador já está em uso.';
        }

        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $taxId = $taxId !== null && trim($taxId) !== ''
            ? InputSanitizer::string($taxId, 20)
            : null;

        $this->companies->updateProfile($companyId, $name, $taxId, $slug);
        $this->companies->updateOnboardingStep($companyId, self::STEP_PLAN);
        Auth::setCompany($companyId, $name);

        $userId = Auth::id();
        if ($userId !== null)
        {
            $this->companies->setOwner($companyId, $userId);
        }
    }

    public function selectPlan(string $planCode): void
    {
        $companyId = CompanyContext::requireId();
        Database::transaction(function (\PDO $pdo) use ($companyId, $planCode): void
        {
            $this->subscriptions->subscribeCompany($companyId, $planCode);
            $this->companies->completeOnboarding($companyId);
        });
    }

    private function normalizeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return substr($slug, 0, 80);
    }
}
