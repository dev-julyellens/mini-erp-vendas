<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\Money;
use App\Models\Payment;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\CashFlowRepository;
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

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try
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

            $pdo->commit();

            Audit::record(
                'recebimento',
                'financeiro',
                $paymentId,
                ['status' => $ar->status, 'paid_total' => $paidTotal],
                [
                    'accounts_receivable_id' => $accountsReceivableId,
                    'order_id' => $ar->order_id,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'paid_at' => $paidAtNormalized,
                    'new_status' => $newStatus,
                ],
                $userId
            );

            return $paymentId;
        }
        catch (ValidationException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
        catch (PDOException $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
            }
            throw $e;
        }
        catch (\Throwable $e)
        {
            if ($pdo->inTransaction())
            {
                $pdo->rollBack();
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
