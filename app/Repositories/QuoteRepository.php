<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Quote;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class QuoteRepository extends BaseRepository
{
    use CompanyScope;

    public function findById(int $id): ?Quote
    {
        $stmt = $this->db->prepare(
            'SELECT q.*, c.name AS customer_name, u.name AS created_by_name
             FROM quotes q
             INNER JOIN customers c ON c.id = q.customer_id AND c.company_id = q.company_id
             LEFT JOIN users u ON u.id = q.created_by
             WHERE q.id = :id AND q.company_id = :company_id'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Quote::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?Quote
    {
        $stmt = $this->db->prepare(
            'SELECT q.*, c.name AS customer_name
             FROM quotes q
             INNER JOIN customers c ON c.id = q.customer_id AND c.company_id = q.company_id
             WHERE q.id = :id AND q.company_id = :company_id
             FOR UPDATE OF q'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Quote::fromArray($row) : null;
    }

    public function insert(int $customerId, string $totalAmount, string $status, ?string $validUntil, ?string $notes, ?int $createdBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quotes (company_id, customer_id, total_amount, status, valid_until, notes, created_by)
             VALUES (:company_id, :customer_id, :total, :status, :valid_until, :notes, :created_by)
             RETURNING id'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'customer_id' => $customerId,
            'total' => $totalAmount,
            'status' => $status,
            'valid_until' => $validUntil,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function updateTotal(int $quoteId, string $totalAmount): void
    {
        $stmt = $this->db->prepare(
            'UPDATE quotes
             SET total_amount = :total, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $quoteId,
            'total' => $totalAmount,
            'company_id' => $this->companyId(),
        ]);
    }

    public function markStatus(int $quoteId, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE quotes
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $quoteId,
            'status' => $status,
            'company_id' => $this->companyId(),
        ]);
    }

    public function markSentFromDraft(int $quoteId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quotes
             SET status = \'sent\', updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id AND status = \'draft\''
        );
        $stmt->execute([
            'id' => $quoteId,
            'company_id' => $this->companyId(),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markConverted(int $quoteId, int $orderId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE quotes
             SET status = \'converted\',
                 converted_order_id = :order_id,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id
               AND status IN (\'draft\', \'sent\', \'approved\')'
        );
        $stmt->execute([
            'id' => $quoteId,
            'order_id' => $orderId,
            'company_id' => $this->companyId(),
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array{items: list<Quote>, total: int}
     */
    public function paginateFiltered(int $page, int $perPage, ?int $customerId, ?string $status): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['q.company_id = :company_id'];
        $params = $this->companyParams();

        if ($customerId !== null)
        {
            $where[] = 'q.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }
        if ($status !== null && $status !== '')
        {
            $where[] = 'q.status = :status';
            $params['status'] = $status;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM quotes q $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->db->prepare(
            "SELECT q.*, c.name AS customer_name, u.name AS created_by_name
             FROM quotes q
             INNER JOIN customers c ON c.id = q.customer_id AND c.company_id = q.company_id
             LEFT JOIN users u ON u.id = q.created_by
             $whereSql
             ORDER BY q.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v)
        {
            $listStmt->bindValue(':' . $k, $v);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        $items = [];
        foreach ($listStmt->fetchAll() as $row)
        {
            $items[] = Quote::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }
}
