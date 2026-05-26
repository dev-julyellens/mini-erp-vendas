<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Auth;
use App\Helpers\Flash;
use App\Helpers\Redirect;
use App\Helpers\SecurityConfig;

final class SessionService
{
    private const LAST_ACTIVITY_KEY = 'session_last_activity';
    private const STARTED_AT_KEY = 'session_started_at';

    public function initializeOnLogin(): void
    {
        $now = time();
        $_SESSION[self::LAST_ACTIVITY_KEY] = $now;
        $_SESSION[self::STARTED_AT_KEY] = $now;
    }

    public function touch(): void
    {
        if (!Auth::check())
        {
            return;
        }

        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
    }

    /**
     * @return array{expired: bool, reason: ?string}
     */
    public function validate(): array
    {
        if (!Auth::check())
        {
            return ['expired' => false, 'reason' => null];
        }

        $now = time();
        $lastActivity = (int) ($_SESSION[self::LAST_ACTIVITY_KEY] ?? 0);
        $startedAt = (int) ($_SESSION[self::STARTED_AT_KEY] ?? 0);

        if ($startedAt <= 0 || $lastActivity <= 0)
        {
            $this->initializeOnLogin();
            return ['expired' => false, 'reason' => null];
        }

        $idleLimit = SecurityConfig::sessionIdleTimeout();
        if (($now - $lastActivity) > $idleLimit)
        {
            return ['expired' => true, 'reason' => 'inatividade'];
        }

        $absoluteLimit = SecurityConfig::sessionAbsoluteTimeout();
        if (($now - $startedAt) > $absoluteLimit)
        {
            return ['expired' => true, 'reason' => 'tempo_maximo'];
        }

        return ['expired' => false, 'reason' => null];
    }

    public function expireSession(string $reason): void
    {
        Auth::logout();
        unset($_SESSION[self::LAST_ACTIVITY_KEY], $_SESSION[self::STARTED_AT_KEY]);

        $message = $reason === 'tempo_maximo'
            ? 'Sessão encerrada por tempo máximo. Faça login novamente.'
            : 'Sessão expirada por inatividade. Faça login novamente.';

        Flash::warning($message);
        Redirect::to('/login');
    }
}
