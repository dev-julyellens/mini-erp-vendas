<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, int> $metrics */
/** @var list<\App\Models\Plan> $plans */

$title = 'Administração SaaS';
$subtitle = 'Planos, assinaturas e indicadores da plataforma';
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('admin/saas/subscriptions'), ENT_QUOTES, 'UTF-8') . '">'
    . 'Assinaturas por empresa</a>';
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<div class="kpi-grid mb-4">
    <?php
    $metricCards = [
        ['Empresas', (int) ($metrics['companies_total'] ?? 0), 'stat-orders', null],
        ['Empresas ativas', (int) ($metrics['companies_active'] ?? 0), 'stat-products', null],
        ['Usuários', (int) ($metrics['users_total'] ?? 0), 'stat-customers', null],
        ['Assinaturas ativas', (int) ($metrics['subscriptions_active'] ?? 0), 'stat-finance-month', $url('admin/saas/subscriptions')],
        ['Em trial', (int) ($metrics['subscriptions_trialing'] ?? 0), 'stat-finance-open', null],
    ];
    foreach ($metricCards as [$label, $metricValue, $variant, $href]):
        $value = (string) $metricValue;
        $hint = null;
        require dirname(__DIR__, 2) . '/components/kpi-card.php';
    endforeach;
    ?>
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