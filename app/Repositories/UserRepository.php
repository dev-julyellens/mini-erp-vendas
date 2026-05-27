<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository extends BaseRepository
{
    private const USER_COLUMNS = 'id, name, email, password_hash, role, active, avatar_path, created_at, updated_at';

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE LOWER(email) = LOWER(:email)'
        );
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute(['hash' => $passwordHash, 'id' => $userId]);
    }

    public function deleteResetTokensForUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }

    public function insertResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $this->deleteResetTokensForUser($userId);

        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findUserIdByValidResetToken(string $tokenHash): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM password_reset_tokens
             WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $userId = $stmt->fetchColumn();
        return $userId !== false ? (int) $userId : null;
    }

    public function deleteResetToken(string $tokenHash): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset_tokens WHERE token_hash = :token_hash');
        $stmt->execute(['token_hash' => $tokenHash]);
    }

    /**
     * @return array{items: list<User>, total: int}
     */
    public function paginate(int $page, int $perPage, ?string $search = null, ?bool $activeOnly = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '')
        {
            $where[] = '(name ILIKE :search OR email ILIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        if ($activeOnly === true)
        {
            $where[] = 'active = TRUE';
        }
        elseif ($activeOnly === false)
        {
            $where[] = 'active = FALSE';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT ' . self::USER_COLUMNS . "
             FROM users
             WHERE {$whereSql}
             ORDER BY name ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value)
        {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = User::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function emailExistsForOther(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email) AND id <> :id LIMIT 1'
        );
        $stmt->execute(['email' => trim($email), 'id' => $excludeId]);

        return (bool) $stmt->fetchColumn();
    }

    public function insert(string $name, string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, active)
             VALUES (:name, :email, :password_hash, :role, TRUE)
             RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, string $name, string $email, string $role): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name, email = :email, role = :role, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET active = :active, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        Database::bindBool($stmt, ':active', $active);
        $stmt->execute();
    }

    public function updateAvatarPath(int $id, ?string $avatarPath): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET avatar_path = :avatar_path, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'avatar_path' => $avatarPath,
        ]);
    }

    /**
     * @return list<User>
     */
    public function listActiveForSelect(?string $search = null, int $limit = 50): array
    {
        $sql = 'SELECT ' . self::USER_COLUMNS . ' FROM users WHERE active = TRUE';
        $params = [];
        if ($search !== null && trim($search) !== '')
        {
            $sql .= ' AND (name ILIKE :search OR email ILIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY name ASC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value)
        {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = User::fromArray($row);
        }

        return $list;
    }
}
