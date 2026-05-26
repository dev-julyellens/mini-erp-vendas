<?php

declare(strict_types=1);

namespace App\Models;

final class Company
{
    public int $id;
    public string $name;
    public ?string $tax_id;
    public string $slug;
    public ?int $owner_user_id;
    public string $onboarding_step;
    public ?string $onboarding_completed_at;
    public bool $active;
    public string $created_at;
    public string $updated_at;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = (int) $row['id'];
        $m->name = (string) $row['name'];
        $m->tax_id = isset($row['tax_id']) && $row['tax_id'] !== null && $row['tax_id'] !== ''
            ? (string) $row['tax_id']
            : null;
        $m->slug = (string) ($row['slug'] ?? 'empresa-' . $m->id);
        $m->owner_user_id = isset($row['owner_user_id']) && $row['owner_user_id'] !== null
            ? (int) $row['owner_user_id']
            : null;
        $m->onboarding_step = (string) ($row['onboarding_step'] ?? 'completed');
        $m->onboarding_completed_at = isset($row['onboarding_completed_at']) && $row['onboarding_completed_at'] !== null
            ? (string) $row['onboarding_completed_at']
            : null;
        $m->active = self::parseBool($row['active'] ?? true);
        $m->created_at = (string) ($row['created_at'] ?? '');
        $m->updated_at = (string) ($row['updated_at'] ?? '');

        return $m;
    }

    private static function parseBool(mixed $value): bool
    {
        if (is_bool($value))
        {
            return $value;
        }

        if (is_int($value) || is_float($value))
        {
            return (bool) $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 't', 'yes', 'on'], true);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null
            || $this->onboarding_step === 'completed';
    }

    /**
     * @return array{id: int, name: string}
     */
    public function toSelectArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
