<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $reportPath */
/** @var list<array<string, mixed>> $items */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array<string, mixed> $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<\App\Models\Category> $categories */

$categoryId = $filters['category_id'] ?? null;

require dirname(__DIR__) . '/reports/_report-header.php';
?>
<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-6 col-md-4">
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
        <div class="col-12 col-md-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url($reportPath), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>SKU</th>
                    <th>Categoria</th>
                    <th class="text-end">Estoque</th>
                    <th class="text-end">Mínimo</th>
                    <th class="text-end">Déficit</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr>
                        <td colspan="6" class="text-muted">Nenhum produto abaixo do estoque mínimo.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?php
                        $stock = (int) ($row['stock'] ?? 0);
                        $min = (int) ($row['min_stock'] ?? 0);
                        $deficit = max(0, $min - $stock);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted"><?= htmlspecialchars((string) ($row['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['category_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <span class="badge text-bg-danger"><?= $stock ?></span>
                            </td>
                            <td class="text-end"><?= $min ?></td>
                            <td class="text-end fw-semibold"><?= $deficit ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $path = $reportPath;
    require dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>