<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\BackupLog;
use PDO;

final class BackupLogRepository extends BaseRepository
{
    public function insert(
        string $operation,
        string $triggerType,
        string $status,
        ?string $filename = null,
        ?int $fileSize = null,
        ?string $message = null,
        ?int $userId = null,
        ?int $durationMs = null
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO backup_logs (
                operation, trigger_type, filename, file_size, status, message, user_id, duration_ms
             ) VALUES (
                :operation, :trigger_type, :filename, :file_size, :status, :message, :user_id, :duration_ms
             ) RETURNING id'
        );
        $stmt->execute([
            'operation' => $operation,
            'trigger_type' => $triggerType,
            'filename' => $filename,
            'file_size' => $fileSize,
            'status' => $status,
            'message' => $message,
            'user_id' => $userId,
            'duration_ms' => $durationMs,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<BackupLog>, total: int}
     */
    public function search(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->query('SELECT COUNT(*) FROM backup_logs')->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT b.id, b.operation, b.trigger_type, b.filename, b.file_size,
                    b.status, b.message, b.user_id, b.duration_ms, b.created_at,
                    u.name AS user_name, u.email AS user_email
             FROM backup_logs b
             LEFT JOIN users u ON u.id = b.user_id
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $items[] = BackupLog::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }
}
