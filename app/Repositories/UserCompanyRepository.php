<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\UserCompany;
use PDO;

final class UserCompanyRepository extends BaseRepository
{
    public function find(int $userId, int $companyId): ?UserCompany
    {
        $stmt = $this->db->prepare(
            'SELECT uc.user_id, uc.company_id, uc.role, uc.active, uc.created_at, uc.updated_at,
                    u.name AS user_name, u.email AS user_email, c.name AS company_name
             FROM user_companies uc
             INNER JOIN users u ON u.id = uc.user_id
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :user_id AND uc.company_id = :company_id'
        );
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);
        $row = $stmt->fetch();

        return $row ? UserCompany::fromArray($row) : null;
    }

    public function getRoleForUserInCompany(int $userId, int $companyId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT role FROM user_companies
             WHERE user_id = :user_id AND company_id = :company_id AND active = TRUE'
        );
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);
        $role = $stmt->fetchColumn();

        return $role !== false ? (string) $role : null;
    }

    /**
     * @return array{items: list<UserCompany>, total: int}
     */
    public function paginate(
        int $page,
        int $perPage,
        ?int $companyId = null,
        ?int $userId = null,
        ?string $search = null,
        ?string $role = null,
        ?bool $activeOnly = null
    ): array
    {
        $where = ['1=1'];
        $params = [];

        if ($companyId !== null)
        {
            $where[] = 'uc.company_id = :company_id';
            $params['company_id'] = $companyId;
        }

        if ($userId !== null)
        {
            $where[] = 'uc.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($search !== null && trim($search) !== '')
        {
            $where[] = '(u.name ILIKE :search OR u.email ILIKE :search OR c.name ILIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        if ($role !== null && $role !== '')
        {
            $where[] = 'uc.role = :role';
            $params['role'] = $role;
        }

        if ($activeOnly === true)
        {
            $where[] = 'uc.active = TRUE';
        }
        elseif ($activeOnly === false)
        {
            $where[] = 'uc.active = FALSE';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM user_companies uc
             INNER JOIN users u ON u.id = uc.user_id
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT uc.user_id, uc.company_id, uc.role, uc.active, uc.created_at, uc.updated_at,
                    u.name AS user_name, u.email AS user_email, c.name AS company_name
             FROM user_companies uc
             INNER JOIN users u ON u.id = uc.user_id
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE {$whereSql}
             ORDER BY c.name ASC, u.name ASC
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
            $items[] = UserCompany::fromArray($row);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function attach(int $userId, int $companyId, string $role): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_companies (user_id, company_id, role, active)
             VALUES (:user_id, :company_id, :role, TRUE)
             ON CONFLICT (user_id, company_id)
             DO UPDATE SET role = EXCLUDED.role, active = TRUE, updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
            'role' => $role,
        ]);
    }

    public function updateRole(int $userId, int $companyId, string $role): void
    {
        $stmt = $this->db->prepare(
            'UPDATE user_companies
             SET role = :role, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
            'role' => $role,
        ]);
    }

    public function setActive(int $userId, int $companyId, bool $active): void
    {
        $stmt = $this->db->prepare(
            'UPDATE user_companies
             SET active = :active, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        Database::bindBool($stmt, ':active', $active);
        $stmt->execute();
    }

    public function detach(int $userId, int $companyId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM user_companies WHERE user_id = :user_id AND company_id = :company_id'
        );
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);
    }

    public function countActiveCompaniesForUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM user_companies uc
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :user_id AND uc.active = TRUE AND c.active = TRUE'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Empresas vinculadas ao usuário com papel (para perfil).
     *
     * @return list<array{company_id: int, company_name: string, role: string, active: bool}>
     */
    public function listBindingsForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT uc.company_id, c.name AS company_name, uc.role, uc.active
             FROM user_companies uc
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :user_id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = [
                'company_id' => (int) $row['company_id'],
                'company_name' => (string) $row['company_name'],
                'role' => (string) $row['role'],
                'active' => (bool) $row['active'],
            ];
        }

        return $list;
    }
}
