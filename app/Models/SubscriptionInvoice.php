<?php

declare(strict_types=1);

namespace App\Models;

final class SubscriptionInvoice
{
    public int $id;
    public int $subscription_id;
    public int $company_id;
    public string $amount;
    public string $status;
    public string $period_start;
    public string $period_end;
    public string $due_at;
    public ?string $paid_at;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->subscription_id = (int) $row['subscription_id'];
        $m->company_id = (int) $row['company_id'];
        $m->amount = (string) $row['amount'];
        $m->status = (string) $row['status'];
        $m->period_start = (string) $row['period_start'];
        $m->period_end = (string) $row['period_end'];
        $m->due_at = (string) $row['due_at'];
        $m->paid_at = isset($row['paid_at']) && $row['paid_at'] !== null
            ? (string) $row['paid_at']
            : null;

        return $m;
    }
}
