<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;

final class AuditLogController extends Controller
{
    private const PER_PAGE = 20;

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
        $entity = isset($_GET['entity']) ? (string) $_GET['entity'] : null;

        $service = new AuditService();
        $result = $service->searchLogs($userId, $dateFrom, $dateTo, $entity, $page, self::PER_PAGE);

        $filters = [
            'user_id' => $userId,
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
            'entity' => $entity ?? '',
        ];

        $this->view('audit/index', [
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'users' => $result['users'],
            'filters' => $filters,
            'paginationQuery' => AuditService::filterQueryParams($filters),
            'actionLabels' => AuditService::ACTION_LABELS,
            'entityLabels' => AuditService::ENTITY_LABELS,
            'entities' => AuditService::ENTITIES,
        ]);
    }
}
