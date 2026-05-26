<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\DataMask;
use App\Helpers\SecurityConfig;
use App\Services\AccessLogService;

final class AccessLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $userId = isset($_GET['user_id']) && $_GET['user_id'] !== ''
            ? (int) $_GET['user_id']
            : null;
        if ($userId !== null && $userId <= 0)
        {
            $userId = null;
        }

        $dateFrom = isset($_GET['date_from']) ? (string) $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? (string) $_GET['date_to'] : null;
        $pathFilter = isset($_GET['path']) ? trim((string) $_GET['path']) : null;

        $service = new AccessLogService();
        $result = $service->searchLogs($userId, $dateFrom, $dateTo, $pathFilter, $page, self::PER_PAGE);

        $filters = [
            'user_id' => $userId,
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
            'path' => $pathFilter ?? '',
        ];

        $maskEmails = SecurityConfig::maskSensitiveDataInLists();

        $this->view('access-logs/index', [
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'users' => $result['users'],
            'filters' => $filters,
            'paginationQuery' => AccessLogService::filterQueryParams($filters),
            'maskEmails' => $maskEmails,
            'maskEmail' => static fn(string $email): string => $maskEmails ? DataMask::email($email) : $email,
        ]);
    }
}
