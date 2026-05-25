<?php

declare(strict_types=1);

namespace App\Models;

final class AccountsReceivable
{
    /** @var list<string> */
    public const STATUSES = ['pending', 'partial', 'paid', 'canceled'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'pending' => 'Pendente',
        'partial' => 'Parcial',
        'paid' => 'Recebida',
        'canceled' => 'Cancelada',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGE = [
        'pending' => 'warning',
        'partial' => 'info',
        'paid' => 'success',
        'canceled' => 'secondary',
    ];

    public int $id;
    public int $order_id;
    public int $customer_id;
    public string $amount;
    public string $due_date;
    public string $status;
    public string $created_at;
    public string $updated_at;
    public ?string $customer_name;
    public ?string $order_total;
    public ?string $paid_total;
    public ?string $remaining_amount;

    public function canReceive(): bool
    {
        return in_array($this->status, ['pending', 'partial'], true);
    }

    public function isOverdue(): bool
    {
        if (!in_array($this->status, ['pending', 'partial'], true))
        {
            return false;
        }

        try
        {
            $due = new \DateTimeImmutable($this->due_date);
            $today = new \DateTimeImmutable('today');

            return $due < $today;
        }
        catch (\Throwable $e)
        {
            return false;
        }
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
        $m->customer_id = (int) $row['customer_id'];
        $m->amount = (string) $row['amount'];
        $m->due_date = (string) $row['due_date'];
        $m->status = (string) $row['status'];
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) $row['updated_at'];
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        $m->order_total = isset($row['order_total']) ? (string) $row['order_total'] : null;
        $m->paid_total = isset($row['paid_total']) ? (string) $row['paid_total'] : null;
        $m->remaining_amount = isset($row['remaining_amount']) ? (string) $row['remaining_amount'] : null;

        return $m;
    }
}
