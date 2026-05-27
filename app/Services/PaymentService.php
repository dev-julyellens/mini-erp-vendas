<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\Money;
use App\Models\Payment;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\CashFlowRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\PaymentRepository;
use PDO;
use PDOException;

final class PaymentService
{
    public function receive(
        int $accountsReceivableId,
        string $amount,
        string $paymentMethod,
        ?string $paidAt = null,
        ?string $notes = null,
        ?int $userId = null
    ): int
    {
        $userId = $userId ?? Auth::id();
        if ($userId === null)
        {
            throw new ValidationException(['auth' => 'É necessário estar autenticado para registrar um recebimento.']);
        }

        $amount = Money::normalizeDecimal($amount);

        $errors = $this->validateInput($amount, $paymentMethod, $paidAt);
        if ($errors !== [])
        {
            throw new ValidationException($errors);
        }

        $paidAtNormalized = $paidAt ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try
        {
            $tx = Database::transaction(function (PDO $pdo) use (
                $accountsReceivableId,
                $amount,
                $paymentMethod,
                $paidAtNormalized,
                $userId,
                $notes
            ): array
            {
                $accountRepo = new AccountsReceivableRepository($pdo);
                $paymentRepo = new PaymentRepository($pdo);
                $cashRepo = new CashFlowRepository($pdo);

                $ar = $accountRepo->findByIdForUpdate($accountsReceivableId);
                if ($ar === null)
                {
                    throw new ValidationException(['accounts_receivable_id' => 'Conta a receber não encontrada.']);
                }

                if (!$ar->canReceive())
                {
                    throw new ValidationException(['status' => 'Esta conta não aceita novos recebimentos.']);
                }

                $installmentRepo = new InstallmentRepository($pdo);
                if ($installmentRepo->countByOrderId($ar->order_id) > 0)
                {
                    throw new ValidationException([
                        'finance' => 'Esta venda possui parcelamento. Registre o recebimento pela baixa de cada parcela.',
                    ]);
                }

                $paidTotal = $paymentRepo->sumByAccountsReceivableId($accountsReceivableId);
                $remaining = Money::sub($ar->amount, $paidTotal);

                if (Money::compare($amount, '0.00') <= 0)
                {
                    throw new ValidationException(['amount' => 'O valor deve ser maior que zero.']);
                }

                if (Money::compare($amount, $remaining) > 0)
                {
                    throw new ValidationException([
                        'amount' => sprintf('Valor excede o saldo restante (R$ %s).', $remaining),
                    ]);
                }

                $paymentId = $paymentRepo->insert(
                    $accountsReceivableId,
                    $amount,
                    $paymentMethod,
                    $paidAtNormalized,
                    $userId,
                    $notes
                );

                $newPaidTotal = Money::add($paidTotal, $amount);
                $newStatus = Money::compare($newPaidTotal, $ar->amount) >= 0 ? 'paid' : 'partial';
                $accountRepo->updateStatus($accountsReceivableId, $newStatus);

                if ($newStatus === 'paid')
                {
                    (new OrderService())->syncPaymentStatusFromReceivable($ar->order_id, $pdo);
                }

                $cashRepo->insert(
                    'entrada',
                    $amount,
                    $paymentMethod,
                    'payment',
                    $paymentId,
                    sprintf('Recebimento conta #%d (venda #%d)', $accountsReceivableId, $ar->order_id),
                    $paidAtNormalized,
                    $userId
                );

                return [
                    'payment_id' => $paymentId,
                    'order_id' => $ar->order_id,
                    'old_status' => $ar->status,
                    'paid_total' => $paidTotal,
                    'new_status' => $newStatus,
                ];
            });

            $paymentId = $tx['payment_id'];

            Audit::record(
                'recebimento',
                'financeiro',
                $paymentId,
                ['status' => $tx['old_status'], 'paid_total' => $tx['paid_total']],
                [
                    'accounts_receivable_id' => $accountsReceivableId,
                    'order_id' => $tx['order_id'],
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'paid_at' => $paidAtNormalized,
                    'new_status' => $tx['new_status'],
                ],
                $userId
            );

            Logger::info('Recebimento registrado.', [
                'payment_id' => $paymentId,
                'accounts_receivable_id' => $accountsReceivableId,
                'amount' => $amount,
            ]);

            return $paymentId;
        }
        catch (\Throwable $e)
        {
            if (!$e instanceof ValidationException)
            {
                Logger::exception($e, 'Falha ao registrar recebimento.', [
                    'accounts_receivable_id' => $accountsReceivableId,
                ]);
            }

            throw $e;
        }
    }

    /**
     * @return array<string, string>
     */
    private function validateInput(string $amount, string $paymentMethod, ?string $paidAt): array
    {
        $errors = [];

        if (!Money::validatePositive($amount))
        {
            $errors['amount'] = 'Informe um valor válido com até 2 casas decimais.';
        }

        if (!in_array($paymentMethod, Payment::METHODS, true))
        {
            $errors['payment_method'] = 'Selecione uma forma de pagamento válida.';
        }

        if ($paidAt !== null && $paidAt !== '')
        {
            try
            {
                new \DateTimeImmutable($paidAt);
            }
            catch (\Throwable $e)
            {
                $errors['paid_at'] = 'Data de pagamento inválida.';
            }
        }

        return $errors;
    }
}
