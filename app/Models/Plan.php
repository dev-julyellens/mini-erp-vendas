<?php

declare(strict_types=1);

namespace App\Models;

final class Plan
{
    public int $id;
    public string $code;
    public string $name;
    public ?string $description;
    public string $price_monthly;
    public string $billing_interval;
    public int $trial_days;
    public bool $active;
    public int $sort_order;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->code = (string) $row['code'];
        $m->name = (string) $row['name'];
        $m->description = isset($row['description']) && $row['description'] !== ''
            ? (string) $row['description']
            : null;
        $m->price_monthly = (string) $row['price_monthly'];
        $m->billing_interval = (string) $row['billing_interval'];
        $m->trial_days = (int) $row['trial_days'];
        $m->active = filter_var($row['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $m->sort_order = (int) ($row['sort_order'] ?? 0);

        return $m;
    }
}
