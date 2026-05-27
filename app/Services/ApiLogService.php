<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Helpers\ApiRequest;
use App\Repositories\ApiLogRepository;

final class ApiLogService
{
    private ApiLogRepository $logs;

    public function __construct(?ApiLogRepository $logs = null)
    {
        $this->logs = $logs ?? new ApiLogRepository();
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function start(string $method, string $endpoint, ?int $userId, ?array $payload): int
    {
        try
        {
            return $this->logs->insert(
                $userId,
                ApiRequest::clientIp(),
                $method,
                $endpoint,
                ApiRequest::sanitizePayloadForLog($payload)
            );
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'Falha ao registrar log de API.', [
                'method' => $method,
                'endpoint' => $endpoint,
                'user_id' => $userId,
            ]);

            return 0;
        }
    }

    public function finish(int $logId, int $statusCode): void
    {
        if ($logId <= 0)
        {
            return;
        }

        try
        {
            $this->logs->updateStatusCode($logId, $statusCode);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'Falha ao atualizar log de API.', [
                'log_id' => $logId,
                'status_code' => $statusCode,
            ]);
        }
    }

    public function attachUser(int $logId, int $userId): void
    {
        if ($logId <= 0 || $userId <= 0)
        {
            return;
        }

        try
        {
            $this->logs->attachUserId($logId, $userId);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'Falha ao vincular usuário ao log de API.', [
                'log_id' => $logId,
                'user_id' => $userId,
            ]);
        }
    }
}
