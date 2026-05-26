<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Company> $companies */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
/** @var string $status */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Empresas</h1>
        <div class="text-muted">Gestão administrativa de empresas (multiempresa)</div>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/companies/create'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-plus-lg"></i> Nova empresa
    </a>
</div>

<form class="row g-2 mb-3" method="get" action="<?= htmlspecialchars($url('admin/companies'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-md-5">
        <input type="search" name="q" class="form-control" placeholder="Buscar nome, slug ou documento"
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">Todos os status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativas</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativas</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-funnel"></i> Filtrar</button>
    </div>
</form>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="5">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Documento</th>
                    <th>Status</th>
                    <th>Criada em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($c->slug, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars($c->tax_id ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($c->active): ?>
                                <span class="badge text-bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars(substr($c->created_at, 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end text-nowrap">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($url('admin/companies/edit?id=' . $c->id), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                <form class="d-inline" method="post" action="<?= htmlspecialchars($url('admin/companies/toggle-active'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-confirm="<?= $c->active ? 'Desativar esta empresa?' : 'Ativar esta empresa?' ?>">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $c->id ?>">
                                    <input type="hidden" name="active" value="<?= $c->active ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm <?= $c->active ? 'btn-warning' : 'btn-outline' ?>">
                                        <?= $c->active ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php $path = 'admin/companies';
    $query = array_filter(['q' => $search, 'status' => $status]);
    require dirname(__DIR__) . '/partials/pagination.php'; ?>
</div>