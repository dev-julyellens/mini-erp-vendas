<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\User> $users */
/** @var list<string> $globalRoles */
/** @var string $search */
/** @var string $status */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Usuários</h1>
        <div class="text-muted">Gestão de contas de acesso</div>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/create'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-plus-lg"></i> Novo usuário
    </a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-5">
        <input type="search" name="q" class="form-control" placeholder="Nome ou e-mail" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativos</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-secondary" type="submit">Filtrar</button></div>
</form>

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
                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('admin/users/edit?id=' . $u->id), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url('admin/users/reset-password?id=' . $u->id), ENT_QUOTES, 'UTF-8') ?>">Senha</a>
                            <form class="d-inline" method="post" action="<?= htmlspecialchars($url('admin/users/toggle-active'), ENT_QUOTES, 'UTF-8') ?>"
                                data-confirm="Alterar status deste usuário?">
                                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                <input type="hidden" name="id" value="<?= (int) $u->id ?>">
                                <input type="hidden" name="active" value="<?= $u->active ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-outline-warning" type="submit"><?= $u->active ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php $path = 'admin/users';
    $query = array_filter(['q' => $search, 'status' => $status]);
    require dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>