<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Subscription;
use PDO;

final class SubscriptionRepository extends BaseRepository
{
    public function findByCompanyId(int $companyId): ?Subscription
    {
        $stmt = $this->db->prepare(
            'SELECT s.id, s.company_id, s.plan_id, s.status,
                    s.current_period_start, s.current_period_end,
                    s.trial_ends_at, s.canceled_at, s.cancel_at_period_end,
                    p.code AS plan_code, p.name AS plan_name
             FROM subscriptions s
             INNER JOIN plans p ON p.id = s.plan_id
             WHERE s.company_id = :company_id'
        );
        $stmt->execute(['company_id' => $companyId]);
        $row = $stmt->fetch();

        return $row ? Subscription::fromArray($row) : null;
    }

    public function create(
        int $companyId,
        int $planId,
        string $status,
        string $periodStart,
        string $periodEnd,
        ?string $trialEndsAt = null
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO subscriptions (
                company_id, plan_id, status,
                current_period_start, current_period_end, trial_ends_at
             ) VALUES (
                :company_id, :plan_id, :status,
                :period_start, :period_end, :trial_ends_at
             )
             RETURNING id'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => $status,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'trial_ends_at' => $trialEndsAt,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function updatePlan(int $companyId, int $planId, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE subscriptions
             SET plan_id = :plan_id,
                 status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :company_id'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => $status,
        ]);
    }

    public function renewPeriod(int $companyId, string $periodStart, string $periodEnd, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE subscriptions
             SET current_period_start = :period_start,
                 current_period_end = :period_end,
                 status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :company_id'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => $status,
        ]);
    }

    public function updateStatus(int $companyId, string $status): void
    {
        $stmt = $this->db->prepare(
            'UPDATE subscriptions
             SET status = :status,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :company_id'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'status' => $status,
        ]);
    }

    /**
     * @return list<Subscription>
     */
    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function paginateAdmin(int $page, int $perPage, ?string $search = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if ($search !== null && trim($search) !== '')
        {
            $where[] = '(c.name ILIKE :search OR p.name ILIKE :search OR p.code ILIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM subscriptions s
             INNER JOIN companies c ON c.id = s.company_id
             INNER JOIN plans p ON p.id = s.plan_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT s.id, s.company_id, s.plan_id, s.status,
                    s.current_period_start, s.current_period_end,
                    s.trial_ends_at, c.name AS company_name, p.name AS plan_name, p.code AS plan_code
             FROM subscriptions s
             INNER JOIN companies c ON c.id = s.company_id
             INNER JOIN plans p ON p.id = s.plan_id
             WHERE {$whereSql}
             ORDER BY c.name ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value)
        {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * @return list<Subscription>
     */
    public function listDueForRenewal(string $before): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.id, s.company_id, s.plan_id, s.status,
                    s.current_period_start, s.current_period_end,
                    s.trial_ends_at, s.canceled_at, s.cancel_at_period_end,
                    p.code AS plan_code, p.name AS plan_name
             FROM subscriptions s
             INNER JOIN plans p ON p.id = s.plan_id
             WHERE s.status IN (\'active\', \'trialing\', \'past_due\')
               AND s.current_period_end <= :before
             ORDER BY s.current_period_end ASC'
        );
        $stmt->execute(['before' => $before]);
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Subscription::fromArray($row);
        }

        return $list;
    }
}
