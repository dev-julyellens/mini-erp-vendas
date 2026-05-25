<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role, active, created_at, updated_at
             FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, role, active, created_at, updated_at
             FROM users WHERE LOWER(email) = LOWER(:email)'
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
}
