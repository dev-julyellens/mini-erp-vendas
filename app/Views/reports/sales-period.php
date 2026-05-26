<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $reportPath */
/** @var string $exportPath */
/** @var list<array<string, mixed>> $items */
/** @var array{order_count: int, total_amount: string} $summary */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<string> $orderStatuses */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');

require dirname(__DIR__) . '/reports/_report-header.php';
?>
<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label" for="date_from">De</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="date_to">Até</label>
            <input type="date" class="form-control" id="date_to" name="date_to"
                value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php require __DIR__ . '/partials/filters-order-status.php'; ?>
        <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card-soft p-3">
            <div class="text-muted small">Pedidos no período</div>
            <div class="fs-4 fw-semibold"><?= (int) $summary['order_count'] ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-soft p-3">
            <div class="text-muted small">Receita total</div>
            <div class="fs-4 fw-semibold">R$ <?= htmlspecialchars($fmt((string) $summary['total_amount']), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable">
            <thead>
                <tr>
                    <th>Data</th>
                    <th class="text-end">Pedidos</th>
                    <th class="text-end">Total (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="3" class="text-muted">Nenhum registro no período.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(DateHelper::toBrDate((string) $row['period_date']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= (int) ($row['order_count'] ?? 0) ?></td>
                            <td class="text-end fw-semibold">R$ <?= htmlspecialchars($fmt((string) ($row['total_amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $path = $reportPath;
    require dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>