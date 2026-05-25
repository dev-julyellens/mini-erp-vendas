<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashFlowRepository;

final class CashFlowService
{
    private CashFlowRepository $cashFlow;

    public function __construct(?CashFlowRepository $cashFlow = null)
    {
        $this->cashFlow = $cashFlow ?? new CashFlowRepository();
    }

    /**
     * @return array{items: list<\App\Models\CashFlow>, total: int}
     */
    public function search(int $page, int $perPage, ?string $type, ?string $dateFrom, ?string $dateTo): array
    {
        if ($type !== null && $type !== '' && !in_array($type, \App\Models\CashFlow::TYPES, true))
        {
            $type = null;
        }

        return $this->cashFlow->paginateFiltered($page, $perPage, $type, $dateFrom, $dateTo);
    }

    /**
     * @param array<string, string> $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $out = [];
        foreach (['type', 'date_from', 'date_to'] as $key)
        {
            if (!isset($filters[$key]))
            {
                continue;
            }
            $val = $filters[$key];
            if ($val === '' || $val === null)
            {
                continue;
            }
            $out[$key] = (string) $val;
        }

        return $out;
    }
}
