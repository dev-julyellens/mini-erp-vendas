<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\CashFlow;
use App\Models\Payment;

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $reportPath */
/** @var list<array<string, mixed>> $items */
/** @var array{entrada: string, saida: string, saldo: string} $summary */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<string> $types */
/** @var array<string, string> $typeLabels */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$displayType = in_array($filters['type'] ?? '', $types, true) ? $filters['type'] : '';

require dirname(__DIR__) . '/reports/_report-header.php';

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
        value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_date_to">Até</label>
    <input type="date" class="form-control" id="filter_date_to" name="date_to"
        value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url($reportPath);
$filterClearHref = $url($reportPath);
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="kpi-grid mb-3">
    <?php
    $label = 'Entradas';
    $value = 'R$ ' . htmlspecialchars($fmt((string) $summary['entrada']), ENT_QUOTES, 'UTF-8');
    $hint = 'Total de entradas no período';
    $variant = 'stat-finance-cash';
    $href = null;
    require dirname(__DIR__) . '/components/kpi-card.php';

    $label = 'Saídas';
    $value = 'R$ ' . htmlspecialchars($fmt((string) $summary['saida']), ENT_QUOTES, 'UTF-8');
    $hint = 'Total de saídas no período';
    $variant = 'stat-finance-open';
    require dirname(__DIR__) . '/components/kpi-card.php';

    $label = 'Saldo do período';
    $value = 'R$ ' . htmlspecialchars($fmt((string) $summary['saldo']), ENT_QUOTES, 'UTF-8');
    $hint = 'Entradas − saídas';
    $variant = 'stat-finance-month';
    require dirname(__DIR__) . '/components/kpi-card.php';
    ?>
</div>

<?php require __DIR__ . '/partials/report-charts.php'; ?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th class="text-end">Valor (R$)</th>
                    <th>Forma</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="5" class="text-muted">Nenhum lançamento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(DateHelper::toBrDateTime((string) $row['occurred_at']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= ($row['type'] ?? '') === 'entrada' ? 'success' : 'danger' ?>">
                                    <?= htmlspecialchars(CashFlow::typeLabel((string) ($row['type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold">R$ <?= htmlspecialchars($fmt((string) ($row['amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= isset($row['payment_method']) && $row['payment_method'] !== null
                                    ? htmlspecialchars(Payment::methodLabel((string) $row['payment_method']), ENT_QUOTES, 'UTF-8')
                                    : '—' ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars((string) ($row['description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = $reportPath;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>