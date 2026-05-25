<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Flash;
use App\Helpers\Permission;
use App\Models\ReportFilter;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Services\ReportExportService;
use App\Services\ReportFilterService;
use App\Services\ReportService;

final class ReportController extends Controller
{
    private ReportService $reports;
    private ReportFilterService $filterService;

    public function __construct()
    {
        $this->reports = new ReportService();
        $this->filterService = new ReportFilterService();
    }

    public function index(): void
    {
        $canSales = Permission::canView('vendas');
        $canStock = Permission::canView('estoque');
        $canFinance = Permission::canView('financeiro');

        if (!$canSales && !$canStock && !$canFinance)
        {
            http_response_code(403);
            echo '403 - Você não tem permissão para acessar relatórios.';
            exit;
        }

        $this->view('reports/index', [
            'canSales' => $canSales,
            'canStock' => $canStock,
            'canFinance' => $canFinance,
            'flash' => Flash::pull(),
        ]);
    }

    public function salesPeriod(): void
    {
        $this->renderReport(ReportService::TYPE_SALES_PERIOD, 'reports/sales-period');
    }

    public function salesCustomer(): void
    {
        $this->renderReport(ReportService::TYPE_SALES_CUSTOMER, 'reports/sales-customer', true, false);
    }

    public function salesProduct(): void
    {
        $this->renderReport(ReportService::TYPE_SALES_PRODUCT, 'reports/sales-product', false, true);
    }

    public function topProducts(): void
    {
        $this->renderReport(ReportService::TYPE_TOP_PRODUCTS, 'reports/top-products', false, true);
    }

    public function lowStock(): void
    {
        $this->renderReport(ReportService::TYPE_LOW_STOCK, 'reports/low-stock', false, false, true);
    }

    public function cashFlow(): void
    {
        $this->renderReport(ReportService::TYPE_CASH_FLOW, 'reports/cash-flow', false, false, false, true);
    }

    public function exportSalesPeriod(): void
    {
        $this->export(ReportService::TYPE_SALES_PERIOD);
    }

    public function exportSalesCustomer(): void
    {
        $this->export(ReportService::TYPE_SALES_CUSTOMER);
    }

    public function exportSalesProduct(): void
    {
        $this->export(ReportService::TYPE_SALES_PRODUCT);
    }

    public function exportTopProducts(): void
    {
        $this->export(ReportService::TYPE_TOP_PRODUCTS);
    }

    public function exportLowStock(): void
    {
        $this->export(ReportService::TYPE_LOW_STOCK);
    }

    public function exportCashFlow(): void
    {
        $this->export(ReportService::TYPE_CASH_FLOW);
    }

    private function renderReport(
        string $type,
        string $template,
        bool $withCustomers = false,
        bool $withProducts = false,
        bool $withCategoriesOnly = false,
        bool $withCashFlowTypes = false
    ): void
    {
        $keys = $this->reports->filterKeysForType($type);
        $filter = $this->filterService->fromRequest($_GET, $keys);
        $result = $this->reports->run($type, $filter);

        $data = [
            'title' => $this->reports->title($type),
            'reportType' => $type,
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $filter->page,
            'perPage' => ReportService::PER_PAGE,
            'filters' => $this->filterService->filtersForView($filter, $keys),
            'filterKeys' => $keys,
            'paginationQuery' => $this->filterService->toQueryParams($filter, $keys),
            'reportPath' => $this->reports->routePath($type),
            'exportPath' => $this->reports->exportRoutePath($type),
            'summary' => $result['summary'] ?? null,
            'flash' => Flash::pull(),
        ];

        if ($withCustomers)
        {
            $data['customers'] = (new CustomerRepository())->allOrderedByName();
        }
        if ($withProducts)
        {
            $data['products'] = (new ProductRepository())->allOrderedByName();
            $data['categories'] = (new CategoryRepository())->allOrderedByName();
        }
        if ($withCategoriesOnly)
        {
            $data['categories'] = (new CategoryRepository())->allOrderedByName();
        }
        if ($withCashFlowTypes)
        {
            $data['types'] = \App\Models\CashFlow::TYPES;
            $data['typeLabels'] = \App\Models\CashFlow::TYPE_LABELS;
        }
        if (in_array('order_status', $keys, true))
        {
            $data['orderStatuses'] = ReportFilter::ORDER_STATUSES;
        }

        $this->view($template, $data);
    }

    private function export(string $type): void
    {
        $format = trim((string) ($_GET['format'] ?? ''));
        $keys = $this->reports->filterKeysForType($type);
        $filter = $this->filterService->fromRequest($_GET, $keys);

        try
        {
            (new ReportExportService())->export($type, $format, $filter);
        }
        catch (\InvalidArgumentException $e)
        {
            Flash::error($e->getMessage());
            $this->redirect('/' . $this->reports->routePath($type) . '?' . http_build_query(
                $this->filterService->toQueryParams($filter, $keys)
            ));
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro ao gerar exportação do relatório.');
            $this->redirect('/' . $this->reports->routePath($type));
        }
    }
}
