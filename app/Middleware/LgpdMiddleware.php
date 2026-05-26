<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiRequest;
use App\Helpers\ApiResponse;
use App\Helpers\Auth;
use App\Helpers\PathHelper;
use App\Helpers\Redirect;
use App\Services\LgpdConsentService;

final class LgpdMiddleware
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'GET /lgpd/consent',
        'POST /lgpd/consent',
        'POST /logout',
    ];

    public static function handle(?string $method = null, ?string $path = null): void
    {
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $path ?? PathHelper::requestPath();
        $routeKey = $method . ' ' . $path;

        if (in_array($routeKey, self::SKIP_ROUTES, true))
        {
            return;
        }

        if (!Auth::check())
        {
            return;
        }

        $user = Auth::user();
        if ($user === null)
        {
            return;
        }

        $service = new LgpdConsentService();
        if ($service->hasCurrentConsent($user->id))
        {
            return;
        }

        if (ApiRequest::isApiPath($path))
        {
            ApiResponse::error('Consentimento LGPD pendente.', 403);
        }

        Redirect::to('/lgpd/consent');
    }
}
