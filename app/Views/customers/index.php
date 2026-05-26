<?php

declare(strict_types=1);

use App\Helpers\DataMask;
use App\Helpers\DateHelper;
use App\Helpers\SecurityConfig;

/** @var callable(string):string $url */
/** @var list<\App\Models\Customer> $customers */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */

$maskSensitive = SecurityConfig::maskSensitiveDataInLists();

?>
<?php
$title = 'Clientes';
$subtitle = 'Cadastro e manutenção de clientes';
$actionsHtml = '';
if (\App\Helpers\Permission::can('clientes', 'criar'))
{
    $actionsHtml = '<a class="btn btn-primary" href="' . htmlspecialchars($url('customers/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Novo cliente</a>';
}
require dirname(__DIR__) . '/components/page-header.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="4">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Criado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($maskSensitive ? DataMask::email($c->email) : $c->email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($maskSensitive ? DataMask::phone($c->phone) : (string) ($c->phone ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($c->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('customers/edit?id=' . $c->id);
                            $deleteAction = $url('customers/delete');
                            $deleteId = (int) $c->id;
                            $deleteConfirm = 'Remover este cliente? Esta ação não pode ser desfeita.';
                            $deleteTitle = 'Excluir cliente';
                            $canEdit = \App\Helpers\Permission::can('clientes', 'editar');
                            $canDelete = \App\Helpers\Permission::can('clientes', 'excluir');
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($customers === []): ?>
                    <tr class="empty-row">
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                Nenhum cliente cadastrado.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'customers';
    $query = [];
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>