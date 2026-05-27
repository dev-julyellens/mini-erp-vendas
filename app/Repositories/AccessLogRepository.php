<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AccessLog;
use PDO;

final class AccessLogRepository extends BaseRepository
{
    public function insert(
        ?int $userId,
        string $ipAddress,
        string $method,
        string $path,
        ?int $statusCode,
        ?string $userAgent
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO access_logs (user_id, ip_address, http_method, path, status_code, user_agent)
             VALUES (:user_id, :ip_address, :http_method, :path, :status_code, :user_agent)
             RETURNING id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'http_method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'user_agent' => $userAgent,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function updateStatus(int $logId, int $statusCode): void
    {
        $stmt = $this->db->prepare(
            'UPDATE access_logs SET status_code = :status_code WHERE id = :id'
        );
        $stmt->execute(['status_code' => $statusCode, 'id' => $logId]);
    }

    /**
     * @return array{items: list<AccessLog>, total: int, users: list<array{id: int, name: string, email: string}>}
     */
    public function search(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $pathFilter,
        int $page,
        int $perPage
    ): array
    {
        $conditions = ['1=1'];
        $params = [];

        if ($userId !== null && $userId > 0)
        {
            $conditions[] = 'al.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($dateFrom !== null && $dateFrom !== '')
        {
            $conditions[] = 'al.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== null && $dateTo !== '')
        {
            $conditions[] = 'al.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        if ($pathFilter !== null && $pathFilter !== '')
        {
            $conditions[] = 'al.path ILIKE :path_filter ESCAPE \'\\\'';
            $params['path_filter'] = '%' . self::escapeLikePattern($pathFilter) . '%';
        }

        $where = implode(' AND ', $conditions);

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM access_logs al WHERE ' . $where
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);

        $stmt = $this->db->prepare(
            'SELECT al.id, al.user_id, al.ip_address, al.http_method, al.path,
                    al.status_code, al.user_agent, al.created_at,
                    u.name AS user_name, u.email AS user_email
             FROM access_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE ' . $where . '
             ORDER BY al.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value)
        {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch())
        {
            $items[] = AccessLog::fromArray($row);
        }

        $usersStmt = $this->db->query(
            'SELECT id, name, email FROM users WHERE active = TRUE ORDER BY name'
        );
        $users = [];
        while ($row = $usersStmt->fetch())
        {
            $users[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
            ];
        }

        return ['items' => $items, 'total' => $total, 'users' => $users];
    }

    private static function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
