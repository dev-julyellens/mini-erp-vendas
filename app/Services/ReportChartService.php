<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\ReportFilter;

final class ReportChartService
{
    private const TOP_N = 10;

    private const LOW_STOCK_TOP = 12;

    private const MAX_PERIOD_POINTS = 90;

    private const MAX_CASH_FLOW_DAYS = 60;

    private ReportService $reports;

    public function __construct(?ReportService $reports = null)
    {
        $this->reports = $reports ?? new ReportService();
    }

    /**
     * @return array{charts: list<array<string, mixed>>}|null
     */
    public function build(string $type, ReportFilter $filter): ?array
    {
        $charts = match ($type)
        {
            ReportService::TYPE_SALES_PERIOD => $this->salesPeriodCharts($filter),
            ReportService::TYPE_SALES_CUSTOMER => $this->salesCustomerCharts($filter),
            ReportService::TYPE_SALES_PRODUCT => $this->salesProductCharts($filter, false),
            ReportService::TYPE_TOP_PRODUCTS => $this->salesProductCharts($filter, true),
            ReportService::TYPE_LOW_STOCK => $this->lowStockCharts($filter),
            ReportService::TYPE_CASH_FLOW => $this->cashFlowCharts($filter),
            default => [],
        };

        if ($charts === [])
        {
            return null;
        }

        return ['charts' => $charts];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesPeriodCharts(ReportFilter $filter): array
    {
        $rows = $this->reports->dataForExport(ReportService::TYPE_SALES_PERIOD, $filter);
        if ($rows === [])
        {
            return [];
        }

        $slice = array_slice($rows, 0, self::MAX_PERIOD_POINTS);
        $slice = array_reverse($slice);

        $labels = [];
        $amounts = [];
        foreach ($slice as $row)
        {
            $labels[] = DateHelper::toBrDate((string) ($row['period_date'] ?? ''));
            $amounts[] = round((float) ($row['total_amount'] ?? 0), 2);
        }

        return [
            $this->chartSpec(
                'reportChartSalesPeriod',
                'Receita por dia',
                'Evolução no período filtrado (até ' . count($labels) . ' dias)',
                'col-12',
                'line',
                $labels,
                [
                    ['label' => 'Receita (R$)', 'data' => $amounts, 'color' => 'primary', 'currency' => true],
                ]
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesCustomerCharts(ReportFilter $filter): array
    {
        $rows = array_slice($this->reports->dataForExport(ReportService::TYPE_SALES_CUSTOMER, $filter), 0, self::TOP_N);
        if ($rows === [])
        {
            return [];
        }

        $labels = [];
        $amounts = [];
        foreach ($rows as $row)
        {
            $labels[] = $this->shortLabel((string) ($row['customer_name'] ?? '—'));
            $amounts[] = round((float) ($row['total_amount'] ?? 0), 2);
        }

        return [
            $this->chartSpec(
                'reportChartSalesCustomer',
                'Top clientes por receita',
                'Os ' . count($labels) . ' maiores no período',
                'col-12',
                'horizontalBar',
                $labels,
                [
                    ['label' => 'Receita (R$)', 'data' => $amounts, 'color' => 'secondary', 'currency' => true],
                ]
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesProductCharts(ReportFilter $filter, bool $byQuantity): array
    {
        $type = $byQuantity ? ReportService::TYPE_TOP_PRODUCTS : ReportService::TYPE_SALES_PRODUCT;
        $rows = array_slice($this->reports->dataForExport($type, $filter), 0, self::TOP_N);
        if ($rows === [])
        {
            return [];
        }

        $labels = [];
        $values = [];
        foreach ($rows as $row)
        {
            $labels[] = $this->shortLabel((string) ($row['product_name'] ?? '—'));
            $values[] = $byQuantity
                ? (int) ($row['quantity_sold'] ?? 0)
                : round((float) ($row['total_amount'] ?? 0), 2);
        }

        $title = $byQuantity ? 'Top produtos por quantidade' : 'Top produtos por receita';
        $datasetLabel = $byQuantity ? 'Quantidade' : 'Receita (R$)';

        return [
            $this->chartSpec(
                'reportChartSalesProduct',
                $title,
                'Os ' . count($labels) . ' primeiros no período',
                'col-12',
                'horizontalBar',
                $labels,
                [
                    [
                        'label' => $datasetLabel,
                        'data' => $values,
                        'color' => 'accent',
                        'currency' => !$byQuantity,
                    ],
                ]
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lowStockCharts(ReportFilter $filter): array
    {
        $rows = array_slice($this->reports->dataForExport(ReportService::TYPE_LOW_STOCK, $filter), 0, self::LOW_STOCK_TOP);
        if ($rows === [])
        {
            return [];
        }

        $labels = [];
        $deficits = [];
        foreach ($rows as $row)
        {
            $stock = (int) ($row['stock'] ?? 0);
            $min = (int) ($row['min_stock'] ?? 0);
            $labels[] = $this->shortLabel((string) ($row['product_name'] ?? '—'));
            $deficits[] = max(0, $min - $stock);
        }

        return [
            $this->chartSpec(
                'reportChartLowStock',
                'Maior déficit de estoque',
                'Produtos mais abaixo do mínimo',
                'col-12',
                'horizontalBar',
                $labels,
                [
                    ['label' => 'Déficit (un.)', 'data' => $deficits, 'color' => 'danger', 'currency' => false],
                ]
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cashFlowCharts(ReportFilter $filter): array
    {
        $rows = $this->reports->dataForExport(ReportService::TYPE_CASH_FLOW, $filter);
        if ($rows === [])
        {
            return [];
        }

        /** @var array<string, array{entrada: float, saida: float}> $byDay */
        $byDay = [];
        foreach ($rows as $row)
        {
            $occurred = (string) ($row['occurred_at'] ?? '');
            $day = strlen($occurred) >= 10 ? substr($occurred, 0, 10) : $occurred;
            if ($day === '')
            {
                continue;
            }
            if (!isset($byDay[$day]))
            {
                $byDay[$day] = ['entrada' => 0.0, 'saida' => 0.0];
            }
            $amount = (float) ($row['amount'] ?? 0);
            if (($row['type'] ?? '') === 'entrada')
            {
                $byDay[$day]['entrada'] += $amount;
            }
            else
            {
                $byDay[$day]['saida'] += $amount;
            }
        }

        if ($byDay === [])
        {
            return [];
        }

        ksort($byDay);
        $days = array_keys($byDay);
        if (count($days) > self::MAX_CASH_FLOW_DAYS)
        {
            $days = array_slice($days, -self::MAX_CASH_FLOW_DAYS);
        }

        $labels = [];
        $entradas = [];
        $saidas = [];
        foreach ($days as $day)
        {
            $labels[] = DateHelper::toBrDate($day);
            $entradas[] = round($byDay[$day]['entrada'], 2);
            $saidas[] = round($byDay[$day]['saida'], 2);
        }

        return [
            $this->chartSpec(
                'reportChartCashFlow',
                'Entradas e saídas por dia',
                'Totais agregados no período filtrado',
                'col-12',
                'stackedBar',
                $labels,
                [
                    ['label' => 'Entradas', 'data' => $entradas, 'color' => 'accent', 'currency' => true],
                    ['label' => 'Saídas', 'data' => $saidas, 'color' => 'danger', 'currency' => true],
                ]
            ),
        ];
    }

    /**
     * @param list<string> $labels
     * @param list<array{label: string, data: list<float|int>, color: string, currency: bool}> $datasets
     * @return array<string, mixed>
     */
    private function chartSpec(
        string $canvasId,
        string $title,
        string $subtitle,
        string $colClass,
        string $kind,
        array $labels,
        array $datasets
    ): array
    {
        return [
            'canvasId' => $canvasId,
            'dataId' => $canvasId . 'Data',
            'title' => $title,
            'subtitle' => $subtitle,
            'colClass' => $colClass,
            'payload' => [
                'kind' => $kind,
                'labels' => $labels,
                'datasets' => $datasets,
            ],
        ];
    }

    private function shortLabel(string $value, int $max = 32): string
    {
        $value = trim($value);
        if ($value === '')
        {
            return '—';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr'))
        {
            return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1) . '…' : $value;
        }

        return strlen($value) > $max ? substr($value, 0, $max - 1) . '…' : $value;
    }
}
