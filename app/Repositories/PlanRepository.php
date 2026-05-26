<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Plan;
use PDO;

final class PlanRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Plan
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name, description, price_monthly, billing_interval,
                    trial_days, active, sort_order
             FROM plans
             WHERE id = :id AND active = TRUE'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Plan::fromArray($row) : null;
    }

    public function findByCode(string $code): ?Plan
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name, description, price_monthly, billing_interval,
                    trial_days, active, sort_order
             FROM plans
             WHERE code = :code AND active = TRUE'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row ? Plan::fromArray($row) : null;
    }

    /**
     * @return list<Plan>
     */
    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, code, name, description, price_monthly, billing_interval,
                    trial_days, active, sort_order
             FROM plans
             ORDER BY sort_order ASC, name ASC'
        );
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Plan::fromArray($row);
        }

        return $list;
    }

    /**
     * @return list<Plan>
     */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            'SELECT id, code, name, description, price_monthly, billing_interval,
                    trial_days, active, sort_order
             FROM plans
             WHERE active = TRUE
             ORDER BY sort_order ASC, name ASC'
        );
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = Plan::fromArray($row);
        }

        return $list;
    }

    /**
     * @return array<string, int>
     */
    public function limitsForPlan(int $planId): array
    {
        $stmt = $this->db->prepare(
            'SELECT limit_key, limit_value
             FROM plan_limits
             WHERE plan_id = :plan_id'
        );
        $stmt->execute(['plan_id' => $planId]);
        $limits = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $limits[(string) $row['limit_key']] = (int) $row['limit_value'];
        }

        return $limits;
    }
}
