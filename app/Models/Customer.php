<?php

declare(strict_types=1);

namespace App\Models;

final class Customer
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $phone;
    public string $created_at;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->name = (string) $row['name'];
        $m->email = (string) $row['email'];
        $m->phone = isset($row['phone']) ? ($row['phone'] !== null ? (string) $row['phone'] : null) : null;
        $m->created_at = (string) $row['created_at'];
        return $m;
    }
}
