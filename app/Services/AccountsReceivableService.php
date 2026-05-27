<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Repositories\AccountsReceivableRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\PaymentRepository;
use PDO;

final class AccountsReceivableService
{
    public const DEFAULT_DUE_DAYS = 30;

    private AccountsReceivableRepository $accounts;

    public function __construct(?AccountsReceivableRepository $accounts = null)
    {
        $this->accounts = $accounts ?? new AccountsReceivableRepository();
    }

    public function createFromApprovedOrder(
        int $orderId,
        int $customerId,
        string $totalAmount,
        PDO $pdo,
        ?string $dueDate = null
    ): int
    {
        $repo = new AccountsReceivableRepository($pdo);
        $existing = $repo->findByOrderId($orderId);
        if ($existing !== null)
        {
            return $existing->id;
        }

        $dueDate ??= (new \DateTimeImmutable('today'))
            ->modify('+' . self::DEFAULT_DUE_DAYS . ' days')
            ->format('Y-m-d');

        return $repo->insert($orderId, $customerId, $totalAmount, $dueDate);
    }

    /**
     * Cancela conta aberta vinculada à venda (dentro da transação de cancelamento).
     *
     * @return array{ar_id: int, old_status: string}|null dados para auditoria pós-commit
     */
    public function cancelByOrderId(int $orderId, PDO $pdo): ?array
    {
        $repo = new AccountsReceivableRepository($pdo);
        $paymentRepo = new PaymentRepository($pdo);
        $ar = $repo->findByOrderIdForUpdate($orderId);
        if ($ar === null)
        {
            return null;
        }

        if (!in_array($ar->status, ['pending', 'partial'], true))
        {
            if ($ar->status === 'paid')
            {
                throw new ValidationException([
                    'finance' => 'Não é possível cancelar uma venda com conta a receber já quitada.',
                ]);
            }

            return null;
        }

        $paidTotal = $paymentRepo->sumByAccountsReceivableId($ar->id);
        if (\App\Helpers\Money::compare($paidTotal, '0.00') > 0)
        {
            throw new ValidationException([
                'finance' => 'Não é possível cancelar uma venda com recebimentos já registrados na conta.',
            ]);
        }

        $installmentRepo = new InstallmentRepository($pdo);
        if ($installmentRepo->countPaidByOrderId($orderId) > 0)
        {
            throw new ValidationException([
                'finance' => 'Não é possível cancelar uma venda com parcelas já pagas.',
            ]);
        }

        $repo->cancelOpenByOrderId($orderId);

        return ['ar_id' => $ar->id, 'old_status' => $ar->status];
    }

    /**
     * @return array{items: list<\App\Models\AccountsReceivable>, total: int}
     */
    public function search(
        int $page,
        int $perPage,
        ?string $status,
        ?int $customerId,
        ?string $dueFrom,
        ?string $dueTo,
        bool $overdueOnly
    ): array
    {
        if ($status !== null && $status !== '' && !in_array($status, \App\Models\AccountsReceivable::STATUSES, true))
        {
            $status = null;
        }

        return $this->accounts->paginateFiltered(
            $page,
            $perPage,
            $status,
            $customerId,
            $dueFrom,
            $dueTo,
            $overdueOnly
        );
    }

    /**
     * @return array{account: \App\Models\AccountsReceivable, payments: list<\App\Models\Payment>}|null
     */
    public function findDetail(int $id): ?array
    {
        $ar = $this->accounts->findById($id);
        if ($ar === null)
        {
            return null;
        }

        $payments = new PaymentRepository();
        $paymentList = $payments->findByAccountsReceivableId($id);

        return [
            'account' => $ar,
            'payments' => $paymentList,
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return array<string, string>
     */
    public static function filterQueryParams(array $filters): array
    {
        $out = [];
        foreach (['status', 'customer_id', 'due_from', 'due_to', 'overdue'] as $key)
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
}
