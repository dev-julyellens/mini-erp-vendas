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
        $m->active = filter_var($row['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $m->created_at = (string) $row['created_at'];
        $m->updated_at = (string) $row['updated_at'];
        return $m;
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}
     */
    public function toSessionArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
