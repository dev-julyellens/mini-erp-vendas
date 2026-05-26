<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, int> $metrics */
/** @var list<\App\Models\Plan> $plans */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Administração SaaS</h1>
        <div class="text-muted">Planos, assinaturas e indicadores da plataforma</div>
    </div>
    <a class="btn btn-outline-primary" href="<?= htmlspecialchars($url('admin/saas/subscriptions'), ENT_QUOTES, 'UTF-8') ?>">
        Assinaturas por empresa
    </a>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Empresas', $metrics['companies_total'] ?? 0, 'bi-building'],
        ['Empresas ativas', $metrics['companies_active'] ?? 0, 'bi-check-circle'],
        ['Usuários', $metrics['users_total'] ?? 0, 'bi-people'],
        ['Assinaturas ativas', $metrics['subscriptions_active'] ?? 0, 'bi-credit-card'],
        ['Em trial', $metrics['subscriptions_trialing'] ?? 0, 'bi-hourglass-split'],
    ];
    foreach ($cards as [$label, $value, $icon]):
    ?>
        <div class="col-6 col-md-4 col-xl">
            <div class="card-soft p-3 h-100">
                <div class="text-muted small"><i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="display-6 fw-semibold mb-0"><?= (int) $value ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card-soft p-3 p-md-4">
    <h2 class="h5 mb-3">Planos disponíveis</h2>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Preço/mês</th>
                    <th>Trial</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($plan->code, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars($plan->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float) $plan->price_monthly, 2, ',', '.') ?></td>
                        <td><?= (int) $plan->trial_days ?> dias</td>
                        <td><?= $plan->active ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>