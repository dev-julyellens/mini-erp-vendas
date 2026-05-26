<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var callable(string):string $url */
/** @var \App\Models\Subscription|null $subscription */
/** @var list<\App\Models\SubscriptionInvoice> $invoices */
/** @var \App\Models\SubscriptionInvoice|null $pendingInvoice */
/** @var array<string, array{limit: int, current: int, unlimited: bool}> $limits */
/** @var list<\App\Models\Plan> $plans */

$statusLabels = [
    'trialing' => 'Período de teste',
    'active' => 'Ativa',
    'past_due' => 'Pagamento pendente',
    'canceled' => 'Cancelada',
    'expired' => 'Expirada',
];

$title = 'Assinatura';
$subtitle = 'Plano, limites e cobrança recorrente da plataforma.';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => $url('')],
    ['label' => 'Assinatura'],
];
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left"></i> Voltar</a>';
require dirname(__DIR__) . '/components/page-header.php';

?>

<?php if ($subscription === null): ?>
    <div class="alert alert-warning">Nenhuma assinatura vinculada a esta empresa.</div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Plano</div>
                    <div class="fw-semibold"><?= htmlspecialchars($subscription->plan_name ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($statusLabels[$subscription->status] ?? $subscription->status, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Período atual até</div>
                    <div class="fw-semibold">
                        <?= htmlspecialchars(DateHelper::toBrDateTime($subscription->current_period_end), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($pendingInvoice !== null): ?>
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            Fatura pendente: <strong>R$ <?= number_format((float) $pendingInvoice->amount, 2, ',', '.') ?></strong>
            (venc. <?= htmlspecialchars(DateHelper::toBrDate($pendingInvoice->due_at), ENT_QUOTES, 'UTF-8') ?>)
        </div>
        <form method="post" action="<?= htmlspecialchars($url('subscription/pay'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
            <input type="hidden" name="invoice_id" value="<?= (int) $pendingInvoice->id ?>">
            <button type="submit" class="btn btn-primary btn-sm" data-loading-text="Processando...">Simular pagamento</button>
        </form>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Uso do plano</div>
            <ul class="list-group list-group-flush">
                <?php
                $limitLabels = [
                    'customers_max' => 'Clientes',
                    'products_max' => 'Produtos',
                    'users_max' => 'Usuários',
                    'orders_month_max' => 'Pedidos (mês)',
                ];
                foreach ($limits as $key => $usage):
                    $label = $limitLabels[$key] ?? $key;
                    $text = $usage['unlimited']
                        ? $usage['current'] . ' · ilimitado'
                        : $usage['current'] . ' / ' . $usage['limit'];
                ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted"><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Alterar plano</div>
            <div class="card-body">
                <form method="post" action="<?= htmlspecialchars($url('subscription/change-plan'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                    <div class="mb-3">
                        <select name="plan_code" class="form-select" required>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= htmlspecialchars($plan->code, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($subscription !== null && $subscription->plan_code === $plan->code) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan->name, ENT_QUOTES, 'UTF-8') ?>
                                    — R$ <?= number_format((float) $plan->price_monthly, 2, ',', '.') ?>/mês
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" data-loading-text="Atualizando...">Atualizar plano</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($invoices !== []): ?>
    <div class="card mt-4">
        <div class="card-header">Histórico de cobrança</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="small">
                                <?= htmlspecialchars(DateHelper::toBrDate($inv->period_start), ENT_QUOTES, 'UTF-8') ?>
                                —
                                <?= htmlspecialchars(DateHelper::toBrDate($inv->period_end), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>R$ <?= number_format((float) $inv->amount, 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars(DateHelper::toBrDate($inv->due_at), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($inv->status, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>