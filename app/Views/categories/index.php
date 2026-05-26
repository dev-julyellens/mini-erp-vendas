<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Category> $categories */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Categorias</h1>
        <div class="text-muted">Organize o catálogo de produtos e serviços</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-secondary" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-arrow-left"></i> Produtos
        </a>
        <?php if (\App\Helpers\Permission::can('produtos', 'criar')): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('categories/create'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-plus-lg"></i> Nova categoria
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="2">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted">
                            <?= $c->description
                                ? htmlspecialchars($c->description, ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                            <?php if (\App\Helpers\Permission::can('produtos', 'editar')): ?>
                                <a class="btn btn-sm btn-outline"
                                    href="<?= htmlspecialchars($url('categories/edit?id=' . $c->id), ENT_QUOTES, 'UTF-8') ?>">
                                    Editar
                                </a>
                            <?php endif; ?>
                            <?php if (\App\Helpers\Permission::can('produtos', 'excluir')): ?>
                                <form class="d-inline" method="post"
                                    action="<?= htmlspecialchars($url('categories/delete'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-confirm="Remover esta categoria? Esta ação não pode ser desfeita."
                                    data-confirm-title="Excluir categoria">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                                    <button class="btn btn-sm btn-destructive" type="submit">Excluir</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr class="empty-row">
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="bi bi-tags"></i>
                                Nenhuma categoria cadastrada.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>