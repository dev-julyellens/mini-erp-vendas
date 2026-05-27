<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashFlow;
use App\Models\ReportFilter;

final class ReportFilterService
{
    /**
     * @param array<string, mixed> $query
     * @param list<string> $keys
     */
    public function fromRequest(array $query, array $keys): ReportFilter
    {
        $dateFrom = $this->optionalDate($query, 'date_from');
        $dateTo = $this->optionalDate($query, 'date_to');

        if ($dateFrom === null && $dateTo === null && in_array('date_from', $keys, true))
        {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }

        $customerId = null;
        if (in_array('customer_id', $keys, true) && isset($query['customer_id']) && $query['customer_id'] !== '')
        {
            $customerId = max(0, (int) $query['customer_id']) ?: null;
        }

        $productId = null;
        if (in_array('product_id', $keys, true) && isset($query['product_id']) && $query['product_id'] !== '')
        {
            $productId = max(0, (int) $query['product_id']) ?: null;
        }

        $categoryId = null;
        if (in_array('category_id', $keys, true) && isset($query['category_id']) && $query['category_id'] !== '')
        {
            $categoryId = max(0, (int) $query['category_id']) ?: null;
        }

        $orderStatus = null;
        if (in_array('order_status', $keys, true) && isset($query['order_status']))
        {
            $raw = trim((string) $query['order_status']);
            if ($raw !== '' && in_array($raw, ReportFilter::ORDER_STATUSES, true))
            {
                $orderStatus = $raw;
            }
        }

        $cashFlowType = null;
        if (in_array('type', $keys, true) && isset($query['type']))
        {
            $raw = trim((string) $query['type']);
            if ($raw !== '' && in_array($raw, CashFlow::TYPES, true))
            {
                $cashFlowType = $raw;
            }
        }

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo)
        {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $page = max(1, (int) ($query['page'] ?? 1));

        return new ReportFilter(
            $dateFrom,
            $dateTo,
            $customerId,
            $productId,
            $categoryId,
            $orderStatus,
            $cashFlowType,
            $page
        );
    }

    /**
     * @param list<string> $keys
     * @return array<string, string>
     */
    public function toQueryParams(ReportFilter $filter, array $keys): array
    {
        $out = [];

        if (in_array('date_from', $keys, true) && $filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $out['date_from'] = $filter->dateFrom;
        }
        if (in_array('date_to', $keys, true) && $filter->dateTo !== null && $filter->dateTo !== '')
        {
            $out['date_to'] = $filter->dateTo;
        }
        if (in_array('customer_id', $keys, true) && $filter->customerId !== null && $filter->customerId > 0)
        {
            $out['customer_id'] = (string) $filter->customerId;
        }
        if (in_array('product_id', $keys, true) && $filter->productId !== null && $filter->productId > 0)
        {
            $out['product_id'] = (string) $filter->productId;
        }
        if (in_array('category_id', $keys, true) && $filter->categoryId !== null && $filter->categoryId > 0)
        {
            $out['category_id'] = (string) $filter->categoryId;
        }
        if (in_array('order_status', $keys, true) && $filter->orderStatus !== null && $filter->orderStatus !== '')
        {
            $out['order_status'] = $filter->orderStatus;
        }
        if (in_array('type', $keys, true) && $filter->cashFlowType !== null && $filter->cashFlowType !== '')
        {
            $out['type'] = $filter->cashFlowType;
        }

        return $out;
    }

    /**
     * @param list<string> $keys
     * @return array<string, int|string|null>
     */
    public function filtersForView(ReportFilter $filter, array $keys): array
    {
        $params = $this->toQueryParams($filter, $keys);

        return [
            'date_from' => $params['date_from'] ?? '',
            'date_to' => $params['date_to'] ?? '',
            'customer_id' => isset($params['customer_id']) ? (int) $params['customer_id'] : null,
            'product_id' => isset($params['product_id']) ? (int) $params['product_id'] : null,
            'category_id' => isset($params['category_id']) ? (int) $params['category_id'] : null,
            'order_status' => $params['order_status'] ?? '',
            'type' => $params['type'] ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function optionalDate(array $query, string $key): ?string
    {
        if (!isset($query[$key]))
        {
            return null;
        }
        $raw = trim((string) $query[$key]);
        if ($raw === '')
        {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);

        return ($dt !== false && $dt->format('Y-m-d') === $raw) ? $raw : null;
    }
}
