<?php

declare(strict_types=1);

namespace App\Models;

final class BackupLog
{
    public int $id;
    public string $operation;
    public string $trigger_type;
    public ?string $filename;
    public ?int $file_size;
    public string $status;
    public ?string $message;
    public ?int $user_id;
    public ?int $duration_ms;
    public string $created_at;
    public ?string $user_name;
    public ?string $user_email;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->operation = (string) $row['operation'];
        $m->trigger_type = (string) $row['trigger_type'];
        $m->filename = isset($row['filename']) && $row['filename'] !== null ? (string) $row['filename'] : null;
        $m->file_size = isset($row['file_size']) && $row['file_size'] !== null ? (int) $row['file_size'] : null;
        $m->status = (string) $row['status'];
        $m->message = isset($row['message']) && $row['message'] !== null ? (string) $row['message'] : null;
        $m->user_id = isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $m->duration_ms = isset($row['duration_ms']) && $row['duration_ms'] !== null ? (int) $row['duration_ms'] : null;
        $m->created_at = (string) $row['created_at'];
        $m->user_name = isset($row['user_name']) && $row['user_name'] !== null ? (string) $row['user_name'] : null;
        $m->user_email = isset($row['user_email']) && $row['user_email'] !== null ? (string) $row['user_email'] : null;

        return $m;
    }
}
