<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ApiLogRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function insert(
        ?int $userId,
        string $ipAddress,
        string $httpMethod,
        string $endpoint,
        ?array $payload
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO api_logs (user_id, ip_address, http_method, endpoint, payload)
             VALUES (:user_id, :ip_address, :http_method, :endpoint, :payload)
             RETURNING id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'http_method' => $httpMethod,
            'endpoint' => $endpoint,
            'payload' => $this->encodeJson($payload),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatusCode(int $logId, int $statusCode): void
    {
        $stmt = $this->db->prepare(
            'UPDATE api_logs SET status_code = :status_code WHERE id = :id'
        );
        $stmt->execute([
            'status_code' => $statusCode,
            'id' => $logId,
        ]);
    }

    public function attachUserId(int $logId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE api_logs SET user_id = :user_id WHERE id = :id AND user_id IS NULL'
        );
        $stmt->execute([
            'user_id' => $userId,
            'id' => $logId,
        ]);
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function encodeJson(?array $payload): ?string
    {
        if ($payload === null)
        {
            return null;
        }

        try
        {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $e)
        {
            return null;
        }
    }
}
