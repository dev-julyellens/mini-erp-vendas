<?php

declare(strict_types=1);

namespace App\Models;

final class PixCharge
{
    /** @var list<string> */
    public const STATUSES = ['pending', 'paid', 'expired', 'canceled'];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'pending' => 'Aguardando pagamento',
        'paid' => 'Pago',
        'expired' => 'Expirado',
        'canceled' => 'Cancelado',
    ];

    public int $id;
    public int $company_id;
    public int $accounts_receivable_id;
    public ?int $installment_id;
    public string $gateway;
    public string $external_id;
    public string $amount;
    public string $status;
    public ?string $qr_payload;
    public ?string $qr_image_url;
    public ?string $receipt_reference;
    public string $expires_at;
    public ?string $paid_at;
    public ?int $payment_id;
    public ?int $created_by;
    public string $created_at;
    public string $updated_at;
    public ?string $customer_name;
    public ?int $order_id;
    public ?int $installment_number;

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->isPending() && strtotime($this->expires_at) < time());
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function statusBadge(string $status): string
    {
        return match ($status)
        {
            'paid' => 'success',
            'expired', 'canceled' => 'secondary',
            default => 'warning',
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->company_id = (int) $row['company_id'];
        $m->accounts_receivable_id = (int) $row['accounts_receivable_id'];
        $m->installment_id = isset($row['installment_id']) && $row['installment_id'] !== null
            ? (int) $row['installment_id']
            : null;
        $m->gateway = (string) $row['gateway'];
        $m->external_id = (string) $row['external_id'];
        $m->amount = (string) $row['amount'];
        $m->status = (string) $row['status'];
        $m->qr_payload = isset($row['qr_payload']) ? (string) $row['qr_payload'] : null;
        $m->qr_image_url = isset($row['qr_image_url']) ? (string) $row['qr_image_url'] : null;
        $m->receipt_reference = isset($row['receipt_reference']) ? (string) $row['receipt_reference'] : null;
        $m->expires_at = (string) $row['expires_at'];
        $m->paid_at = isset($row['paid_at']) && $row['paid_at'] !== null ? (string) $row['paid_at'] : null;
        $m->payment_id = isset($row['payment_id']) && $row['payment_id'] !== null
            ? (int) $row['payment_id']
            : null;
        $m->created_by = isset($row['created_by']) && $row['created_by'] !== null
            ? (int) $row['created_by']
            : null;
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) $row['updated_at'];
        $m->customer_name = isset($row['customer_name']) ? (string) $row['customer_name'] : null;
        $m->order_id = isset($row['order_id']) ? (int) $row['order_id'] : null;
        $m->installment_number = isset($row['installment_number'])
            ? (int) $row['installment_number']
            : null;

        return $m;
    }
}
