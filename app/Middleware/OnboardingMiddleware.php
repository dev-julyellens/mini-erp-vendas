<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\PathHelper;
use App\Helpers\Redirect;
use App\Services\OnboardingService;

final class OnboardingMiddleware
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'GET /onboarding',
        'POST /onboarding/company',
        'GET /onboarding/plan',
        'POST /onboarding/plan',
        'POST /logout',
        'GET /select-company',
        'POST /select-company',
        'GET /lgpd/consent',
        'POST /lgpd/consent',
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if (in_array($routeKey, self::SKIP_ROUTES, true) || !Auth::check())
        {
            return;
        }

        if (!CompanyContext::hasSelected())
        {
            return;
        }

        $service = new OnboardingService();
        if (!$service->needsOnboarding())
        {
            return;
        }

        if (ApiRequest::isApiPath($path))
        {
            ApiResponse::error('Onboarding da empresa pendente.', 403);
        }

        $step = $service->currentStep();
        $target = $step === OnboardingService::STEP_PLAN
            ? '/onboarding/plan'
            : '/onboarding';

        if ($path !== $target && $path !== '/onboarding' && $path !== '/onboarding/plan')
        {
            Redirect::to($target);
        }
    }
}
