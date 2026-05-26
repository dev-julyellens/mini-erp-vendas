<?php

declare(strict_types=1);

namespace App\Models;

final class AccessLog
{
    public int $id;
    public ?int $user_id;
    public string $ip_address;
    public string $http_method;
    public string $path;
    public ?int $status_code;
    public ?string $user_agent;
    public string $created_at;
    public ?string $user_name = null;
    public ?string $user_email = null;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->user_id = isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $m->ip_address = (string) $row['ip_address'];
        $m->http_method = (string) $row['http_method'];
        $m->path = (string) $row['path'];
        $m->status_code = isset($row['status_code']) && $row['status_code'] !== null ? (int) $row['status_code'] : null;
        $m->user_agent = isset($row['user_agent']) ? ($row['user_agent'] !== null ? (string) $row['user_agent'] : null) : null;
        $m->created_at = (string) $row['created_at'];
        $m->user_name = isset($row['user_name']) ? (string) $row['user_name'] : null;
        $m->user_email = isset($row['user_email']) ? (string) $row['user_email'] : null;

        return $m;
    }
}
