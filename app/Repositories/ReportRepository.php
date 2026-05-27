<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\CashFlow;
use App\Models\ReportFilter;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class ReportRepository extends BaseRepository
{
    use CompanyScope;
    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{order_count: int, total_amount: string}}
     */
    public function salesByPeriod(ReportFilter $filter, int $perPage): array
    {
        $where = $this->orderWhereClause($filter);
        $params = $this->orderParams($filter);

        $countSql = 'SELECT COUNT(*) FROM (
                SELECT o.created_at::date AS period_date
                FROM orders o
                ' . $where . '
                GROUP BY o.created_at::date
            ) periods';
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filter->page - 1) * $perPage;
        $listSql = 'SELECT o.created_at::date AS period_date,
                           COUNT(o.id) AS order_count,
                           COALESCE(SUM(o.total_amount), 0) AS total_amount
                    FROM orders o
                    ' . $where . '
                    GROUP BY o.created_at::date
                    ORDER BY period_date DESC
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summarySql = 'SELECT COUNT(o.id) AS order_count,
                              COALESCE(SUM(o.total_amount), 0) AS total_amount
                       FROM orders o
                       ' . $where;
        $summaryStmt = $this->db->prepare($summarySql);
        $summaryStmt->execute($params);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'items' => $items,
            'total' => $total,
            'summary' => [
                'order_count' => (int) ($summaryRow['order_count'] ?? 0),
                'total_amount' => (string) ($summaryRow['total_amount'] ?? '0'),
            ],
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{order_count: int, total_amount: string}}
     */
    public function salesByCustomer(ReportFilter $filter, int $perPage): array
    {
        $where = $this->orderWhereClause($filter, 'o');
        $params = $this->orderParams($filter);

        $countSql = 'SELECT COUNT(*) FROM (
                SELECT c.id
                FROM customers c
                INNER JOIN orders o ON o.customer_id = c.id
                ' . $where . '
                GROUP BY c.id
            ) grouped';
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filter->page - 1) * $perPage;
        $listSql = 'SELECT c.id AS customer_id,
                           c.name AS customer_name,
                           COUNT(o.id) AS order_count,
                           COALESCE(SUM(o.total_amount), 0) AS total_amount
                    FROM customers c
                    INNER JOIN orders o ON o.customer_id = c.id
                    ' . $where . '
                    GROUP BY c.id, c.name
                    ORDER BY total_amount DESC, c.name ASC
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = $this->orderSummary($where, $params);

        return ['items' => $items, 'total' => $total, 'summary' => $summary];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{quantity_sold: int, total_amount: string}}
     */
    public function salesByProduct(ReportFilter $filter, int $perPage): array
    {
        return $this->aggregateProductSales($filter, $perPage, 'revenue');
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{quantity_sold: int, total_amount: string}}
     */
    public function topSellingProducts(ReportFilter $filter, int $perPage): array
    {
        return $this->aggregateProductSales($filter, $perPage, 'quantity');
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function lowStock(ReportFilter $filter, int $perPage): array
    {
        $where = [
            'p.company_id = :company_id',
            'p.type = \'product\'',
            'p.stock <= p.min_stock',
        ];
        $params = $this->companyParams();

        if ($filter->categoryId !== null && $filter->categoryId > 0)
        {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filter->categoryId;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM products p ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filter->page - 1) * $perPage;
        $listSql = 'SELECT p.id AS product_id,
                           p.name AS product_name,
                           p.sku,
                           p.stock,
                           p.min_stock,
                           c.name AS category_name
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    ' . $whereSql . '
                    ORDER BY (p.min_stock - p.stock) DESC, p.name ASC
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{entrada: string, saida: string, saldo: string}}
     */
    public function cashFlow(ReportFilter $filter, int $perPage): array
    {
        $where = ['cf.company_id = :company_id'];
        $params = $this->companyParams();

        if (
            $filter->cashFlowType !== null && $filter->cashFlowType !== ''
            && in_array($filter->cashFlowType, CashFlow::TYPES, true)
        )
        {
            $where[] = 'cf.type = :type';
            $params['type'] = $filter->cashFlowType;
        }
        if ($filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $where[] = 'cf.occurred_at::date >= :date_from';
            $params['date_from'] = $filter->dateFrom;
        }
        if ($filter->dateTo !== null && $filter->dateTo !== '')
        {
            $where[] = 'cf.occurred_at::date <= :date_to';
            $params['date_to'] = $filter->dateTo;
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSql = 'SELECT COUNT(*) FROM cash_flow cf ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filter->page - 1) * $perPage;
        $listSql = 'SELECT cf.id,
                           cf.type,
                           cf.amount,
                           cf.payment_method,
                           cf.description,
                           cf.occurred_at
                    FROM cash_flow cf
                    ' . $whereSql . '
                    ORDER BY cf.occurred_at DESC, cf.id DESC
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summarySql = "SELECT
                COALESCE(SUM(CASE WHEN cf.type = 'entrada' THEN cf.amount ELSE 0 END), 0) AS entrada,
                COALESCE(SUM(CASE WHEN cf.type = 'saida' THEN cf.amount ELSE 0 END), 0) AS saida
            FROM cash_flow cf
            " . $whereSql;
        $summaryStmt = $this->db->prepare($summarySql);
        $summaryStmt->execute($params);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $entrada = (string) ($summaryRow['entrada'] ?? '0');
        $saida = (string) ($summaryRow['saida'] ?? '0');
        $saldo = function_exists('bcsub') ? bcsub($entrada, $saida, 2) : number_format((float) $entrada - (float) $saida, 2, '.', '');

        return [
            'items' => $items,
            'total' => $total,
            'summary' => [
                'entrada' => $entrada,
                'saida' => $saida,
                'saldo' => $saldo,
            ],
        ];
    }

    /**
     * Dados completos para exportação (sem paginação).
     *
     * @return list<array<string, mixed>>
     */
    public function salesByPeriodAll(ReportFilter $filter, int $maxRows = 5000): array
    {
        $where = $this->orderWhereClause($filter);
        $params = $this->orderParams($filter);
        $sql = 'SELECT o.created_at::date AS period_date,
                       COUNT(o.id) AS order_count,
                       COALESCE(SUM(o.total_amount), 0) AS total_amount
                FROM orders o
                ' . $where . '
                GROUP BY o.created_at::date
                ORDER BY period_date DESC
                LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $maxRows, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function salesByCustomerAll(ReportFilter $filter, int $maxRows = 5000): array
    {
        $where = $this->orderWhereClause($filter, 'o');
        $params = $this->orderParams($filter);
        $sql = 'SELECT c.id AS customer_id,
                       c.name AS customer_name,
                       COUNT(o.id) AS order_count,
                       COALESCE(SUM(o.total_amount), 0) AS total_amount
                FROM customers c
                INNER JOIN orders o ON o.customer_id = c.id
                ' . $where . '
                GROUP BY c.id, c.name
                ORDER BY total_amount DESC, c.name ASC
                LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $maxRows, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function salesByProductAll(ReportFilter $filter, int $maxRows = 5000, bool $orderByQuantity = false): array
    {
        $where = $this->orderItemWhereClause($filter);
        $params = $this->orderItemParams($filter);
        $order = $orderByQuantity
            ? 'quantity_sold DESC, total_amount DESC'
            : 'total_amount DESC, quantity_sold DESC';
        $sql = 'SELECT p.id AS product_id,
                       p.name AS product_name,
                       p.sku,
                       p.type AS product_type,
                       SUM(oi.quantity) AS quantity_sold,
                       COALESCE(SUM(oi.subtotal), 0) AS total_amount
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                INNER JOIN products p ON p.id = oi.product_id
                ' . $where . '
                GROUP BY p.id, p.name, p.sku, p.type
                ORDER BY ' . $order . '
                LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $maxRows, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lowStockAll(ReportFilter $filter, int $maxRows = 5000): array
    {
        $filterCopy = new ReportFilter(
            $filter->dateFrom,
            $filter->dateTo,
            $filter->customerId,
            $filter->productId,
            $filter->categoryId,
            $filter->orderStatus,
            $filter->cashFlowType,
            1
        );
        $result = $this->lowStock($filterCopy, $maxRows);

        return $result['items'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cashFlowAll(ReportFilter $filter, int $maxRows = 5000): array
    {
        $filterCopy = new ReportFilter(
            $filter->dateFrom,
            $filter->dateTo,
            $filter->customerId,
            $filter->productId,
            $filter->categoryId,
            $filter->orderStatus,
            $filter->cashFlowType,
            1
        );
        $result = $this->cashFlow($filterCopy, $maxRows);

        return $result['items'];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, summary: array{quantity_sold: int, total_amount: string}}
     */
    private function aggregateProductSales(ReportFilter $filter, int $perPage, string $sortBy): array
    {
        $where = $this->orderItemWhereClause($filter);
        $params = $this->orderItemParams($filter);
        $orderSql = $sortBy === 'quantity'
            ? 'quantity_sold DESC, total_amount DESC, p.name ASC'
            : 'total_amount DESC, p.name ASC';

        $countSql = 'SELECT COUNT(*) FROM (
                SELECT p.id
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                INNER JOIN products p ON p.id = oi.product_id
                ' . $where . '
                GROUP BY p.id
            ) grouped';
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filter->page - 1) * $perPage;
        $listSql = 'SELECT p.id AS product_id,
                           p.name AS product_name,
                           p.sku,
                           p.type AS product_type,
                           SUM(oi.quantity) AS quantity_sold,
                           COALESCE(SUM(oi.subtotal), 0) AS total_amount
                    FROM order_items oi
                    INNER JOIN orders o ON o.id = oi.order_id
                    INNER JOIN products p ON p.id = oi.product_id
                    ' . $where . '
                    GROUP BY p.id, p.name, p.sku, p.type
                    ORDER BY ' . $orderSql . '
                    LIMIT :limit OFFSET :offset';
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summarySql = 'SELECT COALESCE(SUM(oi.quantity), 0) AS quantity_sold,
                              COALESCE(SUM(oi.subtotal), 0) AS total_amount
                       FROM order_items oi
                       INNER JOIN orders o ON o.id = oi.order_id
                       INNER JOIN products p ON p.id = oi.product_id
                       ' . $where;
        $summaryStmt = $this->db->prepare($summarySql);
        $summaryStmt->execute($params);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'items' => $items,
            'total' => $total,
            'summary' => [
                'quantity_sold' => (int) ($summaryRow['quantity_sold'] ?? 0),
                'total_amount' => (string) ($summaryRow['total_amount'] ?? '0'),
            ],
        ];
    }

    private function orderWhereClause(ReportFilter $filter, string $alias = 'o'): string
    {
        $where = [
            $alias . '.status = :order_status',
            $alias . '.company_id = :company_id',
        ];
        if ($filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $where[] = $alias . '.created_at::date >= :date_from';
        }
        if ($filter->dateTo !== null && $filter->dateTo !== '')
        {
            $where[] = $alias . '.created_at::date <= :date_to';
        }
        if ($filter->customerId !== null && $filter->customerId > 0)
        {
            $where[] = $alias . '.customer_id = :customer_id';
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderParams(ReportFilter $filter): array
    {
        $params = [
            'order_status' => $filter->effectiveOrderStatus(),
            'company_id' => $this->companyId(),
        ];
        if ($filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $params['date_from'] = $filter->dateFrom;
        }
        if ($filter->dateTo !== null && $filter->dateTo !== '')
        {
            $params['date_to'] = $filter->dateTo;
        }
        if ($filter->customerId !== null && $filter->customerId > 0)
        {
            $params['customer_id'] = $filter->customerId;
        }

        return $params;
    }

    private function orderItemWhereClause(ReportFilter $filter): string
    {
        $where = [
            'o.status = :order_status',
            'o.company_id = :company_id',
            'p.company_id = :company_id',
        ];
        if ($filter->dateFrom !== null && $filter->dateFrom !== '')
        {
            $where[] = 'o.created_at::date >= :date_from';
        }
        if ($filter->dateTo !== null && $filter->dateTo !== '')
        {
            $where[] = 'o.created_at::date <= :date_to';
        }
        if ($filter->customerId !== null && $filter->customerId > 0)
        {
            $where[] = 'o.customer_id = :customer_id';
        }
        if ($filter->productId !== null && $filter->productId > 0)
        {
            $where[] = 'oi.product_id = :product_id';
        }
        if ($filter->categoryId !== null && $filter->categoryId > 0)
        {
            $where[] = 'p.category_id = :category_id';
        }

        return 'WHERE ' . implode(' AND ', $where);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderItemParams(ReportFilter $filter): array
    {
        $params = $this->orderParams($filter);
        if ($filter->productId !== null && $filter->productId > 0)
        {
            $params['product_id'] = $filter->productId;
        }
        if ($filter->categoryId !== null && $filter->categoryId > 0)
        {
            $params['category_id'] = $filter->categoryId;
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     * @return array{order_count: int, total_amount: string}
     */
    private function orderSummary(string $where, array $params): array
    {
        $summarySql = 'SELECT COUNT(o.id) AS order_count,
                              COALESCE(SUM(o.total_amount), 0) AS total_amount
                       FROM orders o
                       ' . $where;
        $summaryStmt = $this->db->prepare($summarySql);
        $summaryStmt->execute($params);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'order_count' => (int) ($summaryRow['order_count'] ?? 0),
            'total_amount' => (string) ($summaryRow['total_amount'] ?? '0'),
        ];
    }
}
