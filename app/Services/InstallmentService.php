<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Helpers\Auth;
use App\Helpers\Money;
use App\Models\Installment;
use App\Models\Payment;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\CashFlowRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\PaymentRepository;
use PDO;
use PDOException;

final class InstallmentService
{
    public const MIN_COUNT = 2;
    public const MAX_COUNT = 24;
    public const DAYS_BETWEEN = 30;

    private InstallmentRepository $installments;

    public function __construct(?InstallmentRepository $installments = null)
    {
        $this->installments = $installments ?? new InstallmentRepository();
    }

    /**
     * Gera parcelas na mesma transação da venda (quando installmentCount >= 2).
     *
     * @return list<array{installment_number: int, amount: string, due_date: string}>
     */
    public function generateForOrder(int $orderId, string $totalAmount, int $installmentCount, PDO $pdo): array
    {
        $this->assertValidCount($installmentCount);

        $repo = new InstallmentRepository($pdo);
        if ($repo->countByOrderId($orderId) > 0)
        {
            return [];
        }

        $amounts = Money::splitAmount($totalAmount, $installmentCount);
        $rows = [];
        $today = new \DateTimeImmutable('today');

        for ($n = 1; $n <= $installmentCount; $n++)
        {
            $dueDate = $today
                ->modify('+' . ($n * self::DAYS_BETWEEN) . ' days')
                ->format('Y-m-d');

            $rows[] = [
                'installment_number' => $n,
                'amount' => $amounts[$n - 1],
                'due_date' => $dueDate,
            ];
        }

        $repo->insertBatch($orderId, $rows);

        return $rows;
    }

    public function firstDueDate(int $installmentCount): string
    {
        return (new \DateTimeImmutable('today'))
            ->modify('+' . self::DAYS_BETWEEN . ' days')
            ->format('Y-m-d');
    }

    /**
     * Baixa manual de uma parcela (registra pagamento na conta a receber vinculada).
     */
    public function pay(
        int $installmentId,
        string $paymentMethod,
        ?string $paidAt = null,
        ?string $notes = null,
        ?int $userId = null
    ): int
    {
        $userId = $userId ?? Auth::id();
        if ($userId === null)
        {
            throw new ValidationException(['auth' => 'É necessário estar autenticado para registrar o pagamento.']);
        }

        if (!in_array($paymentMethod, Payment::METHODS, true))
        {
            throw new ValidationException(['payment_method' => 'Selecione uma forma de pagamento válida.']);
        }

        $paidAtNormalized = $paidAt ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        if ($paidAt !== null && $paidAt !== '')
        {
            try
            {
                $paidAtNormalized = (new \DateTimeImmutable($paidAt))->format('Y-m-d H:i:s');
            }
            catch (\Throwable $e)
            {
                throw new ValidationException(['paid_at' => 'Data de pagamento inválida.']);
            }
        }

        try
        {
            $tx = Database::transaction(function (PDO $pdo) use (
                $installmentId,
                $paymentMethod,
                $paidAtNormalized,
                $userId,
                $notes
            ): array
            {
                $installmentRepo = new InstallmentRepository($pdo);
                $installmentRepo->refreshOverdueStatuses();

                $installment = $installmentRepo->findByIdForUpdate($installmentId);
                if ($installment === null)
                {
                    throw new ValidationException(['installment_id' => 'Parcela não encontrada.']);
                }

                if (!$installment->canPay())
                {
                    throw new ValidationException(['status' => 'Esta parcela não aceita baixa.']);
                }

                $accountRepo = new AccountsReceivableRepository($pdo);
                $paymentRepo = new PaymentRepository($pdo);
                $cashRepo = new CashFlowRepository($pdo);

                $ar = $accountRepo->findByOrderIdForUpdate($installment->order_id);
                if ($ar === null)
                {
                    throw new ValidationException(['order_id' => 'Conta a receber da venda não encontrada.']);
                }

                if (!$ar->canReceive())
                {
                    throw new ValidationException(['status' => 'A conta a receber vinculada não aceita recebimentos.']);
                }

                $paidTotal = $paymentRepo->sumByAccountsReceivableId($ar->id);
                $remaining = Money::sub($ar->amount, $paidTotal);

                if (Money::compare($installment->amount, $remaining) > 0)
                {
                    throw new ValidationException([
                        'amount' => sprintf('Valor da parcela excede o saldo da conta (R$ %s).', $remaining),
                    ]);
                }

                $paymentId = $paymentRepo->insert(
                    $ar->id,
                    $installment->amount,
                    $paymentMethod,
                    $paidAtNormalized,
                    $userId,
                    $notes !== null && $notes !== ''
                        ? 'Parcela ' . $installment->installment_number . ': ' . $notes
                        : 'Parcela ' . $installment->installment_number . ' da venda #' . $installment->order_id
                );

                $installmentRepo->markPaid($installmentId, $paidAtNormalized);

                $newPaidTotal = Money::add($paidTotal, $installment->amount);
                $newStatus = Money::compare($newPaidTotal, $ar->amount) >= 0 ? 'paid' : 'partial';
                $accountRepo->updateStatus($ar->id, $newStatus);

                if ($newStatus === 'paid')
                {
                    (new OrderService())->syncPaymentStatusFromReceivable($installment->order_id, $pdo);
                }

                $cashRepo->insert(
                    'entrada',
                    $installment->amount,
                    $paymentMethod,
                    'payment',
                    $paymentId,
                    sprintf(
                        'Parcela %d/%d venda #%d',
                        $installment->installment_number,
                        $installmentRepo->countByOrderId($installment->order_id),
                        $installment->order_id
                    ),
                    $paidAtNormalized,
                    $userId
                );

                return [
                    'payment_id' => $paymentId,
                    'installment' => $installment,
                    'ar_id' => $ar->id,
                    'new_status' => $newStatus,
                ];
            });

            Audit::record(
                'recebimento_parcela',
                'financeiro',
                $installmentId,
                ['status' => $tx['installment']->status, 'order_id' => $tx['installment']->order_id],
                [
                    'installment_id' => $installmentId,
                    'order_id' => $tx['installment']->order_id,
                    'installment_number' => $tx['installment']->installment_number,
                    'amount' => $tx['installment']->amount,
                    'payment_method' => $paymentMethod,
                    'paid_at' => $paidAtNormalized,
                    'accounts_receivable_id' => $tx['ar_id'],
                    'new_ar_status' => $tx['new_status'],
                    'payment_id' => $tx['payment_id'],
                ],
                $userId
            );

            Logger::info('Parcela recebida.', [
                'installment_id' => $installmentId,
                'payment_id' => $tx['payment_id'],
            ]);

            return $tx['payment_id'];
        }
        catch (\Throwable $e)
        {
            if (!$e instanceof ValidationException)
            {
                Logger::exception($e, 'Falha ao baixar parcela.', ['installment_id' => $installmentId]);
            }

            throw $e;
        }
    }

