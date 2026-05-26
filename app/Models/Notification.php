<?php

declare(strict_types=1);

namespace App\Models;

final class Notification
{
    public int $id;
    public int $company_id;
    public string $type;
    public string $title;
    public string $message;
    public ?string $entity_type;
    public ?int $entity_id;
    public string $level;
    public ?string $link_url;
    public ?string $dedupe_key;
    public ?string $read_at;
    public string $created_at;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->company_id = (int) $row['company_id'];
        $m->type = (string) $row['type'];
        $m->title = (string) $row['title'];
        $m->message = (string) $row['message'];
        $m->entity_type = isset($row['entity_type']) && $row['entity_type'] !== null
            ? (string) $row['entity_type']
            : null;
        $m->entity_id = isset($row['entity_id']) && $row['entity_id'] !== null
            ? (int) $row['entity_id']
            : null;
        $m->level = (string) ($row['level'] ?? 'warning');
        $m->link_url = isset($row['link_url']) && $row['link_url'] !== null
            ? (string) $row['link_url']
            : null;
        $m->dedupe_key = isset($row['dedupe_key']) && $row['dedupe_key'] !== null
            ? (string) $row['dedupe_key']
            : null;
        $m->read_at = isset($row['read_at']) && $row['read_at'] !== null
            ? (string) $row['read_at']
            : null;
        $m->created_at = (string) $row['created_at'];

        return $m;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
