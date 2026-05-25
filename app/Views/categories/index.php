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
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">
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
        <table class="table table-hover align-middle mb-0">
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
                            <?php if (\App\Helpers\Permission::can('produtos', 'editar')): ?>
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="<?= htmlspecialchars($url('categories/edit?id=' . $c->id), ENT_QUOTES, 'UTF-8') ?>">
                                    Editar
                                </a>
                            <?php endif; ?>
                            <?php if (\App\Helpers\Permission::can('produtos', 'excluir')): ?>
                                <form class="d-inline" method="post"
                                    action="<?= htmlspecialchars($url('categories/delete'), ENT_QUOTES, 'UTF-8') ?>"
                                    onsubmit="return confirm('Remover esta categoria?');">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>