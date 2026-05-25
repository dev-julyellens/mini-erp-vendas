<?php

declare(strict_types=1);

namespace App\Models;

final class Order
{
    /** @var list<string> */
    public const STATUSES = ['pending', 'paid', 'canceled', 'refunded'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'pending' => 'Pendente',
        'paid' => 'Paga',
        'canceled' => 'Cancelada',
        'refunded' => 'Reembolsada',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGE = [
        'pending' => 'warning',
        'paid' => 'success',
        'canceled' => 'secondary',
        'refunded' => 'info',
    ];

    public int $id;
    public int $customer_id;
    public string $total_amount;
    public string $status;
    public ?int $canceled_by;
    public ?string $canceled_at;
    public string $created_at;
    public ?string $customer_name;
    public ?string $canceled_by_name;

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'paid'], true);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['canceled', 'refunded'], true);
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
        $m->customer_id = (int) $row['customer_id'];
        $m->total_amount = (string) $row['total_amount'];
        $m->status = isset($row['status']) ? (string) $row['status'] : 'paid';
        $m->canceled_by = isset($row['canceled_by']) && $row['canceled_by'] !== null
            ? (int) $row['canceled_by']
            : null;
        $m->canceled_at = isset($row['canceled_at']) && $row['canceled_at'] !== null
            ? (string) $row['canceled_at']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        $m->canceled_by_name = isset($row['canceled_by_name']) ? (string) $row['canceled_by_name'] : null;

        return $m;
    }
}
