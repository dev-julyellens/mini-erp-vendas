<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $reportPath */
/** @var list<array<string, mixed>> $items */
/** @var array{quantity_sold: int, total_amount: string} $summary */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<\App\Models\Product> $products */
/** @var list<\App\Models\Category> $categories */
/** @var list<string> $orderStatuses */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$productId = $filters['product_id'] ?? null;
$categoryId = $filters['category_id'] ?? null;

require dirname(__DIR__) . '/reports/_report-header.php';
?>
<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label" for="date_from">De</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label" for="date_to">Até</label>
            <input type="date" class="form-control" id="date_to" name="date_to"
                value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="product_id">Produto</label>
            <select class="form-select" id="product_id" name="product_id">
                <option value="">Todos</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p->id ?>" <?= $productId === $p->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-lg-2">
            <label class="form-label" for="category_id">Categoria</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">Todas</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat->id ?>" <?= $categoryId === $cat->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php require __DIR__ . '/partials/filters-order-status.php'; ?>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card-soft p-3">
            <div class="text-muted small">Quantidade vendida</div>
            <div class="fs-4 fw-semibold"><?= (int) $summary['quantity_sold'] ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-soft p-3">
            <div class="text-muted small">Receita</div>
            <div class="fs-4 fw-semibold">R$ <?= htmlspecialchars($fmt((string) $summary['total_amount']), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>SKU</th>
                    <th>Tipo</th>
                    <th class="text-end">Qtd.</th>
                    <th class="text-end">Receita (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="5" class="text-muted">Nenhum registro.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?php $isService = ($row['product_type'] ?? '') === 'service'; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted"><?= htmlspecialchars((string) ($row['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= $isService ? 'info' : 'secondary' ?>">
                                    <?= $isService ? 'Serviço' : 'Produto' ?>
                                </span>
                            </td>
                            <td class="text-end"><?= (int) ($row['quantity_sold'] ?? 0) ?></td>
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