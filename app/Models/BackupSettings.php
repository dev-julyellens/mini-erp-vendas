<?php

declare(strict_types=1);

namespace App\Models;

final class BackupSettings
{
    public bool $enabled;
    public int $run_hour;
    public int $run_minute;
    public string $frequency;
    public ?string $last_run_at;
    public string $updated_at;
    public ?int $updated_by_user_id;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->enabled = filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN);
        $m->run_hour = (int) $row['run_hour'];
        $m->run_minute = (int) $row['run_minute'];
        $m->frequency = (string) $row['frequency'];
        $m->last_run_at = isset($row['last_run_at']) && $row['last_run_at'] !== null
            ? (string) $row['last_run_at']
            : null;
        $m->updated_at = (string) $row['updated_at'];
        $m->updated_by_user_id = isset($row['updated_by_user_id']) && $row['updated_by_user_id'] !== null
            ? (int) $row['updated_by_user_id']
            : null;

        return $m;
    }
}
