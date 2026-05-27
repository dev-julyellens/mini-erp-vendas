<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class DashboardRepository extends BaseRepository
{
    use CompanyScope;

    /**
     * @return array{
     *   today_amount: string,
     *   today_orders: int,
     *   month_amount: string,
     *   month_orders: int
     * }
     */
    public function salesSnapshot(): array
    {
        $sql = "SELECT
                    COALESCE(SUM(o.total_amount) FILTER (WHERE o.created_at::date = CURRENT_DATE), 0) AS today_amount,
                    COUNT(*) FILTER (WHERE o.created_at::date = CURRENT_DATE) AS today_orders,
                    COALESCE(SUM(o.total_amount) FILTER (
                        WHERE o.created_at::date >= date_trunc('month', CURRENT_DATE)::date
                          AND o.created_at::date < (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date
                    ), 0) AS month_amount,
                    COUNT(*) FILTER (
                        WHERE o.created_at::date >= date_trunc('month', CURRENT_DATE)::date
                          AND o.created_at::date < (date_trunc('month', CURRENT_DATE) + INTERVAL '1 month')::date
                    ) AS month_orders
                FROM orders o
                WHERE o.status = 'paid' AND o.company_id = :company_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyParams());
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'today_amount' => (string) ($row['today_amount'] ?? '0'),
            'today_orders' => (int) ($row['today_orders'] ?? 0),
            'month_amount' => (string) ($row['month_amount'] ?? '0'),
            'month_orders' => (int) ($row['month_orders'] ?? 0),
        ];
    }

    /**
     * @return list<array{sale_date: string, order_count: int, total_amount: string}>
     */
    public function dailyRevenueBetween(string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT o.created_at::date AS sale_date,
                       COUNT(o.id) AS order_count,
                       COALESCE(SUM(o.total_amount), 0) AS total_amount
                FROM orders o
                WHERE o.status = :status
                  AND o.company_id = :company_id
                  AND o.created_at::date >= :date_from
                  AND o.created_at::date <= :date_to
                GROUP BY o.created_at::date
                ORDER BY sale_date ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => 'paid',
            'company_id' => $this->companyId(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $items[] = [
                'sale_date' => (string) $row['sale_date'],
                'order_count' => (int) $row['order_count'],
                'total_amount' => (string) $row['total_amount'],
            ];
        }

        return $items;
    }

    /**
     * @return list<array{year_month: string, order_count: int, total_amount: string}>
     */
    public function monthlySalesBetween(string $monthFrom, string $monthTo): array
    {
        $sql = "SELECT to_char(o.created_at, 'YYYY-MM') AS year_month,
                       COUNT(o.id) AS order_count,
                       COALESCE(SUM(o.total_amount), 0) AS total_amount
                FROM orders o
                WHERE o.status = :status
                  AND o.company_id = :company_id
                  AND to_char(o.created_at, 'YYYY-MM') >= :month_from
                  AND to_char(o.created_at, 'YYYY-MM') <= :month_to
                GROUP BY to_char(o.created_at, 'YYYY-MM')
                ORDER BY year_month ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => 'paid',
            'company_id' => $this->companyId(),
            'month_from' => $monthFrom,
            'month_to' => $monthTo,
        ]);
        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row)
        {
            $items[] = [
                'year_month' => (string) $row['year_month'],
                'order_count' => (int) $row['order_count'],
                'total_amount' => (string) $row['total_amount'],
            ];
        }

        return $items;
    }

    public function countLowStockProducts(): int
    {
        $sql = "SELECT COUNT(*)
                FROM products p
                WHERE p.company_id = :company_id
                  AND p.type = 'product'
                  AND p.stock < p.min_stock";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }

    public function sumOverdueRemaining(): string
    {
        $sql = "SELECT COALESCE(SUM(ar.amount - COALESCE(pay.paid_total, 0)), 0)
                FROM accounts_receivable ar
                INNER JOIN orders o ON o.id = ar.order_id AND o.company_id = :company_id
                LEFT JOIN (
                    SELECT accounts_receivable_id, SUM(amount) AS paid_total
                    FROM payments
                    GROUP BY accounts_receivable_id
                ) pay ON pay.accounts_receivable_id = ar.id
                WHERE ar.status IN ('pending', 'partial')
                  AND ar.due_date < CURRENT_DATE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyParams());

        return (string) $stmt->fetchColumn();
    }
}
