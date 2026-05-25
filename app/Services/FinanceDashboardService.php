<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AccountsReceivableRepository;
use App\Repositories\CashFlowRepository;
use App\Repositories\PaymentRepository;

final class FinanceDashboardService
{
    private AccountsReceivableRepository $accounts;
    private PaymentRepository $payments;
    private CashFlowRepository $cashFlow;

    public function __construct(
        ?AccountsReceivableRepository $accounts = null,
        ?PaymentRepository $payments = null,
        ?CashFlowRepository $cashFlow = null
    )
    {
        $this->accounts = $accounts ?? new AccountsReceivableRepository();
        $this->payments = $payments ?? new PaymentRepository();
        $this->cashFlow = $cashFlow ?? new CashFlowRepository();
    }

    /**
     * @return array{
     *   open_balance: string,
     *   overdue_count: int,
     *   pending_count: int,
     *   partial_count: int,
     *   paid_count: int,
     *   received_today: string,
     *   received_month: string,
     *   cash_balance: string,
     *   entries_month: string,
     *   exits_month: string
     * }
     */
    public function summary(): array
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $monthStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $monthEnd = (new \DateTimeImmutable('last day of this month'))->format('Y-m-d');

        return [
            'open_balance' => $this->accounts->sumRemainingOpen(),
            'overdue_count' => $this->accounts->countOverdueOpen(),
            'pending_count' => $this->accounts->countByStatus('pending'),
            'partial_count' => $this->accounts->countByStatus('partial'),
            'paid_count' => $this->accounts->countByStatus('paid'),
            'received_today' => $this->payments->sumReceivedBetween($today, $today),
            'received_month' => $this->payments->sumReceivedBetween($monthStart, $monthEnd),
            'cash_balance' => $this->cashFlow->netBalance(),
            'entries_month' => $this->cashFlow->sumByTypeBetween('entrada', $monthStart, $monthEnd),
            'exits_month' => $this->cashFlow->sumByTypeBetween('saida', $monthStart, $monthEnd),
        ];
    }
}
