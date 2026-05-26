<?php

declare(strict_types=1);

use App\Helpers\Permission;

/** @var callable(string):string $url */
/** @var array{
 *   open_balance: string,
 *   overdue_count: int,
 *   pending_count: int,
 *   partial_count: int,
 *   paid_count: int,
 *   received_today: string,
 *   received_month: string,
 *   cash_balance: string,
 *   entries_month: string,
 *   exits_month: string,
 *   installment_overdue_count: int,
 *   installment_open_count: int
 * } $summary */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$canReceive = Permission::can('financeiro', 'criar');

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Financeiro</h1>
        <div class="text-muted">Contas a receber, recebimentos e fluxo de caixa</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-wallet2"></i> Contas a receber
        </a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('finance/cash-flow'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-arrow-left-right"></i> Fluxo de caixa
        </a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('finance/installments/overdue'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-exclamation-triangle"></i> Vencidas
        </a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('finance/installments/open'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-calendar2-check"></i> Parcelas abertas
        </a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('finance/installments/history'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-clock-history"></i> Histórico
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-tile stat-finance-open">
            <div class="text-white-50 small">A receber (aberto)</div>
            <h3>R$ <?= htmlspecialchars($fmt($summary['open_balance']), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="small text-white-50">Pendente + parcial</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile stat-finance-cash">
            <div class="text-white-50 small">Saldo em caixa</div>
            <h3>R$ <?= htmlspecialchars($fmt($summary['cash_balance']), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="small text-white-50">Entradas − saídas</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile stat-finance-month">
            <div class="text-white-50 small">Recebido no mês</div>
            <h3>R$ <?= htmlspecialchars($fmt($summary['received_month']), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="small text-white-50">Hoje: R$ <?= htmlspecialchars($fmt($summary['received_today']), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="fw-semibold mb-3">Situação das contas</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Pendentes</span>
                    <span class="badge text-bg-warning"><?= (int) $summary['pending_count'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Parciais</span>
                    <span class="badge text-bg-info"><?= (int) $summary['partial_count'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Recebidas</span>
                    <span class="badge text-bg-success"><?= (int) $summary['paid_count'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Vencidas (abertas)</span>
                    <span class="badge text-bg-danger"><?= (int) $summary['overdue_count'] ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Parcelas vencidas</span>
                    <span class="badge text-bg-danger"><?= (int) ($summary['installment_overdue_count'] ?? 0) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Parcelas abertas</span>
                    <span class="badge text-bg-warning"><?= (int) ($summary['installment_open_count'] ?? 0) ?></span>
                </li>
            </ul>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="fw-semibold mb-2">Movimentação do mês (fluxo)</div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="text-muted small">Entradas</div>
                    <div class="fs-4 fw-bold text-success">R$ <?= htmlspecialchars($fmt($summary['entries_month']), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Saídas</div>
                    <div class="fs-4 fw-bold text-danger">R$ <?= htmlspecialchars($fmt($summary['exits_month']), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <hr>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">
                    Ver contas a receber
                </a>
                <?php if ($canReceive): ?>
                    <span class="text-muted small align-self-center">Registre recebimentos pela ficha de cada conta.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>