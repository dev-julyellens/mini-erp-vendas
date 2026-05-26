<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use App\Helpers\CompanyContext;

trait RequiresPostgresTrait
{
    private static ?bool $postgresAvailable = null;

    protected function requirePostgres(): void
    {
        if (self::$postgresAvailable === null)
        {
            self::$postgresAvailable = $this->probePostgres();
        }

        if (!self::$postgresAvailable)
        {
            $this->markTestSkipped('PostgreSQL indisponível: configure DB_* em config/.env para testes de integração.');
        }
    }

    protected function resetTestContext(): void
    {
        CompanyContext::clearJwt();
        Database::resetForTesting();
    }

    private function probePostgres(): bool
    {
        try
        {
            Database::resetForTesting();
            Database::getConnection()->query('SELECT 1');

            return true;
        }
        catch (\Throwable)
        {
            Database::resetForTesting();

            return false;
        }
    }
}
