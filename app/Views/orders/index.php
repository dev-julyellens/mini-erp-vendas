<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\Order;

/** @var callable(string):string $url */
/** @var list<\App\Models\Order> $orders */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<\App\Models\Customer> $customers */
/** @var array{customer_id: ?int, date_from: string, date_to: string} $filters */

$query = [];
if ($filters['customer_id'] !== null)
{
    $query['customer_id'] = (string) $filters['customer_id'];
}
if ($filters['date_from'] !== '')
{
    $query['date_from'] = $filters['date_from'];
}
if ($filters['date_to'] !== '')
{
    $query['date_to'] = $filters['date_to'];
}

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Vendas</h1>
        <div class="text-muted">Listagem, filtros e detalhes</div>
    </div>
    <?php if (\App\Helpers\Permission::can('vendas', 'criar')): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url('orders/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-plus-lg"></i> Nova venda
        </a>
    <?php endif; ?>
</div>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form class="row g-2 align-items-end filter-form" method="get" action="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="col-md-4">
            <label class="form-label">Cliente</label>
            <select class="form-select" name="customer_id">
                <option value="">Todos</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c->id ?>" <?= $filters['customer_id'] === $c->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">De</label>
            <input class="form-control" type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Até</label>
            <input class="form-control" type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-primary btn-md" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="5">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int) $o->id ?></td>
                        <td><?= htmlspecialchars((string) ($o->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= htmlspecialchars(number_format((float) $o->total_amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge text-bg-<?= htmlspecialchars(Order::statusBadge($o->status), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(Order::statusLabel($o->status), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($o->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-ghost" href="<?= htmlspecialchars($url('orders/show?id=' . $o->id), ENT_QUOTES, 'UTF-8') ?>">Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($orders === []): ?>
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-receipt"></i>
                                Nenhuma venda encontrada para os filtros atuais.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'orders';
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>