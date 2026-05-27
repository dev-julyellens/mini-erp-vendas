<?php

declare(strict_types=1);

namespace App\Models;

final class Quote
{
    /** @var list<string> */
    public const STATUSES = ['draft', 'sent', 'approved', 'canceled', 'converted'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'draft' => 'Rascunho',
        'sent' => 'Enviado',
        'approved' => 'Aprovado',
        'canceled' => 'Cancelado',
        'converted' => 'Convertido em venda',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGE = [
        'draft' => 'secondary',
        'sent' => 'info',
        'approved' => 'success',
        'canceled' => 'dark',
        'converted' => 'primary',
    ];

    public int $id;
    public int $customer_id;
    public string $total_amount;
    public string $status;
    public ?string $valid_until;
    public ?string $notes;
    public ?int $converted_order_id;
    public ?int $created_by;
    public string $created_at;
    public string $updated_at;
    public ?string $customer_name;
    public ?string $created_by_name;

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'approved'], true);
    }

    public function canConvert(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'approved'], true);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'approved'], true);
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
        $m->status = (string) ($row['status'] ?? 'draft');
        $m->valid_until = isset($row['valid_until']) && $row['valid_until'] !== null
            ? (string) $row['valid_until']
            : null;
        $m->notes = isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null;
        $m->converted_order_id = isset($row['converted_order_id']) && $row['converted_order_id'] !== null
            ? (int) $row['converted_order_id']
            : null;
        $m->created_by = isset($row['created_by']) && $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) ($row['updated_at'] ?? $row['created_at']);
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        $m->created_by_name = isset($row['created_by_name']) ? (string) $row['created_by_name'] : null;

        return $m;
    }
}
