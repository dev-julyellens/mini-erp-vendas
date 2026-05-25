<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReportFilter;
use App\Repositories\ReportRepository;

final class ReportService
{
    public const PER_PAGE = 20;

    public const TYPE_SALES_PERIOD = 'sales-period';
    public const TYPE_SALES_CUSTOMER = 'sales-customer';
    public const TYPE_SALES_PRODUCT = 'sales-product';
    public const TYPE_TOP_PRODUCTS = 'top-products';
    public const TYPE_LOW_STOCK = 'low-stock';
    public const TYPE_CASH_FLOW = 'cash-flow';

    /** @var list<string> */
    public const SALES_TYPES = [
        self::TYPE_SALES_PERIOD,
        self::TYPE_SALES_CUSTOMER,
        self::TYPE_SALES_PRODUCT,
        self::TYPE_TOP_PRODUCTS,
    ];

    private ReportRepository $reports;

    public function __construct(?ReportRepository $reports = null)
    {
        $this->reports = $reports ?? new ReportRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $type, ReportFilter $filter): array
    {
        switch ($type)
        {
            case self::TYPE_SALES_PERIOD:
                return $this->reports->salesByPeriod($filter, self::PER_PAGE);
            case self::TYPE_SALES_CUSTOMER:
                return $this->reports->salesByCustomer($filter, self::PER_PAGE);
            case self::TYPE_SALES_PRODUCT:
                return $this->reports->salesByProduct($filter, self::PER_PAGE);
            case self::TYPE_TOP_PRODUCTS:
                return $this->reports->topSellingProducts($filter, self::PER_PAGE);
            case self::TYPE_LOW_STOCK:
                return $this->reports->lowStock($filter, self::PER_PAGE);
            case self::TYPE_CASH_FLOW:
                return $this->reports->cashFlow($filter, self::PER_PAGE);
            default:
                throw new \InvalidArgumentException('Tipo de relatório inválido.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dataForExport(string $type, ReportFilter $filter): array
    {
        switch ($type)
        {
            case self::TYPE_SALES_PERIOD:
                return $this->reports->salesByPeriodAll($filter);
            case self::TYPE_SALES_CUSTOMER:
                return $this->reports->salesByCustomerAll($filter);
            case self::TYPE_SALES_PRODUCT:
                return $this->reports->salesByProductAll($filter);
            case self::TYPE_TOP_PRODUCTS:
                return $this->reports->salesByProductAll($filter, 5000, true);
            case self::TYPE_LOW_STOCK:
                return $this->reports->lowStockAll($filter);
            case self::TYPE_CASH_FLOW:
                return $this->reports->cashFlowAll($filter);
            default:
                throw new \InvalidArgumentException('Tipo de relatório inválido.');
        }
    }

    public function title(string $type): string
    {
        return match ($type)
        {
            self::TYPE_SALES_PERIOD => 'Vendas por período',
            self::TYPE_SALES_CUSTOMER => 'Vendas por cliente',
            self::TYPE_SALES_PRODUCT => 'Vendas por produto',
            self::TYPE_TOP_PRODUCTS => 'Produtos mais vendidos',
            self::TYPE_LOW_STOCK => 'Estoque mínimo',
            self::TYPE_CASH_FLOW => 'Fluxo de caixa',
            default => 'Relatório',
        };
    }

    /**
     * @return list<string>
     */
    public function filterKeysForType(string $type): array
    {
        return match ($type)
        {
            self::TYPE_SALES_PERIOD => ['date_from', 'date_to', 'order_status'],
            self::TYPE_SALES_CUSTOMER => ['date_from', 'date_to', 'customer_id', 'order_status'],
            self::TYPE_SALES_PRODUCT, self::TYPE_TOP_PRODUCTS => [
                'date_from',
                'date_to',
                'product_id',
                'category_id',
                'order_status',
            ],
            self::TYPE_LOW_STOCK => ['category_id'],
            self::TYPE_CASH_FLOW => ['date_from', 'date_to', 'type'],
            default => [],
        };
    }

    public function routePath(string $type): string
    {
        return 'reports/' . $type;
    }

    public function exportRoutePath(string $type): string
    {
        return 'reports/' . $type . '/export';
    }
}
