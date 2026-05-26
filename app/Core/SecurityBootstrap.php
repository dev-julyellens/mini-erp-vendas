<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\AppConfig;

/**
 * Validações de configuração obrigatórias antes de servir requisições.
 */
final class SecurityBootstrap
{
    private const JWT_DEV_FALLBACK = 'mini-erp-dev-jwt-secret-change-in-production';

    private const MIN_JWT_SECRET_LENGTH = 32;

    public static function assertSafeConfiguration(): void
    {
        if (self::shouldSkipChecks())
        {
            return;
        }

        self::assertProductionDebugDisabled();
        self::assertJwtSecret();
    }

    private static function shouldSkipChecks(): bool
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL'))
        {
            return true;
        }

        $argv = $_SERVER['argv'] ?? [];
        if (is_array($argv) && in_array('check-encoding.php', $argv, true))
        {
            return true;
        }

        return false;
    }

    private static function assertProductionDebugDisabled(): void
    {
        if (!AppConfig::isProduction())
        {
            return;
        }

        if (AppConfig::isDebug())
        {
            throw new \RuntimeException(
                'APP_DEBUG deve ser false quando APP_ENV=production. Corrija config/.env antes de continuar.'
            );
        }
    }

    private static function assertJwtSecret(): void
    {
        if (AppConfig::isDebug())
        {
            return;
        }

        $secret = Env::get('JWT_SECRET');
        if ($secret === null || $secret === '')
        {
            throw new \RuntimeException(
                'JWT_SECRET é obrigatório quando APP_DEBUG=false. Defina um segredo aleatório longo em config/.env.'
            );
        }

        if ($secret === self::JWT_DEV_FALLBACK || strlen($secret) < self::MIN_JWT_SECRET_LENGTH)
        {
            throw new \RuntimeException(
                'JWT_SECRET inválido para produção: use um valor aleatório com pelo menos '
                    . self::MIN_JWT_SECRET_LENGTH . ' caracteres (não use o padrão de desenvolvimento).'
            );
        }
    }

    public static function jwtDevFallback(): string
    {
        return self::JWT_DEV_FALLBACK;
    }
}
