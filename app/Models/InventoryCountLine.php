<?php

declare(strict_types=1);

namespace App\Models;

final class InventoryCountLine
{
    public int $id;
    public int $inventory_count_id;
    public int $product_id;
    public int $system_qty;
    public ?int $counted_qty;
    public ?string $product_name;
    public ?string $product_sku;

    public function variance(): ?int
    {
        if ($this->counted_qty === null)
        {
            return null;
        }

        return $this->counted_qty - $this->system_qty;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->inventory_count_id = (int) $row['inventory_count_id'];
        $m->product_id = (int) $row['product_id'];
        $m->system_qty = (int) $row['system_qty'];
        $m->counted_qty = isset($row['counted_qty']) && $row['counted_qty'] !== null
            ? (int) $row['counted_qty']
            : null;
        $m->product_name = isset($row['product_name']) ? (string) $row['product_name'] : null;
        $m->product_sku = isset($row['product_sku']) ? (string) $row['product_sku'] : null;

        return $m;
    }
}
