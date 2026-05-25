<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\AccountsReceivable;
use App\Models\Payment;

/** @var callable(string):string $url */
/** @var AccountsReceivable $account */
/** @var list<Payment> $payments */
/** @var array<string, string> $methodLabels */
$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$remaining = $account->remaining_amount ?? $account->amount;
$canReceive = Permission::can('financeiro', 'criar') && $account->canReceive();
$canPayInstallments = Permission::can('financeiro', 'criar')
    && $account->has_installments
    && in_array($account->status, ['pending', 'partial'], true);

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Conta a receber #<?= (int) $account->id ?></h1>
        <div class="text-muted">Venda #<?= (int) $account->order_id ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($canReceive): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('finance/accounts-receivable/receive?id=' . $account->id), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-cash-coin"></i> Registrar recebimento
            </a>
        <?php elseif ($canPayInstallments): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('finance/installments/open'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-calendar2-check"></i> Baixar parcelas
            </a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="text-muted small">Cliente</div>
            <div class="fs-5 fw-semibold"><?= htmlspecialchars((string) ($account->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <hr>
            <div class="text-muted small">Status</div>
            <div class="mb-2">
                <span class="badge text-bg-<?= htmlspecialchars(AccountsReceivable::statusBadge($account->status), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(AccountsReceivable::statusLabel($account->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if ($account->isOverdue()): ?>
                    <span class="badge text-bg-danger">Vencida</span>
                <?php endif; ?>
            </div>
            <hr>
            <div class="row g-2">
                <div class="col-6">
                    <div class="text-muted small">Valor total</div>
                    <div class="fw-semibold">R$ <?= htmlspecialchars($fmt($account->amount), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Restante</div>
                    <div class="fw-bold">R$ <?= htmlspecialchars($fmt($remaining), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <hr>
            <div class="text-muted small">Vencimento</div>
            <div><?= htmlspecialchars(date('d/m/Y', strtotime($account->due_date)), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small mt-2">Criada em <?= htmlspecialchars(DateHelper::toBrDateTime($account->created_at), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft p-3 p-md-4">
            <div class="fw-semibold mb-2">Recebimentos</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Forma</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments === []): ?>
                            <tr>
                                <td colspan="4" class="text-muted">Nenhum recebimento registrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars(DateHelper::toBrDateTime($p->paid_at), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>R$ <?= htmlspecialchars($fmt($p->amount), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(Payment::methodLabel($p->payment_method), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($p->received_by_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>