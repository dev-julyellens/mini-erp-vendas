<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Services\CashFlowService;
use App\Services\FinanceDashboardService;

final class FinanceController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $dashboard = new FinanceDashboardService();

        $this->view('finance/index', [
            'summary' => $dashboard->summary(),
            'flash' => Flash::pull(),
        ]);
    }

    public function cashFlow(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
        $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

        $service = new CashFlowService();
        $result = $service->search(
            $page,
            self::PER_PAGE,
            $type !== '' ? $type : null,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $filters = [
            'type' => $type,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        $this->view('finance/cash-flow', [
            'movements' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'paginationQuery' => CashFlowService::filterQueryParams($filters),
            'types' => \App\Models\CashFlow::TYPES,
            'typeLabels' => \App\Models\CashFlow::TYPE_LABELS,
            'flash' => Flash::pull(),
        ]);
    }
}
