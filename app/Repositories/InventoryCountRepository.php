<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InventoryCount;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class InventoryCountRepository extends BaseRepository
{
    use CompanyScope;

    public function findById(int $id): ?InventoryCount
    {
        $stmt = $this->db->prepare(
            'SELECT ic.*,
                    cu.name AS created_by_name,
                    fu.name AS finalized_by_name
             FROM inventory_counts ic
             LEFT JOIN users cu ON cu.id = ic.created_by
             LEFT JOIN users fu ON fu.id = ic.finalized_by
             WHERE ic.id = :id AND ic.company_id = :company_id'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? InventoryCount::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?InventoryCount
    {
        $stmt = $this->db->prepare(
            'SELECT ic.*
             FROM inventory_counts ic
             WHERE ic.id = :id AND ic.company_id = :company_id
             FOR UPDATE'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? InventoryCount::fromArray($row) : null;
    }

    public function insert(?string $notes, ?int $createdBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO inventory_counts (company_id, status, notes, created_by)
             VALUES (:company_id, \'open\', :notes, :created_by)
             RETURNING id'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function markFinalized(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE inventory_counts
             SET status = \'finalized\',
                 finalized_by = :user_id,
                 finalized_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id AND status = \'open\''
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'company_id' => $this->companyId(),
        ]);
    }

    public function countOpen(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM inventory_counts
             WHERE company_id = :company_id AND status = \'open\''
        );
        $stmt->execute(['company_id' => $this->companyId()]);

        return (int) $stmt->fetchColumn();
    }

    public function markCanceled(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE inventory_counts
             SET status = \'canceled\'
             WHERE id = :id AND company_id = :company_id AND status = \'open\''
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
    }

    /**
     * @return array{items: list<InventoryCount>, total: int}
     */
    public function paginate(int $page, int $perPage, ?string $status): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['ic.company_id = :company_id'];
        $params = $this->companyParams();

        if ($status !== null && $status !== '')
        {
            $where[] = 'ic.status = :status';
            $params['status'] = $status;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM inventory_counts ic $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->db->prepare(
            "SELECT ic.*, cu.name AS created_by_name, fu.name AS finalized_by_name
             FROM inventory_counts ic
             LEFT JOIN users cu ON cu.id = ic.created_by
             LEFT JOIN users fu ON fu.id = ic.finalized_by
             $whereSql
             ORDER BY ic.created_at DESC
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
            $items[] = InventoryCount::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }
}
