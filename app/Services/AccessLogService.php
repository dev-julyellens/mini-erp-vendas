<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\ApiRequest;
use App\Repositories\AccessLogRepository;

final class AccessLogService
{
    private AccessLogRepository $logs;

    public function __construct(?AccessLogRepository $logs = null)
    {
        $this->logs = $logs ?? new AccessLogRepository();
    }

    public function start(?int $userId, string $method, string $path): int
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
            ? substr($_SERVER['HTTP_USER_AGENT'], 0, 512)
            : null;

        return $this->logs->insert(
            $userId,
            ApiRequest::clientIp(),
            strtoupper($method),
            substr($path, 0, 255),
            null,
            $userAgent
        );
    }

    public function finish(int $logId, int $statusCode): void
    {
        if ($logId <= 0)
        {
            return;
        }

        $this->logs->updateStatus($logId, $statusCode);
    }

    /**
     * @return array{items: list<\App\Models\AccessLog>, total: int, users: list<array{id: int, name: string, email: string}>}
     */
    public function searchLogs(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $pathFilter,
        int $page,
        int $perPage
    ): array
    {
        return $this->logs->search($userId, $dateFrom, $dateTo, $pathFilter, $page, $perPage);
    }

    /**
     * @param array{user_id: ?int, date_from: string, date_to: string, path: string} $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $query = [];
        if ($filters['user_id'] !== null && $filters['user_id'] > 0)
        {
            $query['user_id'] = (string) $filters['user_id'];
        }
        if ($filters['date_from'] !== '')
        {
            $query['date_from'] = $filters['date_from'];
        }
        if ($filters['date_to'] !== '')
        {
            $query['date_to'] = $filters['date_to'];
        }
        if ($filters['path'] !== '')
        {
            $query['path'] = $filters['path'];
        }

        return $query;
    }
}
