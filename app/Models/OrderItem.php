<?php

declare(strict_types=1);

namespace App\Models;

final class OrderItem
{
    public int $id;
    public int $order_id;
    public int $product_id;
    public int $quantity;
    public string $unit_price;
    public string $subtotal;
    public ?string $product_name;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->order_id = (int) $row['order_id'];
        $m->product_id = (int) $row['product_id'];
        $m->quantity = (int) $row['quantity'];
        $m->unit_price = (string) $row['unit_price'];
        $m->subtotal = (string) $row['subtotal'];
        $m->product_name = isset($row['product_name']) ? (string) $row['product_name'] : null;
        return $m;
    }
}
