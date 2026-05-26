<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $reportPath */
/** @var list<array<string, mixed>> $items */
/** @var array{order_count: int, total_amount: string} $summary */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<\App\Models\Customer> $customers */
/** @var list<string> $orderStatuses */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$customerId = $filters['customer_id'] ?? null;

require dirname(__DIR__) . '/reports/_report-header.php';

ob_start();
?>
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
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_customer_id">Cliente</label>
    <select class="form-select" id="filter_customer_id" name="customer_id">
        <option value="">Todos</option>
        <?php foreach ($customers as $c): ?>
            <option value="<?= (int) $c->id ?>" <?= $customerId === $c->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php require __DIR__ . '/partials/filters-order-status.php'; ?>
<?php
$filterContent = ob_get_clean();
$filterAction = $url($reportPath);
$filterClearHref = $url($reportPath);
$filterActionsColClass = 'col-12 col-md-auto';
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="kpi-grid mb-3">
    <?php
    $label = 'Pedidos';
    $value = (string) (int) $summary['order_count'];
    $hint = 'Clientes no ranking';
    $variant = 'stat-revenue-month';
    $href = null;
    require dirname(__DIR__) . '/components/kpi-card.php';

    $label = 'Receita';
    $value = 'R$ ' . htmlspecialchars($fmt((string) $summary['total_amount']), ENT_QUOTES, 'UTF-8');
    $hint = 'Total no período';
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
                    <th>Cliente</th>
                    <th class="text-end">Pedidos</th>
                    <th class="text-end">Total (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="3" class="text-muted">Nenhum registro.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= (int) ($row['order_count'] ?? 0) ?></td>
                            <td class="text-end fw-semibold">R$ <?= htmlspecialchars($fmt((string) ($row['total_amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></td>
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