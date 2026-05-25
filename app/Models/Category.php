<?php

declare(strict_types=1);

namespace App\Models;

final class Category
{
    public int $id;
    public string $name;
    public ?string $description;
    public string $createdAt;

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
        $m->createdAt = (string) ($row['created_at'] ?? '');

        return $m;
    }
}
