<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Permission;
use App\Models\ReportFilter;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ReportRepository;

final class DashboardService
{
    public const DAILY_CHART_DAYS = 14;
    public const MONTHLY_CHART_MONTHS = 12;
    public const PREVIEW_LIMIT = 8;
    public const TOP_PRODUCTS_DAYS = 30;

    private DashboardRepository $dashboard;
    private OrderRepository $orders;
    private ProductRepository $products;
    private CustomerRepository $customers;
    private ReportRepository $reports;
    private AccountsReceivableRepository $accounts;

    public function __construct(
        ?DashboardRepository $dashboard = null,
        ?OrderRepository $orders = null,
        ?ProductRepository $products = null,
        ?CustomerRepository $customers = null,
        ?ReportRepository $reports = null,
        ?AccountsReceivableRepository $accounts = null
    )
    {
        $this->dashboard = $dashboard ?? new DashboardRepository();
        $this->orders = $orders ?? new OrderRepository();
        $this->products = $products ?? new ProductRepository();
        $this->customers = $customers ?? new CustomerRepository();
        $this->reports = $reports ?? new ReportRepository();
        $this->accounts = $accounts ?? new AccountsReceivableRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $today = new \DateTimeImmutable('today');
        $dailyFrom = $today->modify('-' . (self::DAILY_CHART_DAYS - 1) . ' days')->format('Y-m-d');
        $dailyTo = $today->format('Y-m-d');
        $monthStart = $today->modify('first day of -' . (self::MONTHLY_CHART_MONTHS - 1) . ' months');

        $counts = [];
        if (Permission::canView('vendas'))
        {
            $counts['orders'] = $this->orders->countAll();
        }
        if (Permission::canView('produtos'))
        {
            $counts['products'] = $this->products->countAll();
        }
        if (Permission::canView('clientes'))
        {
            $counts['customers'] = $this->customers->countAll();
        }

        $sales = null;
        if (Permission::canView('vendas'))
        {
            $topFrom = $today->modify('-' . (self::TOP_PRODUCTS_DAYS - 1) . ' days')->format('Y-m-d');
            $topFilter = new ReportFilter(
                dateFrom: $topFrom,
                dateTo: $dailyTo,
                orderStatus: 'paid',
                page: 1
            );
            $topProducts = $this->reports->topSellingProducts($topFilter, self::PREVIEW_LIMIT);

            $sales = [
                'snapshot' => $this->dashboard->salesSnapshot(),
                'dailySeries' => $this->buildDailySeries($dailyFrom, $dailyTo, self::DAILY_CHART_DAYS),
                'monthlySeries' => $this->buildMonthlySeries(
                    $monthStart->format('Y-m'),
                    $today->format('Y-m'),
                    self::MONTHLY_CHART_MONTHS
                ),
                'topProducts' => $topProducts['items'],
            ];
        }

        $stock = null;
        if (Permission::canView('produtos') || Permission::canView('estoque'))
        {
            $stock = [
                'lowStock' => array_slice($this->products->findLowStock(), 0, self::PREVIEW_LIMIT),
                'lowStockTotal' => $this->dashboard->countLowStockProducts(),
            ];
        }

        $finance = null;
        if (Permission::canView('financeiro'))
        {
            $overduePage = $this->accounts->paginateFiltered(
                1,
                self::PREVIEW_LIMIT,
                null,
                null,
                null,
                null,
                true
            );

            $finance = [
                'overdueAccounts' => $overduePage['items'],
                'overdueCount' => $this->accounts->countOverdueOpen(),
                'overdueTotal' => $this->dashboard->sumOverdueRemaining(),
            ];
        }

        return [
            'counts' => $counts,
            'sales' => $sales,
            'stock' => $stock,
            'finance' => $finance,
        ];
    }

    /**
     * @return list<array{date: string, label: string, amount: string, order_count: int}>
     */
    private function buildDailySeries(string $dateFrom, string $dateTo, int $days): array
    {
        $rows = $this->dashboard->dailyRevenueBetween($dateFrom, $dateTo);
        $map = [];
        foreach ($rows as $row)
        {
            $map[$row['sale_date']] = $row;
        }

        $end = new \DateTimeImmutable($dateTo);
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--)
        {
            $day = $end->modify('-' . $i . ' days');
            $key = $day->format('Y-m-d');
            $row = $map[$key] ?? null;
            $series[] = [
                'date' => $key,
                'label' => $day->format('d/m'),
                'amount' => (string) ($row['total_amount'] ?? '0'),
                'order_count' => (int) ($row['order_count'] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{year_month: string, label: string, amount: string, order_count: int}>
     */
    private function buildMonthlySeries(string $monthFrom, string $monthTo, int $months): array
    {
        $rows = $this->dashboard->monthlySalesBetween($monthFrom, $monthTo);
        $map = [];
        foreach ($rows as $row)
        {
            $map[$row['year_month']] = $row;
        }

        $end = new \DateTimeImmutable($monthTo . '-01');
        $series = [];
        for ($i = $months - 1; $i >= 0; $i--)
        {
            $month = $end->modify('-' . $i . ' months');
            $key = $month->format('Y-m');
            $row = $map[$key] ?? null;
            $series[] = [
                'year_month' => $key,
                'label' => $month->format('m/Y'),
                'amount' => (string) ($row['total_amount'] ?? '0'),
                'order_count' => (int) ($row['order_count'] ?? 0),
            ];
        }

        return $series;
    }
}
