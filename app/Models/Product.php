<?php

declare(strict_types=1);

namespace App\Models;

final class Product
{
    public int $id;
    public string $name;
    public ?string $description;
    public string $price;
    public int $stock;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->name = (string) $row['name'];
        $m->description = isset($row['description']) && $row['description'] !== null
            ? (string) $row['description']
            : null;
        $m->price = (string) $row['price'];
        $m->stock = (int) $row['stock'];
        return $m;
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->stock < $threshold;
    }
}
