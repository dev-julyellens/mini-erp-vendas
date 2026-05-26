<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<array<string, mixed>> $items */
/** @var list<\App\Models\Company> $companies */
/** @var list<\App\Models\Plan> $plans */
/** @var string $search */

?>
<div class="mb-3">
    <h1 class="h3 mb-1">Assinaturas</h1>
    <a class="small text-muted" href="<?= htmlspecialchars($url('admin/saas'), ENT_QUOTES, 'UTF-8') ?>">&larr; Dashboard SaaS</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-6">
        <input type="search" name="q" class="form-control" placeholder="Empresa ou plano" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-auto"><button class="btn btn-secondary" type="submit">Buscar</button></div>
</form>

<div class="card-soft p-3 p-md-4 mb-4">
    <h2 class="h6 mb-3">Vincular / alterar plano da empresa</h2>
    <form class="row g-2 align-items-end" method="post" action="<?= htmlspecialchars($url('admin/saas/assign-plan'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
        <div class="col-md-5">
            <label class="form-label">Empresa</label>
            <select name="company_id" class="form-select" required>
                <option value="">Selecione</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Plano</label>
            <select name="plan_code" class="form-select" required>
                <?php foreach ($plans as $p): ?>
                    <option value="<?= htmlspecialchars($p->code, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Aplicar plano</button>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Plano</th>
                    <th>Status</th>
                    <th>Período até</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['company_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['plan_name'], ENT_QUOTES, 'UTF-8') ?> <code class="small"><?= htmlspecialchars((string) $row['plan_code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-muted small"><?= htmlspecialchars(substr((string) $row['current_period_end'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php $path = 'admin/saas/subscriptions';
    $query = array_filter(['q' => $search]);
    require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
</div>