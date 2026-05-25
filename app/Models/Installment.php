<?php

declare(strict_types=1);

namespace App\Models;

final class Installment
{
    /** @var list<string> */
    public const STATUSES = ['pending', 'overdue', 'paid', 'canceled'];

    /** @var list<string> */
    public const OPEN_STATUSES = ['pending', 'overdue'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'pending' => 'Pendente',
        'overdue' => 'Vencida',
        'paid' => 'Paga',
        'canceled' => 'Cancelada',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGE = [
        'pending' => 'warning',
        'overdue' => 'danger',
        'paid' => 'success',
        'canceled' => 'secondary',
    ];

    public int $id;
    public int $order_id;
    public int $installment_number;
    public string $amount;
    public string $due_date;
    public ?string $paid_at;
    public string $status;
    public string $created_at;
    public string $updated_at;
    public ?string $customer_name;
    public ?string $order_total;
    public ?int $customer_id;

    public function canPay(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
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
        $m->order_id = (int) $row['order_id'];
        $m->installment_number = (int) $row['installment_number'];
        $m->amount = (string) $row['amount'];
        $m->due_date = (string) $row['due_date'];
        $m->paid_at = isset($row['paid_at']) && $row['paid_at'] !== null ? (string) $row['paid_at'] : null;
        $m->status = (string) $row['status'];
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) $row['updated_at'];
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        $m->order_total = isset($row['order_total']) ? (string) $row['order_total'] : null;
        $m->customer_id = isset($row['customer_id']) ? (int) $row['customer_id'] : null;

        return $m;
    }
}
