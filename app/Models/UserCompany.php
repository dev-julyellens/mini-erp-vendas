<?php

declare(strict_types=1);

namespace App\Models;

final class UserCompany
{
    public int $user_id;
    public int $company_id;
    public string $role;
    public bool $active;
    public string $user_name;
    public string $user_email;
    public string $company_name;
    public ?string $created_at;
    public ?string $updated_at;

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->user_id = (int) $row['user_id'];
        $m->company_id = (int) $row['company_id'];
        $m->role = (string) ($row['role'] ?? 'employee');
        $m->active = self::parseBool($row['active'] ?? true);
        $m->user_name = (string) ($row['user_name'] ?? '');
        $m->user_email = (string) ($row['user_email'] ?? '');
        $m->company_name = (string) ($row['company_name'] ?? '');
        $m->created_at = isset($row['created_at']) ? (string) $row['created_at'] : null;
        $m->updated_at = isset($row['updated_at']) ? (string) $row['updated_at'] : null;

        return $m;
    }

    private static function parseBool(mixed $value): bool
    {
        if (is_bool($value))
        {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 't', 'yes', 'on'], true);
    }

    public function roleLabel(): string
    {
        return match ($this->role)
        {
            'owner' => 'Proprietário',
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'employee' => 'Colaborador',
            default => $this->role,
        };
    }
}
