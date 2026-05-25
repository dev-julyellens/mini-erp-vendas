<?php

declare(strict_types=1);

namespace App\Models;

final class StockMovement
{
    public int $id;
    public int $product_id;
    public string $type;
    public int $quantity;
    public ?string $reference_type;
    public ?int $reference_id;
    public ?string $notes;
    public ?int $created_by;
    public string $created_at;
    public ?string $product_name;
    public ?string $user_name;
    public ?string $user_email;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->product_id = (int) $row['product_id'];
        $m->type = (string) $row['type'];
        $m->quantity = (int) $row['quantity'];
        $m->reference_type = isset($row['reference_type']) && $row['reference_type'] !== null
            ? (string) $row['reference_type']
            : null;
        $m->reference_id = isset($row['reference_id']) && $row['reference_id'] !== null
            ? (int) $row['reference_id']
            : null;
        $m->notes = isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null;
        $m->created_by = isset($row['created_by']) && $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->product_name = isset($row['product_name']) && $row['product_name'] !== null
            ? (string) $row['product_name']
            : null;
        $m->user_name = isset($row['user_name']) && $row['user_name'] !== null
            ? (string) $row['user_name']
            : null;
        $m->user_email = isset($row['user_email']) && $row['user_email'] !== null
            ? (string) $row['user_email']
            : null;

        return $m;
    }
}
