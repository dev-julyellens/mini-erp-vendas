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

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Serviços</h1>
        <div class="text-muted">Cadastro de serviços com valor padrão e tempo estimado — sem controle de estoque</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-box-seam"></i> Produtos
        </a>
        <?php if (\App\Helpers\Permission::can('produtos', 'criar')): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('services/create'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-plus-lg"></i> Novo serviço
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('services'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end filter-form">
        <div class="col-12 col-md-5">
            <label class="form-label small text-muted mb-1">Buscar</label>
            <input class="form-control" name="q" placeholder="Nome ou SKU"
                value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1">Categoria</label>
            <select class="form-select" name="category_id">
                <option value="">Todas</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat->id ?>" <?= $filters['category_id'] === $cat->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
            <?php if ($filterQuery !== []): ?>
                <a class="btn btn-secondary" href="<?= htmlspecialchars($url('services'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

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
                            <div class="table-actions">
                            <?php if (\App\Helpers\Permission::can('produtos', 'editar')): ?>
                                <a class="btn btn-sm btn-outline"
                                    href="<?= htmlspecialchars($url('services/edit?id=' . $s->id), ENT_QUOTES, 'UTF-8') ?>">
                                    Editar
                                </a>
                            <?php endif; ?>
                            <?php if (\App\Helpers\Permission::can('produtos', 'excluir')): ?>
                                <form class="d-inline" method="post"
                                    action="<?= htmlspecialchars($url('services/delete'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-confirm="Remover este serviço? Esta ação não pode ser desfeita."
                                    data-confirm-title="Excluir serviço">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $s->id ?>">
                                    <button class="btn btn-sm btn-destructive" type="submit">Excluir</button>
                                </form>
                            <?php endif; ?>
                            </div>
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