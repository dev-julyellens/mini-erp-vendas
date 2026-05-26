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
use App\Services\SubscriptionService;

final class SubscriptionMiddleware
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'GET /subscription',
        'POST /subscription/pay',
        'POST /subscription/change-plan',
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

        if ((new OnboardingService())->needsOnboarding())
        {
            return;
        }

        $subscription = (new SubscriptionService())->current();
        if ($subscription !== null && $subscription->isUsable())
        {
            return;
        }

        if (ApiRequest::isApiPath($path))
        {
            ApiResponse::error('Assinatura inativa ou expirada.', 402);
        }

        if ($path !== '/subscription')
        {
            Redirect::to('/subscription');
        }
    }
}
