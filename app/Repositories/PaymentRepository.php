<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class PaymentRepository extends BaseRepository
{
    use CompanyScope;

    public function insert(
        int $accountsReceivableId,
        string $amount,
        string $paymentMethod,
        string $paidAt,
        ?int $receivedBy,
        ?string $notes
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payments (accounts_receivable_id, amount, payment_method, paid_at, received_by, notes)
             VALUES (:ar_id, :amount, :method, :paid_at, :received_by, :notes)
             RETURNING id'
        );
        $stmt->execute([
            'ar_id' => $accountsReceivableId,
            'amount' => $amount,
            'method' => $paymentMethod,
            'paid_at' => $paidAt,
            'received_by' => $receivedBy,
            'notes' => $notes,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function sumByAccountsReceivableId(int $accountsReceivableId): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(pay.amount), 0)
             FROM payments pay
             INNER JOIN accounts_receivable ar ON ar.id = pay.accounts_receivable_id
             INNER JOIN orders o ON o.id = ar.order_id AND o.company_id = :company_id
             WHERE pay.accounts_receivable_id = :id'
        );
        $stmt->execute([
            'id' => $accountsReceivableId,
            'company_id' => $this->companyId(),
        ]);

        return (string) $stmt->fetchColumn();
    }

    /**
     * @return list<Payment>
     */
    public function findByAccountsReceivableId(int $accountsReceivableId): array
    {
        $sql = 'SELECT p.id, p.accounts_receivable_id, p.amount, p.payment_method, p.paid_at,
                       p.received_by, p.notes, p.created_at, u.name AS received_by_name
                FROM payments p
                INNER JOIN accounts_receivable ar ON ar.id = p.accounts_receivable_id
                INNER JOIN orders o ON o.id = ar.order_id AND o.company_id = :company_id
                LEFT JOIN users u ON u.id = p.received_by
                WHERE p.accounts_receivable_id = :id
                ORDER BY p.paid_at DESC, p.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $accountsReceivableId,
            'company_id' => $this->companyId(),
        ]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row)
        {
            $items[] = Payment::fromArray($row);
        }

        return $items;
    }

    public function sumReceivedBetween(string $from, string $to): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             INNER JOIN accounts_receivable ar ON ar.id = p.accounts_receivable_id
             INNER JOIN orders o ON o.id = ar.order_id AND o.company_id = :company_id
             WHERE p.paid_at::date >= :from AND p.paid_at::date <= :to'
        );
        $stmt->execute([
            'from' => $from,
            'to' => $to,
            'company_id' => $this->companyId(),
        ]);

        return (string) $stmt->fetchColumn();
    }
}
