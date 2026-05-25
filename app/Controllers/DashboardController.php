<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Helpers\Permission;
use App\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $service = new DashboardService();
        $data = $service->build();

        $viewData = [
            'dashboard' => $data,
            'ordersCount' => (int) ($data['counts']['orders'] ?? 0),
            'productsCount' => (int) ($data['counts']['products'] ?? 0),
            'customersCount' => (int) ($data['counts']['customers'] ?? 0),
            'lowStockProducts' => $data['stock']['lowStock'] ?? [],
            'flash' => Flash::pull(),
        ];

        if (Permission::canView('vendas'))
        {
            $viewData['pageScripts'] = [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
                'assets/js/dashboard.js',
            ];
        }

        $this->view('dashboard/index', $viewData);
    }
}
