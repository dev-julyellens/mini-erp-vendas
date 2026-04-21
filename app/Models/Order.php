<?php

declare(strict_types=1);

namespace App\Models;

final class Order
{
    public int $id;
    public int $customer_id;
    public string $total_amount;
    public string $created_at;
    public ?string $customer_name;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->customer_id = (int) $row['customer_id'];
        $m->total_amount = (string) $row['total_amount'];
        $m->created_at = (string) $row['created_at'];
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        return $m;
    }
}
