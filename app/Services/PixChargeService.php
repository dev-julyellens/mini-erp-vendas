<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\CompanyContext;
use App\Helpers\Money;
use App\Helpers\PixConfig;
use App\Integrations\Payment\GatewayFactory;
use App\Integrations\Payment\PixChargeRequest;
use App\Models\PixCharge;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\PixChargeRepository;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

final class PixChargeService
{
    public function createForAccountsReceivable(
        int $accountsReceivableId,
        string $amount,
        ?int $userId = null
    ): int
    {
        if (!PixConfig::isEnabled())
        {
            throw new ValidationException(['pix' => 'Integração PIX desabilitada.']);
        }

        $userId = $userId ?? Auth::id();
        if ($userId === null)
        {
            throw new ValidationException(['auth' => 'É necessário estar autenticado.']);
        }

        $amount = Money::normalizeDecimal($amount);
        if (!Money::validatePositive($amount))
        {
            throw new ValidationException(['amount' => 'Informe um valor válido.']);
        }

        $arRepo = new AccountsReceivableRepository();
        $ar = $arRepo->findById($accountsReceivableId);
        if ($ar === null)
        {
            throw new ValidationException(['accounts_receivable_id' => 'Conta a receber não encontrada.']);
        }

        if (!$ar->canReceive())
        {
            throw new ValidationException(['status' => 'Esta conta não aceita cobrança PIX.']);
        }

        if ($ar->has_installments)
        {
            throw new ValidationException([
                'finance' => 'Venda parcelada: gere o PIX pela baixa de cada parcela.',
            ]);
        }

        $remaining = $ar->remaining_amount ?? Money::sub($ar->amount, '0.00');
        if (Money::compare($amount, $remaining) > 0)
        {
            throw new ValidationException([
                'amount' => sprintf('Valor excede o saldo restante (R$ %s).', $remaining),
            ]);
        }

        $existing = (new PixChargeRepository())->findPendingByAccountsReceivableId($accountsReceivableId);
        if ($existing !== null)
        {
            return $existing->id;
        }

        return $this->createCharge(
            $accountsReceivableId,
            null,
            $amount,
            sprintf('Conta #%d venda #%d', $accountsReceivableId, $ar->order_id),
            $userId
        );
    }

    public function createForInstallment(int $installmentId, ?int $userId = null): int
    {
        if (!PixConfig::isEnabled())
        {
            throw new ValidationException(['pix' => 'Integração PIX desabilitada.']);
        }

        $userId = $userId ?? Auth::id();
        if ($userId === null)
        {
            throw new ValidationException(['auth' => 'É necessário estar autenticado.']);
        }

        $installmentRepo = new InstallmentRepository();
        $installmentRepo->refreshOverdueStatuses();
        $installment = $installmentRepo->findById($installmentId);
        if ($installment === null)
        {
            throw new ValidationException(['installment_id' => 'Parcela não encontrada.']);
        }

        if (!$installment->canPay())
        {
            throw new ValidationException(['status' => 'Esta parcela não aceita cobrança PIX.']);
        }

        $arRepo = new AccountsReceivableRepository();
        $ar = $arRepo->findByOrderId($installment->order_id);
        if ($ar === null)
        {
            throw new ValidationException(['order_id' => 'Conta a receber não encontrada.']);
        }

        $existing = (new PixChargeRepository())->findPendingByInstallmentId($installmentId);
        if ($existing !== null)
        {
            return $existing->id;
        }

        return $this->createCharge(
            $ar->id,
            $installmentId,
            $installment->amount,
            sprintf(
                'Parcela %d venda #%d',
                $installment->installment_number,
                $installment->order_id
            ),
            $userId
        );
    }

    public function findById(int $id): ?PixCharge
    {
        $repo = new PixChargeRepository();
        $repo->expirePendingPastDue();

        $charge = $repo->findById($id);
        if ($charge === null)
        {
            return null;
        }

        // Compatibilidade: cobranças mock antigas podem ter URL externa (bloqueada por CSP)
        // ou não ter imagem persistida.
        if (
            $charge->gateway === 'mock'
            && $charge->qr_payload !== null
            && $charge->qr_payload !== ''
            && (
                $charge->qr_image_url === null
                || $charge->qr_image_url === ''
                || str_starts_with($charge->qr_image_url, 'http')
            )
        )
        {
            $dataUri = $this->buildQrDataUri($charge->qr_payload, 240);
            if ($dataUri !== null)
            {
                $charge->qr_image_url = $dataUri;
                // Persiste para evitar inconsistências e cache de HTML/CDN.
                $repo->updateQrImageUrlById($charge->id, $dataUri);
            }
        }

        return $charge;
    }

