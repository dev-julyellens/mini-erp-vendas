<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Category> $categories */

$title = 'Categorias';
$subtitle = 'Organize o catálogo de produtos e serviços';
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left"></i> Produtos</a>';
if (\App\Helpers\Permission::can('produtos', 'criar'))
{
    $actionsHtml .= '<a class="btn btn-primary" href="' . htmlspecialchars($url('categories/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Nova categoria</a>';
}
require dirname(__DIR__) . '/components/page-header.php';

?>
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
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('categories/edit?id=' . $c->id);
                            $deleteAction = $url('categories/delete');
                            $deleteId = (int) $c->id;
                            $deleteConfirm = 'Remover esta categoria? Esta ação não pode ser desfeita.';
                            $deleteTitle = 'Excluir categoria';
                            $canEdit = \App\Helpers\Permission::can('produtos', 'editar');
                            $canDelete = \App\Helpers\Permission::can('produtos', 'excluir');
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
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