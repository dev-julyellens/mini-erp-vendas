<?php

declare(strict_types=1);

namespace App\Models;

final class CashFlow
{
    /** @var list<string> */
    public const TYPES = ['entrada', 'saida'];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        'entrada' => 'Entrada',
        'saida' => 'Saída',
    ];

    public int $id;
    public string $type;
    public string $amount;
    public ?string $payment_method;
    public ?string $reference_type;
    public ?int $reference_id;
    public ?string $description;
    public string $occurred_at;
    public ?int $created_by;
    public string $created_at;
    public ?string $created_by_name;

    public static function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->type = (string) $row['type'];
        $m->amount = (string) $row['amount'];
        $m->payment_method = isset($row['payment_method']) && $row['payment_method'] !== null
            ? (string) $row['payment_method']
            : null;
        $m->reference_type = isset($row['reference_type']) && $row['reference_type'] !== null
            ? (string) $row['reference_type']
            : null;
        $m->reference_id = isset($row['reference_id']) && $row['reference_id'] !== null
            ? (int) $row['reference_id']
            : null;
        $m->description = isset($row['description']) && $row['description'] !== null
            ? (string) $row['description']
            : null;
        $m->occurred_at = (string) $row['occurred_at'];
        $m->created_by = isset($row['created_by']) && $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->created_by_name = isset($row['created_by_name']) ? (string) $row['created_by_name'] : null;

        return $m;
    }
}
