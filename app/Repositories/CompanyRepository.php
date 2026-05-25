<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Company;
use PDO;

final class CompanyRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Company
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, tax_id, active, created_at, updated_at
             FROM companies
             WHERE id = :id AND active = TRUE'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Company::fromArray($row) : null;
    }

    /**
     * @return list<Company>
     */
    public function listActiveForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.tax_id, c.active, c.created_at, c.updated_at
             FROM companies c
             INNER JOIN user_companies uc ON uc.company_id = c.id
             WHERE uc.user_id = :user_id AND c.active = TRUE
             ORDER BY c.name ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Company::fromArray($row);
        }

        return $list;
    }

    public function userHasAccess(int $userId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM user_companies uc
             INNER JOIN companies c ON c.id = uc.company_id
             WHERE uc.user_id = :user_id
               AND uc.company_id = :company_id
               AND c.active = TRUE
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
