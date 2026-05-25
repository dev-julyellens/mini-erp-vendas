<?php

declare(strict_types=1);

namespace App\Models;

final class AuditLog
{
    public int $id;
    public ?int $user_id;
    public string $action;
    public string $entity;
    public ?int $entity_id;
    /** @var array<string, mixed>|null */
    public ?array $old_values;
    /** @var array<string, mixed>|null */
    public ?array $new_values;
    public ?string $ip_address;
    public ?string $user_agent;
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
        $m->user_id = isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $m->action = (string) $row['action'];
        $m->entity = (string) $row['entity'];
        $m->entity_id = isset($row['entity_id']) && $row['entity_id'] !== null ? (int) $row['entity_id'] : null;
        $m->old_values = self::decodeJson($row['old_values'] ?? null);
        $m->new_values = self::decodeJson($row['new_values'] ?? null);
        $m->ip_address = isset($row['ip_address']) ? ($row['ip_address'] !== null ? (string) $row['ip_address'] : null) : null;
        $m->user_agent = isset($row['user_agent']) ? ($row['user_agent'] !== null ? (string) $row['user_agent'] : null) : null;
        $m->created_at = (string) $row['created_at'];
        $m->user_name = isset($row['user_name']) ? ($row['user_name'] !== null ? (string) $row['user_name'] : null) : null;
        $m->user_email = isset($row['user_email']) ? ($row['user_email'] !== null ? (string) $row['user_email'] : null) : null;

        return $m;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeJson(mixed $value): ?array
    {
        if ($value === null || $value === '')
        {
            return null;
        }

        if (is_array($value))
        {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
