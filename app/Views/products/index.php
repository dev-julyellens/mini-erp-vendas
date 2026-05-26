<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Product> $products */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array{q: string, category_id: int, type: string, low_stock: bool} $filters */
/** @var list<\App\Models\Category> $categories */
/** @var array<string, string> $typeOptions */

$filterQuery = array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'category_id' => $filters['category_id'] > 0 ? (string) $filters['category_id'] : null,
    'type' => $filters['type'] !== '' ? $filters['type'] : null,
    'low_stock' => $filters['low_stock'] ? '1' : null,
], static fn($v) => $v !== null && $v !== '');

$title = 'Produtos';
$subtitle = 'Catálogo avançado com SKU, categorias, custos e estoque mínimo';
$actionsHtml = '';
if (\App\Helpers\Permission::can('produtos', 'visualizar'))
{
    $actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('services'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-tools"></i> Serviços</a>';
    $actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('categories'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-tags"></i> Categorias</a>';
}
if (\App\Helpers\Permission::can('produtos', 'criar'))
{
    $actionsHtml .= '<a class="btn btn-primary" href="' . htmlspecialchars($url('products/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Novo produto</a>';
}
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-4">
    <label class="form-label" for="filter_q">Buscar</label>
    <input class="form-control" id="filter_q" name="q" placeholder="Nome, SKU ou código de barras"
        value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-12 col-md-3">
    <label class="form-label" for="filter_category_id">Categoria</label>
    <select class="form-select" id="filter_category_id" name="category_id">
        <option value="">Todas</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat->id ?>" <?= $filters['category_id'] === $cat->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-sm-6 col-md-2">
    <label class="form-label" for="filter_type">Tipo</label>
    <select class="form-select" id="filter_type" name="type">
        <option value="">Todos</option>
        <?php foreach ($typeOptions as $value => $label): ?>
            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['type'] === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-sm-6 col-md-3 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="lowStockFilter"
            <?= $filters['low_stock'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="lowStockFilter">Só estoque baixo</label>
    </div>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('products');
$filterClearHref = $url('products');
$filterActionsColClass = 'col-12 col-md-auto';
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="6">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th class="d-none d-md-table-cell">SKU</th>
                    <th class="d-none d-lg-table-cell">Categoria</th>
                    <th class="d-none d-sm-table-cell">Tipo</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php $low = $p->isLowStock(); ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small d-md-none"><code><?= htmlspecialchars($p->sku, ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php if ($p->barcode): ?>
                                <div class="text-muted small">EAN: <?= htmlspecialchars($p->barcode, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell"><code class="small"><?= htmlspecialchars($p->sku, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td class="d-none d-lg-table-cell text-muted small">
                            <?= $p->categoryName
                                ? htmlspecialchars($p->categoryName, ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="d-none d-sm-table-cell">
                            <span class="badge <?= $p->isService() ? 'text-bg-info' : 'text-bg-secondary' ?>">
                                <?= htmlspecialchars($p->typeLabel(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>R$ <?= htmlspecialchars(number_format((float) $p->price, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($p->isService()): ?>
                                <span class="text-muted small">N/A</span>
                            <?php elseif ($low): ?>
                                <span class="badge text-bg-warning">
                                    <?= (int) $p->stock ?> / mín. <?= (int) $p->minStock ?>
                                </span>
                            <?php else: ?>
                                <span class="badge text-bg-light border"><?= (int) $p->stock ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('products/edit?id=' . $p->id);
                            $deleteAction = $url('products/delete');
                            $deleteId = (int) $p->id;
                            $deleteConfirm = 'Remover este produto? Esta ação não pode ser desfeita.';
                            $deleteTitle = 'Excluir produto';
                            $canEdit = \App\Helpers\Permission::can('produtos', 'editar');
                            $canDelete = \App\Helpers\Permission::can('produtos', 'excluir');
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?>
                    <tr class="empty-row">
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-box-seam"></i>
                                Nenhum produto encontrado.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'products';
    $query = $filterQuery;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>