    /**
     * @return array{status: string, paid: bool, charge_id: int, redirect?: string}
     */
    public function refreshStatus(int $chargeId): array
    {
        $repo = new PixChargeRepository();
        $repo->expirePendingPastDue();
        $charge = $repo->findById($chargeId);
        if ($charge === null)
        {
            throw new ValidationException(['id' => 'Cobrança PIX não encontrada.']);
        }

        if ($charge->payment_id !== null || $charge->status === 'paid')
        {
            return [
                'status' => 'paid',
                'paid' => true,
                'charge_id' => $chargeId,
                'redirect' => 'finance/pix/receipt?id=' . $chargeId,
            ];
        }

        if ($charge->status !== 'pending')
        {
            return [
                'status' => $charge->status,
                'paid' => false,
                'charge_id' => $chargeId,
            ];
        }

        $gateway = GatewayFactory::make($charge->gateway);
        $remote = $gateway->fetchStatus($charge->external_id);
        if ($remote->status !== 'pending' && $remote->status !== $charge->status)
        {
            $this->applyRemoteStatus($charge, $remote->status, $remote->paidAt, $remote->receiptReference);
            $charge = $repo->findById($chargeId) ?? $charge;
        }

        if ($charge->status === 'paid' && $charge->payment_id === null)
        {
            $this->reconcile($charge->id);
        }

        $paid = $charge->status === 'paid';

        return [
            'status' => $charge->status,
            'paid' => $paid,
            'charge_id' => $chargeId,
            'redirect' => $paid ? 'finance/pix/receipt?id=' . $chargeId : null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleWebhook(string $gatewayName, array $payload, string $rawBody, ?string $signature): void
    {
        $gateway = GatewayFactory::make($gatewayName);
        if (!$gateway->verifyWebhookSignature($rawBody, $signature))
        {
            throw new ValidationException(['webhook' => 'Assinatura inválida.']);
        }

        $result = $gateway->parseWebhook($payload);
        if ($result->externalId === '')
        {
            throw new ValidationException(['webhook' => 'Identificador da cobrança ausente.']);
        }

        $repo = new PixChargeRepository();
        $charge = $repo->findByGatewayExternalIdGlobal($gatewayName, $result->externalId);
        if ($charge === null)
        {
            throw new ValidationException(['webhook' => 'Cobrança não encontrada.']);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $this->applyRemoteStatus(
            $charge,
            $result->status,
            $result->paidAt,
            $result->receiptReference,
            $json !== false ? $json : null,
            false
        );

        if ($result->status === 'paid')
        {
            CompanyContext::setJwtCompanyId($charge->company_id);
            try
            {
                $this->reconcile($charge->id);
            }
            finally
            {
                CompanyContext::clearJwt();
            }
        }
    }

    /**
     * Simula confirmação de pagamento (apenas gateway mock, ambiente de testes).
     */
    public function simulateMockPayment(int $chargeId): void
    {
        $charge = (new PixChargeRepository())->findById($chargeId);
        if ($charge === null)
        {
            throw new ValidationException(['id' => 'Cobrança PIX não encontrada.']);
        }

        if ($charge->gateway !== 'mock')
        {
            throw new ValidationException(['gateway' => 'Simulação disponível apenas para o gateway mock.']);
        }

        if (!$charge->isPending())
        {
            throw new ValidationException(['status' => 'Cobrança não está pendente.']);
        }

        $payload = [
            'external_id' => $charge->external_id,
            'status' => 'paid',
            'paid_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'receipt_reference' => 'SIM-' . $charge->external_id,
        ];

        $this->handleWebhook('mock', $payload, json_encode($payload) ?: '{}', null);
    }

    public function reconcile(int $chargeId): int
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try
        {
            $repo = new PixChargeRepository($pdo);
            $charge = $repo->findByIdForUpdate($chargeId);
            if ($charge === null)
            {
                throw new ValidationException(['id' => 'Cobrança PIX não encontrada.']);
            }

            if ($charge->status !== 'paid')
            {
                throw new ValidationException(['status' => 'Cobrança ainda não está paga.']);
            }

            if ($charge->payment_id !== null)
            {
                $pdo->commit();

                return $charge->payment_id;
            }

            $pdo->commit();
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }

        $charge = (new PixChargeRepository())->findById($chargeId);
        if ($charge === null || $charge->payment_id !== null)
        {
            return $charge?->payment_id ?? 0;
        }

        $notes = $this->buildPaymentNotes($charge);
        $paidAt = $charge->paid_at ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($charge->installment_id !== null)
        {
            $paymentId = (new InstallmentService())->pay(
                $charge->installment_id,
                'pix',
                $paidAt,
                $notes,
                $charge->created_by
            );
        }
        else
        {
            $paymentId = (new PaymentService())->receive(
                $charge->accounts_receivable_id,
                $charge->amount,
                'pix',
                $paidAt,
                $notes,
                $charge->created_by
            );
        }

        (new PixChargeRepository())->linkPayment($chargeId, $paymentId);

        Audit::record(
            'pix_conciliacao',
            'financeiro',
            $chargeId,
            null,
            [
                'payment_id' => $paymentId,
                'external_id' => $charge->external_id,
                'gateway' => $charge->gateway,
                'amount' => $charge->amount,
            ],
            $charge->created_by
        );

        return $paymentId;
    }

    private function createCharge(
        int $accountsReceivableId,
        ?int $installmentId,
        string $amount,
        string $description,
        int $userId
    ): int
    {
        $companyId = CompanyContext::requireId();
        $gateway = GatewayFactory::make();
        $externalId = $this->generateExternalId();
        $ttl = PixConfig::chargeTtlSeconds();

        $response = $gateway->createCharge(new PixChargeRequest(
            $externalId,
            $amount,
            $description,
            $ttl,
        ));

        $repo = new PixChargeRepository();
        $chargeId = $repo->insert(
            $companyId,
            $accountsReceivableId,
            $installmentId,
            $gateway->name(),
            $response->externalId,
            $amount,
            $response->status,
            $response->qrPayload,
            $response->qrImageUrl,
            $response->expiresAt,
            $userId
        );

        Audit::record(
            'pix_cobranca',
            'financeiro',
            $chargeId,
            null,
            [
                'accounts_receivable_id' => $accountsReceivableId,
                'installment_id' => $installmentId,
                'amount' => $amount,
                'gateway' => $gateway->name(),
                'external_id' => $response->externalId,
            ],
            $userId
        );

        return $chargeId;
    }

    private function applyRemoteStatus(
        PixCharge $charge,
        string $status,
        ?string $paidAt,
        ?string $receiptReference,
        ?string $rawWebhook = null,
        bool $scoped = true
    ): void
    {
        if (!in_array($status, PixCharge::STATUSES, true))
        {
            return;
        }

        $repo = new PixChargeRepository();
        if ($scoped)
        {
            $repo->updateStatus($charge->id, $status, $paidAt, $receiptReference, $rawWebhook);
        }
        else
        {
            $repo->updateStatusById($charge->id, $status, $paidAt, $receiptReference, $rawWebhook);
        }
    }

    private function buildPaymentNotes(PixCharge $charge): string
    {
        $parts = ['PIX automático', 'TX: ' . $charge->external_id];
        if ($charge->receipt_reference !== null && $charge->receipt_reference !== '')
        {
            $parts[] = 'Comprovante: ' . $charge->receipt_reference;
        }

        return implode(' | ', $parts);
    }

    private function generateExternalId(): string
    {
        return 'ERP' . strtoupper(bin2hex(random_bytes(8)));
    }

    private function buildQrDataUri(string $payload, int $size): ?string
    {
        try
        {
            $qrCode = new QrCode(
                data: $payload,
                size: max(120, $size),
                margin: 0
            );
            $png = (new PngWriter())->write($qrCode)->getString();

            return 'data:image/png;base64,' . base64_encode($png);
        }
        catch (\Throwable $e)
        {
            return null;
        }
    }
}
