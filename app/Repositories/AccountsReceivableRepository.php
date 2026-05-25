<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AccountsReceivable;
use PDO;

final class AccountsReceivableRepository
{
    private PDO $db;

    private const SELECT_COLUMNS = 'ar.id, ar.order_id, ar.customer_id, ar.amount, ar.due_date, ar.status,
        ar.created_at, ar.updated_at, c.name AS customer_name,
        o.total_amount AS order_total,
        COALESCE(pay.paid_total, 0) AS paid_total,
        (ar.amount - COALESCE(pay.paid_total, 0)) AS remaining_amount,
        EXISTS (SELECT 1 FROM installments inst WHERE inst.order_id = ar.order_id) AS has_installments';

    private const FROM_JOIN = 'FROM accounts_receivable ar
        INNER JOIN customers c ON c.id = ar.customer_id
        INNER JOIN orders o ON o.id = ar.order_id
        LEFT JOIN (
            SELECT accounts_receivable_id, SUM(amount) AS paid_total
            FROM payments
            GROUP BY accounts_receivable_id
        ) pay ON pay.accounts_receivable_id = ar.id';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function insert(int $orderId, int $customerId, string $amount, string $dueDate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO accounts_receivable (order_id, customer_id, amount, due_date, status)
             VALUES (:order_id, :customer_id, :amount, :due_date, :status)
             RETURNING id'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?AccountsReceivable
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' WHERE ar.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? AccountsReceivable::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?AccountsReceivable
    {
        $sql = 'SELECT ar.id, ar.order_id, ar.customer_id, ar.amount, ar.due_date, ar.status,
                       ar.created_at, ar.updated_at
                FROM accounts_receivable ar
                WHERE ar.id = :id
                FOR UPDATE OF ar';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? AccountsReceivable::fromArray($row) : null;
    }

    public function findByOrderId(int $orderId): ?AccountsReceivable
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' WHERE ar.order_id = :order_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        $row = $stmt->fetch();

        return $row ? AccountsReceivable::fromArray($row) : null;
    }

    public function findByOrderIdForUpdate(int $orderId): ?AccountsReceivable
    {
        $sql = 'SELECT ar.id, ar.order_id, ar.customer_id, ar.amount, ar.due_date, ar.status,
                       ar.created_at, ar.updated_at
                FROM accounts_receivable ar
                WHERE ar.order_id = :order_id
                FOR UPDATE OF ar';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        $row = $stmt->fetch();

        return $row ? AccountsReceivable::fromArray($row) : null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accounts_receivable
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function cancelOpenByOrderId(int $orderId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE accounts_receivable
             SET status = :canceled, updated_at = CURRENT_TIMESTAMP
             WHERE order_id = :order_id
               AND status IN (\'pending\', \'partial\')'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'canceled' => 'canceled',
        ]);
    }

    /**
     * @return array{items: list<AccountsReceivable>, total: int}
     */
    public function paginateFiltered(
        int $page,
        int $perPage,
        ?string $status,
        ?int $customerId,
        ?string $dueFrom,
        ?string $dueTo,
        bool $overdueOnly
    ): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($status !== null && $status !== '')
        {
            $where[] = 'ar.status = :status';
            $params['status'] = $status;
        }
        if ($customerId !== null)
        {
            $where[] = 'ar.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }
        if ($dueFrom !== null && $dueFrom !== '')
        {
            $where[] = 'ar.due_date >= :due_from';
            $params['due_from'] = $dueFrom;
        }
        if ($dueTo !== null && $dueTo !== '')
        {
            $where[] = 'ar.due_date <= :due_to';
            $params['due_to'] = $dueTo;
        }
        if ($overdueOnly)
        {
            $where[] = "ar.status IN ('pending', 'partial') AND ar.due_date < CURRENT_DATE";
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSql = 'SELECT COUNT(*) ' . self::FROM_JOIN . ' ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listSql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' '
            . $whereSql . ' ORDER BY ar.due_date ASC, ar.id DESC LIMIT :limit OFFSET :offset';
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
            $items[] = AccountsReceivable::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function sumRemainingOpen(): string
    {
        $sql = 'SELECT COALESCE(SUM(ar.amount - COALESCE(pay.paid_total, 0)), 0)
                FROM accounts_receivable ar
                LEFT JOIN (
                    SELECT accounts_receivable_id, SUM(amount) AS paid_total
                    FROM payments
                    GROUP BY accounts_receivable_id
                ) pay ON pay.accounts_receivable_id = ar.id
                WHERE ar.status IN (\'pending\', \'partial\')';

        return (string) $this->db->query($sql)->fetchColumn();
    }

    public function countOverdueOpen(): int
    {
        $sql = "SELECT COUNT(*)
                FROM accounts_receivable ar
                WHERE ar.status IN ('pending', 'partial')
                  AND ar.due_date < CURRENT_DATE";

        return (int) $this->db->query($sql)->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM accounts_receivable WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }
}
