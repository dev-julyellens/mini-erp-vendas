<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $password_hash;
    public string $role;
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
        $m->email = (string) $row['email'];
        $m->password_hash = (string) $row['password_hash'];
        $m->role = (string) $row['role'];
        $m->active = self::parseBool($row['active'] ?? true);
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) $row['updated_at'];
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

    /**
     * @return array{
     *   id: int,
     *   name: string,
     *   email: string,
     *   role: string,
     *   company_id?: int,
     *   company_name?: string
     * }
     */
    public function toSessionArray(
        ?int $companyId = null,
        ?string $companyName = null,
        ?string $companyRole = null
    ): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($companyId !== null && $companyId > 0)
        {
            $data['company_id'] = $companyId;
            $data['company_name'] = trim((string) $companyName);
            if ($companyRole !== null && $companyRole !== '')
            {
                $data['company_role'] = $companyRole;
            }
        }

        return $data;
    }
}
