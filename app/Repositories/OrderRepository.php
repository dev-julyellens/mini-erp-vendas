<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Order;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class OrderRepository
{
    use CompanyScope;

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Order
    {
        $sql = 'SELECT o.*, c.name AS customer_name, u.name AS canceled_by_name
                FROM orders o
                INNER JOIN customers c ON c.id = o.customer_id AND c.company_id = o.company_id
                LEFT JOIN users u ON u.id = o.canceled_by
                WHERE o.id = :id AND o.company_id = :company_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Order::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?Order
    {
        $sql = 'SELECT o.*, c.name AS customer_name
                FROM orders o
                INNER JOIN customers c ON c.id = o.customer_id AND c.company_id = o.company_id
                WHERE o.id = :id AND o.company_id = :company_id
                FOR UPDATE OF o';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Order::fromArray($row) : null;
    }

    public function insert(int $customerId, string $totalAmount, string $status = 'paid'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (customer_id, total_amount, status, company_id)
             VALUES (:customer_id, :total, :status, :company_id)
             RETURNING id'
        );
        $stmt->execute([
            'customer_id' => $customerId,
            'total' => $totalAmount,
            'status' => $status,
            'company_id' => $this->companyId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function markCanceled(int $orderId, int $canceledBy): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET status = :status,
                 canceled_by = :canceled_by,
                 canceled_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND company_id = :company_id
               AND status IN (\'pending\', \'paid\')'
        );
        $stmt->execute([
            'id' => $orderId,
            'status' => 'canceled',
            'canceled_by' => $canceledBy,
            'company_id' => $this->companyId(),
        ]);

        if ($stmt->rowCount() === 0)
        {
            throw new \RuntimeException('Order status could not be updated to canceled.');
        }
    }

    public function updateTotal(int $orderId, string $totalAmount): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders
             SET total_amount = :total
             WHERE id = :id
               AND company_id = :company_id
               AND status NOT IN (\'canceled\', \'refunded\')'
        );
        $stmt->execute([
            'id' => $orderId,
            'total' => $totalAmount,
            'company_id' => $this->companyId(),
        ]);
    }

    /**
     * @return array{items: list<Order>, total: int}
     */
    public function paginateFiltered(int $page, int $perPage, ?int $customerId, ?string $dateFrom, ?string $dateTo): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['o.company_id = :company_id'];
        $params = $this->companyParams();
        if ($customerId !== null)
        {
            $where[] = 'o.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }
        if ($dateFrom !== null && $dateFrom !== '')
        {
            $where[] = 'o.created_at::date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '')
        {
            $where[] = 'o.created_at::date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM orders o $whereSql";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listSql = "SELECT o.*, c.name AS customer_name
                    FROM orders o
                    INNER JOIN customers c ON c.id = o.customer_id AND c.company_id = o.company_id
                    $whereSql
                    ORDER BY o.created_at DESC
                    LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($listSql);
        foreach ($params as $k => $v)
        {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row)
        {
            $items[] = Order::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function countAll(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE company_id = :company_id');
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }

    public function countCurrentMonth(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM orders
             WHERE company_id = :company_id
               AND created_at >= date_trunc(\'month\', CURRENT_TIMESTAMP)'
        );
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }
}
