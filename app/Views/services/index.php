<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Product> $services */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array{q: string, category_id: int} $filters */
/** @var list<\App\Models\Category> $categories */

$filterQuery = array_filter([
    'q' => $filters['q'] !== '' ? $filters['q'] : null,
    'category_id' => $filters['category_id'] > 0 ? (string) $filters['category_id'] : null,
], static fn($v) => $v !== null && $v !== '');

$title = 'Serviços';
$subtitle = 'Cadastro de serviços com valor padrão e tempo estimado — sem controle de estoque';
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-box-seam"></i> Produtos</a>';
if (\App\Helpers\Permission::can('produtos', 'criar'))
{
    $actionsHtml .= '<a class="btn btn-primary" href="' . htmlspecialchars($url('services/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Novo serviço</a>';
}
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-5">
    <label class="form-label" for="filter_q">Buscar</label>
    <input class="form-control" id="filter_q" name="q" placeholder="Nome ou SKU"
        value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-12 col-md-4">
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
<?php
$filterContent = ob_get_clean();
$filterAction = $url('services');
$filterClearHref = $url('services');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="5">
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th class="d-none d-md-table-cell">SKU</th>
                    <th class="d-none d-lg-table-cell">Categoria</th>
                    <th>Valor padrão</th>
                    <th class="d-none d-sm-table-cell">Tempo est.</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small d-md-none"><code><?= htmlspecialchars($s->sku, ENT_QUOTES, 'UTF-8') ?></code></div>
                        </td>
                        <td class="d-none d-md-table-cell"><code class="small"><?= htmlspecialchars($s->sku, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td class="d-none d-lg-table-cell text-muted small">
                            <?= $s->categoryName
                                ? htmlspecialchars($s->categoryName, ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td>R$ <?= htmlspecialchars(number_format((float) $s->price, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="d-none d-sm-table-cell text-muted">
                            <?= htmlspecialchars($s->estimatedTimeLabel(), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="text-end">
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('services/edit?id=' . $s->id);
                            $deleteAction = $url('services/delete');
                            $deleteId = (int) $s->id;
                            $deleteConfirm = 'Remover este serviço? Esta ação não pode ser desfeita.';
                            $deleteTitle = 'Excluir serviço';
                            $canEdit = \App\Helpers\Permission::can('produtos', 'editar');
                            $canDelete = \App\Helpers\Permission::can('produtos', 'excluir');
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($services === []): ?>
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-tools"></i>
                                Nenhum serviço encontrado.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'services';
    $query = $filterQuery;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>