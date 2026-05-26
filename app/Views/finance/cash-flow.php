<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
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

$title = 'Fluxo de caixa';
$subtitle = 'Entradas geradas por recebimentos e demais movimentações';
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => 'Fluxo de caixa'],
];
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-speedometer2"></i> Dashboard financeiro</a>';
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_type">Tipo</label>
    <select class="form-select" id="filter_type" name="type">
        <option value="">Todos</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $displayType === $t ? 'selected' : '' ?>>
                <?= htmlspecialchars($typeLabels[$t] ?? $t, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_date_from">De</label>
    <input type="date" class="form-control" id="filter_date_from" name="date_from"
        value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_date_to">Até</label>
    <input type="date" class="form-control" id="filter_date_to" name="date_to"
        value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('finance/cash-flow');
$filterClearHref = $url('finance/cash-flow');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable">
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
                            <td><?= htmlspecialchars(DateHelper::toBrDateTime($m->occurred_at), ENT_QUOTES, 'UTF-8') ?></td>
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
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>