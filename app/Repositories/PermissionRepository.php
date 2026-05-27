<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PermissionRepository extends BaseRepository
{
    /**
     * @return list<string> chaves "modulo.acao"
     */
    public function findPermissionKeysByRole(string $role): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.module, p.action
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role = :role
             ORDER BY p.module, p.action'
        );
        $stmt->execute(['role' => $role]);
        $keys = [];
        while ($row = $stmt->fetch())
        {
            $keys[] = (string) $row['module'] . '.' . (string) $row['action'];
        }

        return $keys;
    }

    public function roleHasPermission(string $role, string $module, string $action): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role = :role AND p.module = :module AND p.action = :action
             LIMIT 1'
        );
        $stmt->execute([
            'role' => $role,
            'module' => $module,
            'action' => $action,
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
