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
            'SELECT id, name, tax_id, slug, owner_user_id,
                    onboarding_step, onboarding_completed_at,
                    active, created_at, updated_at
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
            'SELECT c.id, c.name, c.tax_id, c.slug, c.owner_user_id,
                    c.onboarding_step, c.onboarding_completed_at,
                    c.active, c.created_at, c.updated_at
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

    public function countUsers(int $companyId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM user_companies WHERE company_id = :company_id'
        );
        $stmt->execute(['company_id' => $companyId]);

        return (int) $stmt->fetchColumn();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM companies WHERE LOWER(slug) = LOWER(:slug)';
        $params = ['slug' => $slug];
        if ($excludeId !== null)
        {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function updateProfile(int $companyId, string $name, ?string $taxId, string $slug): void
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET name = :name,
                 tax_id = :tax_id,
                 slug = :slug,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $companyId,
            'name' => $name,
            'tax_id' => $taxId,
            'slug' => $slug,
        ]);
    }

    public function updateOnboardingStep(int $companyId, string $step): void
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET onboarding_step = :step,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $companyId,
            'step' => $step,
        ]);
    }

    public function completeOnboarding(int $companyId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET onboarding_step = \'completed\',
                 onboarding_completed_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $companyId]);
    }

    public function setOwner(int $companyId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE companies
             SET owner_user_id = :user_id,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND owner_user_id IS NULL'
        );
        $stmt->execute([
            'id' => $companyId,
            'user_id' => $userId,
        ]);
    }
}
