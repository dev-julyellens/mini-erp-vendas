<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\SubscriptionInvoice;
use PDO;

final class SubscriptionInvoiceRepository extends BaseRepository
{
    public function create(
        int $subscriptionId,
        int $companyId,
        string $amount,
        string $periodStart,
        string $periodEnd,
        string $dueAt
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO subscription_invoices (
                subscription_id, company_id, amount, status,
                period_start, period_end, due_at
             ) VALUES (
                :subscription_id, :company_id, :amount, \'pending\',
                :period_start, :period_end, :due_at
             )
             RETURNING id'
        );
        $stmt->execute([
            'subscription_id' => $subscriptionId,
            'company_id' => $companyId,
            'amount' => $amount,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_at' => $dueAt,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findByIdForCompany(int $invoiceId, int $companyId): ?SubscriptionInvoice
    {
        $stmt = $this->db->prepare(
            'SELECT id, subscription_id, company_id, amount, status,
                    period_start, period_end, due_at, paid_at
             FROM subscription_invoices
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $invoiceId,
            'company_id' => $companyId,
        ]);
        $row = $stmt->fetch();

        return $row ? SubscriptionInvoice::fromArray($row) : null;
    }

    public function hasPendingForSubscription(int $subscriptionId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1
             FROM subscription_invoices
             WHERE subscription_id = :subscription_id AND status = \'pending\'
             LIMIT 1'
        );
        $stmt->execute(['subscription_id' => $subscriptionId]);

        return (bool) $stmt->fetchColumn();
    }

    public function markPaidForCompany(int $invoiceId, int $companyId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE subscription_invoices
             SET status = \'paid\',
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND company_id = :company_id
               AND status = \'pending\''
        );
        $stmt->execute([
            'id' => $invoiceId,
            'company_id' => $companyId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markFailed(int $invoiceId, string $reason): void
    {
        $stmt = $this->db->prepare(
            'UPDATE subscription_invoices
             SET status = \'failed\',
                 failure_reason = :reason,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $invoiceId,
            'reason' => $reason,
        ]);
    }

    /**
     * @return list<SubscriptionInvoice>
     */
    public function listByCompany(int $companyId, int $limit = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, subscription_id, company_id, amount, status,
                    period_start, period_end, due_at, paid_at
             FROM subscription_invoices
             WHERE company_id = :company_id
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $list = [];
        foreach ($stmt->fetchAll() as $row)
        {
            $list[] = SubscriptionInvoice::fromArray($row);
        }

        return $list;
    }

    public function findLatestPending(int $companyId): ?SubscriptionInvoice
    {
        $stmt = $this->db->prepare(
            'SELECT id, subscription_id, company_id, amount, status,
                    period_start, period_end, due_at, paid_at
             FROM subscription_invoices
             WHERE company_id = :company_id AND status = \'pending\'
             ORDER BY due_at ASC
             LIMIT 1'
        );
        $stmt->execute(['company_id' => $companyId]);
        $row = $stmt->fetch();

        return $row ? SubscriptionInvoice::fromArray($row) : null;
    }
}
