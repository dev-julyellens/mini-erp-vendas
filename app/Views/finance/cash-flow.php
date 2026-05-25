<?php

declare(strict_types=1);

use App\Models\CashFlow;
use App\Models\Payment;

/** @var callable(string):string $url */
/** @var list<\App\Models\CashFlow> $movements */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array{type: string, date_from: string, date_to: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<string> $types */
/** @var array<string, string> $typeLabels */

$displayType = in_array($filters['type'], $types, true) ? $filters['type'] : '';
$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Fluxo de caixa</h1>
        <div class="text-muted">Entradas geradas por recebimentos e demais movimentações</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-speedometer2"></i> Dashboard financeiro
    </a>
</div>

<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('finance/cash-flow'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label" for="type">Tipo</label>
            <select class="form-select" id="type" name="type">
                <option value="">Todos</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $displayType === $t ? 'selected' : '' ?>>
                        <?= htmlspecialchars($typeLabels[$t] ?? $t, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="date_from">De</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="date_to">Até</label>
            <input type="date" class="form-control" id="date_to" name="date_to"
                value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/cash-flow'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Forma</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movements === []): ?>
                    <tr>
                        <td colspan="5" class="text-muted">Nenhum lançamento encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m->occurred_at)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= $m->type === 'entrada' ? 'success' : 'danger' ?>">
                                    <?= htmlspecialchars(CashFlow::typeLabel($m->type), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="fw-semibold">R$ <?= htmlspecialchars($fmt($m->amount), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= $m->payment_method !== null
                                    ? htmlspecialchars(Payment::methodLabel($m->payment_method), ENT_QUOTES, 'UTF-8')
                                    : '—' ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars((string) ($m->description ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = 'finance/cash-flow';
    $query = $paginationQuery;
    require dirname(__DIR__, 2) . '/partials/pagination.php';
    ?>
</div>