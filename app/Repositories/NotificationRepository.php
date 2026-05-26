<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Core\Database;
use App\Models\Notification;
use App\Repositories\Concerns\CompanyScope;

final class NotificationRepository
{
    use CompanyScope;

    private const SELECT_COLUMNS = '
        id, company_id, type, title, message, entity_type, entity_id,
        level, link_url, dedupe_key, read_at, created_at';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    /**
     * @param array{
     *   type: string,
     *   title: string,
     *   message: string,
     *   entity_type?: ?string,
     *   entity_id?: ?int,
     *   level?: string,
     *   link_url?: ?string,
     *   dedupe_key?: ?string
     * } $data
     */
    public function insert(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (
                company_id, type, title, message, entity_type, entity_id,
                level, link_url, dedupe_key
             ) VALUES (
                :company_id, :type, :title, :message, :entity_type, :entity_id,
                :level, :link_url, :dedupe_key
             ) RETURNING id'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'level' => $data['level'] ?? 'warning',
            'link_url' => $data['link_url'] ?? null,
            'dedupe_key' => $data['dedupe_key'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?Notification
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM notifications
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? Notification::fromArray($row) : null;
    }

    public function existsUnreadByDedupe(string $dedupeKey): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM notifications
             WHERE company_id = :company_id
               AND dedupe_key = :dedupe_key
               AND read_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'dedupe_key' => $dedupeKey,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Evita recriar alerta operacional já registrado (lido ou não).
     */
    public function existsByDedupeKey(string $dedupeKey): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM notifications
             WHERE company_id = :company_id
               AND dedupe_key = :dedupe_key
             LIMIT 1'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'dedupe_key' => $dedupeKey,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function countUnread(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM notifications
             WHERE company_id = :company_id AND read_at IS NULL'
        );
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<Notification>
     */
    public function findRecent(int $limit, bool $unreadOnly = false): array
    {
        $limit = max(1, min($limit, 50));
        $sql = 'SELECT ' . self::SELECT_COLUMNS . '
                FROM notifications
                WHERE company_id = :company_id';
        if ($unreadOnly)
        {
            $sql .= ' AND read_at IS NULL';
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':company_id', $this->companyId(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapRows($stmt->fetchAll());
    }

    /**
     * @return list<Notification>
     */
    public function findUnreadAfterId(int $afterId, int $limit): array
    {
        $limit = max(1, min($limit, 10));
        $stmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM notifications
             WHERE company_id = :company_id
               AND read_at IS NULL
               AND id > :after_id
             ORDER BY id ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':company_id', $this->companyId(), PDO::PARAM_INT);
        $stmt->bindValue(':after_id', max(0, $afterId), PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $this->mapRows($stmt->fetchAll());
    }

    public function maxId(): int
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(id), 0)
             FROM notifications
             WHERE company_id = :company_id'
        );
        $stmt->execute($this->companyParams());

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<Notification>, total: int}
     */
    public function paginate(
        int $page,
        int $perPage,
        ?string $type,
        ?bool $unreadOnly
    ): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['company_id = :company_id'];
        $params = $this->companyParams();

        if ($type !== null && $type !== '')
        {
            $where[] = 'type = :type';
            $params['type'] = $type;
        }
        if ($unreadOnly === true)
        {
            $where[] = 'read_at IS NULL';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE ' . $whereSql
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->db->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM notifications
             WHERE ' . $whereSql . '
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value)
        {
            $listStmt->bindValue(':' . $key, $value);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        return [
            'items' => $this->mapRows($listStmt->fetchAll()),
            'total' => $total,
        ];
    }

    public function markRead(int $id): bool
    {
        $notification = $this->findById($id);
        if ($notification === null)
        {
            return false;
        }

        if ($notification->isRead())
        {
            return true;
        }

        $stmt = $this->db->prepare(
            'UPDATE notifications
             SET read_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND company_id = :company_id
               AND read_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);

        return $stmt->rowCount() > 0;
    }

    public function markAllRead(): int
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications
             SET read_at = CURRENT_TIMESTAMP
             WHERE company_id = :company_id AND read_at IS NULL'
        );
        $stmt->execute($this->companyParams());

        return $stmt->rowCount();
    }

    public function deleteByDedupeKey(string $dedupeKey): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notifications
             WHERE company_id = :company_id AND dedupe_key = :dedupe_key'
        );
        $stmt->execute([
            'company_id' => $this->companyId(),
            'dedupe_key' => $dedupeKey,
        ]);

        return $stmt->rowCount();
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<Notification>
     */
    private function mapRows(array $rows): array
    {
        $items = [];
        foreach ($rows as $row)
        {
            $items[] = Notification::fromArray($row);
        }

        return $items;
    }
}
