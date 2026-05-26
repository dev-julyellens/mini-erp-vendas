<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\ApiRequest;
use App\Helpers\Audit;
use App\Helpers\SecurityConfig;
use App\Repositories\LgpdConsentRepository;

final class LgpdConsentService
{
    private LgpdConsentRepository $consents;

    public function __construct(?LgpdConsentRepository $consents = null)
    {
        $this->consents = $consents ?? new LgpdConsentRepository();
    }

    public function currentPolicyVersion(): string
    {
        return SecurityConfig::lgpdPolicyVersion();
    }

    public function hasCurrentConsent(int $userId): bool
    {
        return $this->consents->hasAcceptedVersion($userId, $this->currentPolicyVersion());
    }

    public function recordConsent(int $userId, bool $accepted): void
    {
        if (!$accepted)
        {
            throw new ValidationException([
                'consent' => 'É necessário aceitar a política de privacidade para utilizar o sistema.',
            ]);
        }

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
            ? substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null;

        $version = $this->currentPolicyVersion();
        $this->consents->recordConsent($userId, $version, ApiRequest::clientIp(), $userAgent);

        Audit::record(
            'consentimento_lgpd',
            'usuarios',
            $userId,
            null,
            ['policy_version' => $version],
            $userId
        );
    }
}