    /**
     * Cancela parcelas abertas da venda (dentro da transação de cancelamento).
     *
     * @return array{count: int}|null dados para auditoria
     */
    public function cancelByOrderId(int $orderId, PDO $pdo): ?array
    {
        $repo = new InstallmentRepository($pdo);
        if ($repo->countByOrderId($orderId) === 0)
        {
            return null;
        }

        if ($repo->countPaidByOrderId($orderId) > 0)
        {
            throw new ValidationException([
                'finance' => 'Não é possível cancelar uma venda com parcelas já pagas.',
            ]);
        }

        $repo->cancelOpenByOrderId($orderId);

        return ['count' => $repo->countByOrderId($orderId)];
    }

    public function orderHasInstallments(int $orderId): bool
    {
        return $this->installments->countByOrderId($orderId) > 0;
    }

    /**
     * @return array{items: list<Installment>, total: int}
     */
    public function search(
        int $page,
        int $perPage,
        string $listType,
        ?int $customerId,
        ?string $dueFrom,
        ?string $dueTo
    ): array
    {
        if (!in_array($listType, ['overdue', 'open', 'history'], true))
        {
            $listType = 'open';
        }

        $this->refreshOverdueStatuses();

        return $this->installments->paginateFiltered(
            $page,
            $perPage,
            $listType,
            $customerId,
            $dueFrom,
            $dueTo
        );
    }

    public function refreshOverdueStatuses(): void
    {
        $this->installments->refreshOverdueStatuses();
    }

    /**
     * @return list<Installment>
     */
    public function findByOrderId(int $orderId): array
    {
        $this->installments->refreshOverdueStatuses();

        return $this->installments->findByOrderId($orderId);
    }

    public function findById(int $id): ?Installment
    {
        $this->installments->refreshOverdueStatuses();

        return $this->installments->findById($id);
    }

    /**
     * @param array<string, string> $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $out = [];
        foreach (['customer_id', 'due_from', 'due_to'] as $key)
        {
            if (!isset($filters[$key]))
            {
                continue;
            }
            $val = $filters[$key];
            if ($val === '' || $val === null)
            {
                continue;
            }
            $out[$key] = (string) $val;
        }

        return $out;
    }

    public function assertValidCount(int $count): void
    {
        if ($count < self::MIN_COUNT || $count > self::MAX_COUNT)
        {
            throw new ValidationException([
                'installment_count' => sprintf(
                    'Informe entre %d e %d parcelas.',
                    self::MIN_COUNT,
                    self::MAX_COUNT
                ),
            ]);
        }
    }

    public static function normalizeInstallmentCount(mixed $value): int
    {
        $count = (int) $value;
        if ($count < 1)
        {
            return 1;
        }

        return min($count, self::MAX_COUNT);
    }
}
