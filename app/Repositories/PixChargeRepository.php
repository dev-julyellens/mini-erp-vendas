<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PixCharge;
use App\Repositories\Concerns\CompanyScope;
use PDO;

final class PixChargeRepository
{
    use CompanyScope;

    private PDO $db;

    private const SELECT_COLUMNS = 'pc.id, pc.company_id, pc.accounts_receivable_id, pc.installment_id,
        pc.gateway, pc.external_id, pc.amount, pc.status, pc.qr_payload, pc.qr_image_url,
        pc.receipt_reference, pc.expires_at, pc.paid_at, pc.payment_id, pc.created_by,
        pc.created_at, pc.updated_at, c.name AS customer_name, ar.order_id,
        inst.installment_number';

    private const FROM_JOIN = 'FROM pix_charges pc
        INNER JOIN accounts_receivable ar ON ar.id = pc.accounts_receivable_id
        INNER JOIN orders o ON o.id = ar.order_id AND o.company_id = :company_id
        INNER JOIN customers c ON c.id = ar.customer_id AND c.company_id = :company_id
        LEFT JOIN installments inst ON inst.id = pc.installment_id';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function insert(
        int $companyId,
        int $accountsReceivableId,
        ?int $installmentId,
        string $gateway,
        string $externalId,
        string $amount,
        string $status,
        ?string $qrPayload,
        ?string $qrImageUrl,
        string $expiresAt,
        ?int $createdBy
    ): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pix_charges (
                company_id, accounts_receivable_id, installment_id, gateway, external_id,
                amount, status, qr_payload, qr_image_url, expires_at, created_by
             ) VALUES (
                :company_id, :ar_id, :installment_id, :gateway, :external_id,
                :amount, :status, :qr_payload, :qr_image_url, :expires_at, :created_by
             )
             RETURNING id'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'ar_id' => $accountsReceivableId,
            'installment_id' => $installmentId,
            'gateway' => $gateway,
            'external_id' => $externalId,
            'amount' => $amount,
            'status' => $status,
            'qr_payload' => $qrPayload,
            'qr_image_url' => $qrImageUrl,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?PixCharge
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN . ' WHERE pc.id = :id AND pc.company_id = :company_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function findByIdForUpdate(int $id): ?PixCharge
    {
        $sql = 'SELECT pc.id, pc.company_id, pc.accounts_receivable_id, pc.installment_id,
                       pc.gateway, pc.external_id, pc.amount, pc.status, pc.qr_payload,
                       pc.qr_image_url, pc.receipt_reference, pc.expires_at, pc.paid_at,
                       pc.payment_id, pc.created_by, pc.created_at, pc.updated_at
                FROM pix_charges pc
                WHERE pc.id = :id AND pc.company_id = :company_id
                FOR UPDATE OF pc';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'company_id' => $this->companyId()]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function findByGatewayExternalId(string $gateway, string $externalId): ?PixCharge
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN
            . ' WHERE pc.gateway = :gateway AND pc.external_id = :external_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'gateway' => $gateway,
            'external_id' => $externalId,
            'company_id' => $this->companyId(),
        ]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function findByGatewayExternalIdGlobal(string $gateway, string $externalId): ?PixCharge
    {
        $sql = 'SELECT pc.id, pc.company_id, pc.accounts_receivable_id, pc.installment_id,
                       pc.gateway, pc.external_id, pc.amount, pc.status, pc.qr_payload,
                       pc.qr_image_url, pc.receipt_reference, pc.expires_at, pc.paid_at,
                       pc.payment_id, pc.created_by, pc.created_at, pc.updated_at
                FROM pix_charges pc
                WHERE pc.gateway = :gateway AND pc.external_id = :external_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['gateway' => $gateway, 'external_id' => $externalId]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function findPendingByAccountsReceivableId(int $accountsReceivableId): ?PixCharge
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN
            . ' WHERE pc.accounts_receivable_id = :ar_id AND pc.status = :status
                AND pc.installment_id IS NULL
                ORDER BY pc.id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'ar_id' => $accountsReceivableId,
            'status' => 'pending',
            'company_id' => $this->companyId(),
        ]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function findPendingByInstallmentId(int $installmentId): ?PixCharge
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' ' . self::FROM_JOIN
            . ' WHERE pc.installment_id = :installment_id AND pc.status = :status
                ORDER BY pc.id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'installment_id' => $installmentId,
            'status' => 'pending',
            'company_id' => $this->companyId(),
        ]);
        $row = $stmt->fetch();

        return $row ? PixCharge::fromArray($row) : null;
    }

    public function updateStatus(
        int $id,
        string $status,
        ?string $paidAt = null,
        ?string $receiptReference = null,
        ?string $rawWebhook = null
    ): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pix_charges
             SET status = :status,
                 paid_at = COALESCE(:paid_at, paid_at),
                 receipt_reference = COALESCE(:receipt_reference, receipt_reference),
                 raw_webhook = COALESCE(:raw_webhook::jsonb, raw_webhook),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'company_id' => $this->companyId(),
            'status' => $status,
            'paid_at' => $paidAt,
            'receipt_reference' => $receiptReference,
            'raw_webhook' => $rawWebhook,
        ]);
    }

    public function updateStatusById(
        int $id,
        string $status,
        ?string $paidAt = null,
        ?string $receiptReference = null,
        ?string $rawWebhook = null
    ): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pix_charges
             SET status = :status,
                 paid_at = COALESCE(:paid_at, paid_at),
                 receipt_reference = COALESCE(:receipt_reference, receipt_reference),
                 raw_webhook = COALESCE(:raw_webhook::jsonb, raw_webhook),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'paid_at' => $paidAt,
            'receipt_reference' => $receiptReference,
            'raw_webhook' => $rawWebhook,
        ]);
    }

    public function linkPayment(int $id, int $paymentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pix_charges
             SET payment_id = :payment_id, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND company_id = :company_id'
        );
        $stmt->execute([
            'id' => $id,
            'payment_id' => $paymentId,
            'company_id' => $this->companyId(),
        ]);
    }

    public function linkPaymentById(int $id, int $paymentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pix_charges
             SET payment_id = :payment_id, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'payment_id' => $paymentId]);
    }

    public function updateQrImageUrlById(int $id, string $qrImageUrl): void
    {
        $stmt = $this->db->prepare(
            'UPDATE pix_charges
             SET qr_image_url = :qr_image_url, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'qr_image_url' => $qrImageUrl,
        ]);
    }

    public function expirePendingPastDue(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE pix_charges
             SET status = 'expired', updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :company_id
               AND status = 'pending'
               AND expires_at < CURRENT_TIMESTAMP"
        );
        $stmt->execute(['company_id' => $this->companyId()]);

        return $stmt->rowCount();
    }
}
