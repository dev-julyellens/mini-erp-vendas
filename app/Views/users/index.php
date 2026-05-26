<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\User> $users */
/** @var list<string> $globalRoles */
/** @var string $search */
/** @var string $status */

$title = 'Usuários';
$subtitle = 'Gestão de contas de acesso';
$actionsHtml = '<a class="btn btn-primary" href="' . htmlspecialchars($url('admin/users/create'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-plus-lg"></i> Novo usuário</a>';
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-5">
    <label class="form-label" for="filter_q">Buscar</label>
    <input type="search" class="form-control" id="filter_q" name="q" placeholder="Nome ou e-mail"
        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-12 col-md-4">
    <label class="form-label" for="filter_status">Status</label>
    <select class="form-select" id="filter_status" name="status">
        <option value="">Todos</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativos</option>
    </select>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('admin/users');
$filterClearHref = $url('admin/users');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="4">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Papel global</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($u->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($u->role, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= $u->active ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
                        <td class="text-end text-nowrap">
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('admin/users/edit?id=' . $u->id);
                            $canEdit = true;
                            $canDelete = false;
                            $extraActions = [
                                [
                                    'href' => $url('admin/users/reset-password?id=' . $u->id),
                                    'label' => 'Senha',
                                    'variant' => 'ghost',
                                ],
                                [
                                    'action' => $url('admin/users/toggle-active'),
                                    'label' => $u->active ? 'Desativar' : 'Ativar',
                                    'variant' => 'warning',
                                    'confirm' => 'Alterar status deste usuário?',
                                    'extras' => [
                                        'id' => (string) $u->id,
                                        'active' => $u->active ? '0' : '1',
                                    ],
                                ],
                            ];
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = 'admin/users';
    $query = array_filter(['q' => $search, 'status' => $status]);
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>