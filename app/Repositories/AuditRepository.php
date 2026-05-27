<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Helpers\CompanyContext;
use App\Models\AuditLog;
use PDO;

final class AuditRepository extends BaseRepository
{
    /**
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function insert(
        ?int $userId,
        string $action,
        string $entity,
        ?int $entityId,
        ?array $oldValues,
        ?array $newValues,
        ?string $ipAddress,
        ?string $userAgent
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (
                user_id, action, entity, entity_id,
                old_values, new_values, ip_address, user_agent, company_id
             ) VALUES (
                :user_id, :action, :entity, :entity_id,
                :old_values, :new_values, :ip_address, :user_agent, :company_id
             ) RETURNING id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => $this->encodeJson($oldValues),
            'new_values' => $this->encodeJson($newValues),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'company_id' => CompanyContext::id(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<AuditLog>, total: int}
     */
    public function search(
        ?int $userId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $entity,
        int $page,
        int $perPage
    ): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        $companyId = CompanyContext::id();
        if ($companyId !== null)
        {
            $where[] = '(a.company_id = :company_id OR a.company_id IS NULL)';
            $params['company_id'] = $companyId;
        }
        if ($where === [])
        {
            $where[] = '1 = 1';
        }

        if ($userId !== null)
        {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($dateFrom !== null && $dateFrom !== '')
        {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== null && $dateTo !== '')
        {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $dateTo;
        }

        if ($entity !== null && $entity !== '')
        {
            $where[] = 'a.entity = :entity';
            $params['entity'] = $entity;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM audit_logs a WHERE ' . $whereSql;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT a.id, a.user_id, a.action, a.entity, a.entity_id,
                       a.old_values, a.new_values, a.ip_address, a.user_agent, a.created_at,
                       u.name AS user_name, u.email AS user_email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE ' . $whereSql . '
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
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
            $items[] = AuditLog::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    public function listUsersForFilter(): array
    {
        $companyId = CompanyContext::id();
        if ($companyId !== null)
        {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT u.id, u.name, u.email
                 FROM users u
                 INNER JOIN audit_logs a ON a.user_id = u.id
                 WHERE a.company_id = :company_id OR a.company_id IS NULL
                 ORDER BY u.name ASC'
            );
            $stmt->execute(['company_id' => $companyId]);
        }
        else
        {
            $stmt = $this->db->query(
                'SELECT DISTINCT u.id, u.name, u.email
                 FROM users u
                 INNER JOIN audit_logs a ON a.user_id = u.id
                 ORDER BY u.name ASC'
            );
        }
        $rows = $stmt->fetchAll();
        $list = [];
        foreach ($rows as $row)
        {
            $list[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
            ];
        }

        return $list;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function encodeJson(?array $data): ?string
    {
        if ($data === null)
        {
            return null;
        }

        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
