<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InventoryCountLine;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class InventoryCountLineRepository extends BaseRepository
{
    use CompanyScope;

    /**
     * @param list<array{product_id: int, system_qty: int, counted_qty: int|null}> $lines
     */
    public function insertBatch(int $inventoryCountId, array $lines): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO inventory_count_lines (inventory_count_id, product_id, system_qty, counted_qty)
             VALUES (:inventory_count_id, :product_id, :system_qty, :counted_qty)'
        );

        foreach ($lines as $line)
        {
            $stmt->execute([
                'inventory_count_id' => $inventoryCountId,
                'product_id' => (int) $line['product_id'],
                'system_qty' => (int) $line['system_qty'],
                'counted_qty' => $line['counted_qty'],
            ]);
        }
    }

    public function updateCountedQty(int $lineId, int $countedQty): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE inventory_count_lines l
             SET counted_qty = :counted_qty
             FROM inventory_counts ic
             WHERE l.id = :id
               AND l.inventory_count_id = ic.id
               AND ic.company_id = :company_id
               AND ic.status = \'open\''
        );
        $stmt->execute([
            'id' => $lineId,
            'counted_qty' => $countedQty,
            'company_id' => $this->companyId(),
        ]);

        return $stmt->rowCount() > 0;
    }

    public function findById(int $lineId): ?InventoryCountLine
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, p.name AS product_name, p.sku AS product_sku
             FROM inventory_count_lines l
             INNER JOIN inventory_counts ic ON ic.id = l.inventory_count_id
             INNER JOIN products p ON p.id = l.product_id AND p.company_id = ic.company_id
             WHERE l.id = :id AND ic.company_id = :company_id'
        );
        $stmt->execute(['id' => $lineId, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? InventoryCountLine::fromArray($row) : null;
    }

    /**
     * @return list<InventoryCountLine>
     */
    public function findByInventoryCountId(int $inventoryCountId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, p.name AS product_name, p.sku AS product_sku
             FROM inventory_count_lines l
             INNER JOIN inventory_counts ic ON ic.id = l.inventory_count_id
             INNER JOIN products p ON p.id = l.product_id AND p.company_id = ic.company_id
             WHERE l.inventory_count_id = :inventory_count_id
               AND ic.company_id = :company_id
             ORDER BY p.name'
        );
        $stmt->execute([
            'inventory_count_id' => $inventoryCountId,
            'company_id' => $this->companyId(),
        ]);
        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = InventoryCountLine::fromArray($row);
        }

        return $items;
    }

    /**
     * @return list<InventoryCountLine>
     */
    public function findWithVariance(int $inventoryCountId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.*, p.name AS product_name, p.sku AS product_sku
             FROM inventory_count_lines l
             INNER JOIN products p ON p.id = l.product_id
             WHERE l.inventory_count_id = :inventory_count_id
               AND l.counted_qty IS NOT NULL
               AND l.counted_qty <> l.system_qty
             ORDER BY p.name'
        );
        $stmt->execute(['inventory_count_id' => $inventoryCountId]);
        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = InventoryCountLine::fromArray($row);
        }

        return $items;
    }
}
