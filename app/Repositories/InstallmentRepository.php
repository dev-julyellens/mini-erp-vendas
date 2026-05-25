<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Installment;
use PDO;

final class InstallmentRepository
{
    private PDO $db;

    private const SELECT_COLUMNS = 'i.id, i.order_id, i.installment_number, i.amount, i.due_date,
        i.paid_at, i.status, i.created_at, i.updated_at,
        o.customer_id, c.name AS customer_name, o.total_amount AS order_total';

    private const FROM_JOIN = 'FROM installments i
        INNER JOIN orders o ON o.id = i.order_id
        INNER JOIN customers c ON c.id = o.customer_id';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * @param list<array{installment_number: int, amount: string, due_date: string}> $rows
     */
    public function insertBatch(int $orderId, array $rows): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO installments (order_id, installment_number, amount, due_date, status)
             VALUES (:order_id, :number, :amount, :due_date, :status)'
        );

        foreach ($rows as $row)
        {
            $stmt->execute([
                'order_id' => $orderId,
                'number' => $row['installment_number'],
                'amount' => $row['amount'],
                'due_date' => $row['due_date'],
                'status' => 'pending',
            ]);
        }
    }

    public function findById(int $id): ?Installment
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' WHERE i.id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Installment::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?Installment
    {
        $sql = 'SELECT i.id, i.order_id, i.installment_number, i.amount, i.due_date,
                       i.paid_at, i.status, i.created_at, i.updated_at
                FROM installments i
                WHERE i.id = :id
                FOR UPDATE OF i';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Installment::fromArray($row) : null;
    }

    /**
     * @return list<Installment>
     */
    public function findByOrderId(int $orderId): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN
            . ' WHERE i.order_id = :order_id ORDER BY i.installment_number ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row)
        {
            $items[] = Installment::fromArray($row);
        }

        return $items;
    }

    public function countByOrderId(int $orderId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM installments WHERE order_id = :order_id');
        $stmt->execute(['order_id' => $orderId]);

        return (int) $stmt->fetchColumn();
    }

    public function countPaidByOrderId(int $orderId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM installments WHERE order_id = :order_id AND status = 'paid'"
        );
        $stmt->execute(['order_id' => $orderId]);

        return (int) $stmt->fetchColumn();
    }

    public function markPaid(int $id, string $paidAt): void
    {
        $stmt = $this->db->prepare(
            'UPDATE installments
             SET status = :status, paid_at = :paid_at, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);
    }

    public function cancelOpenByOrderId(int $orderId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE installments
             SET status = 'canceled', updated_at = CURRENT_TIMESTAMP
             WHERE order_id = :order_id
               AND status IN ('pending', 'overdue')"
        );
        $stmt->execute(['order_id' => $orderId]);
    }

    public function refreshOverdueStatuses(): void
    {
        $this->db->exec(
            "UPDATE installments
             SET status = 'overdue', updated_at = CURRENT_TIMESTAMP
             WHERE status = 'pending'
               AND due_date < CURRENT_DATE"
        );
    }

    /**
     * @return array{items: list<Installment>, total: int}
     */
    public function paginateFiltered(
        int $page,
        int $perPage,
        string $listType,
        ?int $customerId,
        ?string $dueFrom,
        ?string $dueTo
    ): array
    {
        $this->refreshOverdueStatuses();

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($listType === 'overdue')
        {
            $where[] = "i.status = 'overdue'";
            $where[] = "o.status <> 'canceled'";
        }
        elseif ($listType === 'open')
        {
            $where[] = "i.status IN ('pending', 'overdue')";
            $where[] = "o.status <> 'canceled'";
        }
        elseif ($listType === 'history')
        {
            $where[] = "i.status IN ('paid', 'canceled')";
        }

        if ($customerId !== null)
        {
            $where[] = 'o.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }
        if ($dueFrom !== null && $dueFrom !== '')
        {
            $where[] = 'i.due_date >= :due_from';
            $params['due_from'] = $dueFrom;
        }
        if ($dueTo !== null && $dueTo !== '')
        {
            $where[] = 'i.due_date <= :due_to';
            $params['due_to'] = $dueTo;
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));

        $countSql = 'SELECT COUNT(*) ' . self::FROM_JOIN . ' ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $orderBy = $listType === 'history'
            ? 'i.paid_at DESC NULLS LAST, i.due_date DESC, i.id DESC'
            : 'i.due_date ASC, i.installment_number ASC, i.id DESC';

        $listSql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' '
            . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset';
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
            $items[] = Installment::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function countByStatus(string $status): int
    {
        $this->refreshOverdueStatuses();
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM installments WHERE status = :status');
        $stmt->execute(['status' => $status]);

        return (int) $stmt->fetchColumn();
    }

    public function countOverdue(): int
    {
        $this->refreshOverdueStatuses();

        return (int) $this->db->query("SELECT COUNT(*) FROM installments WHERE status = 'overdue'")->fetchColumn();
    }

    public function countOpen(): int
    {
        $this->refreshOverdueStatuses();

        return (int) $this->db->query(
            "SELECT COUNT(*) FROM installments WHERE status IN ('pending', 'overdue')"
        )->fetchColumn();
    }
}
