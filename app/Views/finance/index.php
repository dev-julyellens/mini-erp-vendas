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

$title = 'Financeiro';
$subtitle = 'Contas a receber, recebimentos e fluxo de caixa';
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-wallet2"></i> Contas a receber</a>';
$actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance/cash-flow'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left-right"></i> Fluxo de caixa</a>';
$actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance/installments/overdue'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-exclamation-triangle"></i> Vencidas</a>';
$actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance/installments/open'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-calendar2-check"></i> Parcelas abertas</a>';
$actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance/installments/history'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-clock-history"></i> Histórico</a>';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="kpi-grid mb-4">
    <?php
    $label = 'A receber (aberto)';
    $value = 'R$ ' . htmlspecialchars($fmt($summary['open_balance']), ENT_QUOTES, 'UTF-8');
    $hint = 'Pendente + parcial';
    $variant = 'stat-finance-open';
    $href = $url('finance/accounts-receivable');
    require dirname(__DIR__) . '/components/kpi-card.php';

    $label = 'Saldo em caixa';
    $value = 'R$ ' . htmlspecialchars($fmt($summary['cash_balance']), ENT_QUOTES, 'UTF-8');
    $hint = 'Entradas − saídas';
    $variant = 'stat-finance-cash';
    $href = $url('finance/cash-flow');
    require dirname(__DIR__) . '/components/kpi-card.php';

    $label = 'Recebido no mês';
    $value = 'R$ ' . htmlspecialchars($fmt($summary['received_month']), ENT_QUOTES, 'UTF-8');
    $hint = 'Hoje: R$ ' . $fmt($summary['received_today']);
    $variant = 'stat-finance-month';
    $href = null;
    require dirname(__DIR__) . '/components/kpi-card.php';
    ?>
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