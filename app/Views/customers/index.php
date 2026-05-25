<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var list<\App\Models\Customer> $customers */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Clientes</h1>
        <div class="text-muted">Cadastro e manutenção de clientes</div>
    </div>
    <?php if (\App\Helpers\Permission::can('clientes', 'criar')): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url('customers/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-plus-lg"></i> Novo cliente
        </a>
    <?php endif; ?>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                        <td><?= htmlspecialchars($c->email, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($c->phone ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($c->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <?php if (\App\Helpers\Permission::can('clientes', 'editar')): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('customers/edit?id=' . $c->id), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                            <?php endif; ?>
                            <?php if (\App\Helpers\Permission::can('clientes', 'excluir')): ?>
                                <form class="d-inline" method="post" action="<?= htmlspecialchars($url('customers/delete'), ENT_QUOTES, 'UTF-8') ?>"
                                    onsubmit="return confirm('Remover este cliente?');">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($customers === []): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Nenhum cliente cadastrado.</td>
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