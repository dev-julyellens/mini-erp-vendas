<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\AuditService;

/**
 * API reutilizável para registrar auditoria a partir dos Services.
 */
final class Audit
{
    private static ?AuditService $service = null;

    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public static function record(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): void
    {
        self::service()->record($action, $entity, $entityId, $oldValues, $newValues, $userId);
    }

    private static function service(): AuditService
    {
        if (self::$service === null)
        {
            self::$service = new AuditService();
        }

        return self::$service;
    }
}
