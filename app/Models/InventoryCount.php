<?php

declare(strict_types=1);

namespace App\Models;

final class InventoryCount
{
    /** @var list<string> */
    public const STATUSES = ['open', 'finalized', 'canceled'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'open' => 'Em andamento',
        'finalized' => 'Finalizado',
        'canceled' => 'Cancelado',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGE = [
        'open' => 'warning',
        'finalized' => 'success',
        'canceled' => 'secondary',
    ];

    public int $id;
    public string $status;
    public ?string $notes;
    public ?int $created_by;
    public ?int $finalized_by;
    public ?string $finalized_at;
    public string $created_at;
    public ?string $created_by_name;
    public ?string $finalized_by_name;

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function statusBadge(string $status): string
    {
        return self::STATUS_BADGE[$status] ?? 'light';
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->status = (string) ($row['status'] ?? 'open');
        $m->notes = isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null;
        $m->created_by = isset($row['created_by']) && $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;
        $m->finalized_by = isset($row['finalized_by']) && $row['finalized_by'] !== null
            ? (int) $row['finalized_by']
            : null;
        $m->finalized_at = isset($row['finalized_at']) && $row['finalized_at'] !== null
            ? (string) $row['finalized_at']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->created_by_name = isset($row['created_by_name']) ? (string) $row['created_by_name'] : null;
        $m->finalized_by_name = isset($row['finalized_by_name']) ? (string) $row['finalized_by_name'] : null;

        return $m;
    }
}
