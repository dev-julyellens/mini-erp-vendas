<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Payment;
use PDO;

final class PaymentRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

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
            'SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE accounts_receivable_id = :id'
        );
        $stmt->execute(['id' => $accountsReceivableId]);

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
                LEFT JOIN users u ON u.id = p.received_by
                WHERE p.accounts_receivable_id = :id
                ORDER BY p.paid_at DESC, p.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $accountsReceivableId]);
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
            'SELECT COALESCE(SUM(amount), 0)
             FROM payments
             WHERE paid_at::date >= :from AND paid_at::date <= :to'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        return (string) $stmt->fetchColumn();
    }
}
