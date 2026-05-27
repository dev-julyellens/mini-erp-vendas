<?php

declare(strict_types=1);

namespace App\Models;

final class QuoteItem
{
    public int $id;
    public int $quote_id;
    public int $product_id;
    public int $quantity;
    public string $unit_price;
    public string $subtotal;
    public ?string $product_name;
    public ?string $product_sku;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->quote_id = (int) $row['quote_id'];
        $m->product_id = (int) $row['product_id'];
        $m->quantity = (int) $row['quantity'];
        $m->unit_price = (string) $row['unit_price'];
        $m->subtotal = (string) $row['subtotal'];
        $m->product_name = isset($row['product_name']) ? (string) $row['product_name'] : null;
        $m->product_sku = isset($row['product_sku']) ? (string) $row['product_sku'] : null;

        return $m;
    }
}
