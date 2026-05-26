<?php

declare(strict_types=1);

namespace App\Models;

final class Subscription
{
    public int $id;
    public int $company_id;
    public int $plan_id;
    public string $status;
    public string $current_period_start;
    public string $current_period_end;
    public ?string $trial_ends_at;
    public ?string $canceled_at;
    public bool $cancel_at_period_end;
    public ?string $plan_code;
    public ?string $plan_name;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->company_id = (int) $row['company_id'];
        $m->plan_id = (int) $row['plan_id'];
        $m->status = (string) $row['status'];
        $m->current_period_start = (string) $row['current_period_start'];
        $m->current_period_end = (string) $row['current_period_end'];
        $m->trial_ends_at = isset($row['trial_ends_at']) && $row['trial_ends_at'] !== null
            ? (string) $row['trial_ends_at']
            : null;
        $m->canceled_at = isset($row['canceled_at']) && $row['canceled_at'] !== null
            ? (string) $row['canceled_at']
            : null;
        $m->cancel_at_period_end = filter_var($row['cancel_at_period_end'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $m->plan_code = isset($row['plan_code']) ? (string) $row['plan_code'] : null;
        $m->plan_name = isset($row['plan_name']) ? (string) $row['plan_name'] : null;

        return $m;
    }

    public function isUsable(): bool
    {
        return in_array($this->status, ['trialing', 'active', 'past_due'], true);
    }
}
