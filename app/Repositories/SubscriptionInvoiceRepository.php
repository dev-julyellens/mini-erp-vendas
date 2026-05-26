<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\SubscriptionInvoice;
use PDO;

final class SubscriptionInvoiceRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

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

    public function markPaid(int $invoiceId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE subscription_invoices
             SET status = \'paid\',
                 paid_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $invoiceId]);
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
