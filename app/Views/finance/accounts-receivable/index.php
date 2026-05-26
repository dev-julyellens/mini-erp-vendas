<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\AccountsReceivable;

/** @var callable(string):string $url */
/** @var list<AccountsReceivable> $accounts */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<\App\Models\Customer> $customers */
/** @var array{status: string, customer_id: ?int, due_from: string, due_to: string, overdue: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<string> $statuses */
/** @var array<string, string> $statusLabels */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$canReceive = Permission::can('financeiro', 'criar');
$displayStatus = in_array($filters['status'], $statuses, true) ? $filters['status'] : '';

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Contas a receber</h1>
        <div class="text-muted">Geradas automaticamente a cada venda aprovada</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
</div>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end filter-form">
        <div class="col-12 col-md-3">
            <label class="form-label" for="customer_id">Cliente</label>
            <select class="form-select" id="customer_id" name="customer_id">
                <option value="">Todos</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c->id ?>" <?= $filters['customer_id'] === $c->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" <?= $displayStatus === $s ? 'selected' : '' ?>>
                        <?= htmlspecialchars($statusLabels[$s] ?? $s, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="due_from">Venc. de</label>
            <input type="date" class="form-control" id="due_from" name="due_from"
                value="<?= htmlspecialchars($filters['due_from'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="due_to">Venc. até</label>
            <input type="date" class="form-control" id="due_to" name="due_to"
                value="<?= htmlspecialchars($filters['due_to'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-6 col-md-3">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="overdue" name="overdue" value="1"
                    <?= $filters['overdue'] === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="overdue">Somente vencidas</label>
            </div>
        </div>
        <div class="col-12 col-md-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="7">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Venda</th>
                    <th>Cliente</th>
                    <th>Valor</th>
                    <th class="d-none d-md-table-cell">Restante</th>
                    <th class="d-none d-lg-table-cell">Vencimento</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($accounts === []): ?>
                    <tr>
                        <td colspan="8" class="text-muted">Nenhuma conta encontrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $ar): ?>
                        <?php
                        $remaining = $ar->remaining_amount ?? $ar->amount;
                        $badge = AccountsReceivable::statusBadge($ar->status);
                        ?>
                        <tr class="<?= $ar->isOverdue() ? 'table-warning' : '' ?>">
                            <td><?= (int) $ar->id ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($url('orders/show?id=' . $ar->order_id), ENT_QUOTES, 'UTF-8') ?>">
                                    #<?= (int) $ar->order_id ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars((string) ($ar->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                R$ <?= htmlspecialchars($fmt($ar->amount), ENT_QUOTES, 'UTF-8') ?>
                                <div class="d-md-none text-muted small">Rest.: R$ <?= htmlspecialchars($fmt($remaining), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="d-none d-md-table-cell">R$ <?= htmlspecialchars($fmt($remaining), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="d-none d-lg-table-cell"><?= htmlspecialchars(DateHelper::toBrDate($ar->due_date), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(AccountsReceivable::statusLabel($ar->status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap gap-1 justify-content-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable/show?id=' . $ar->id), ENT_QUOTES, 'UTF-8') ?>">
                                        Detalhes
                                    </a>
                                    <?php if ($canReceive && $ar->canReceive()): ?>
                                        <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($url('finance/accounts-receivable/receive?id=' . $ar->id), ENT_QUOTES, 'UTF-8') ?>">
                                            Receber
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = 'finance/accounts-receivable';
    $query = $paginationQuery;
    require dirname(__DIR__, 2) . '/partials/pagination.php';
    ?>
</div>