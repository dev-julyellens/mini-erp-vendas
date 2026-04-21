<?php

declare(strict_types=1);

/** @var int $ordersCount */
/** @var int $productsCount */
/** @var int $customersCount */
/** @var array $lowStockProducts */
/** @var int $lowStockThreshold */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <div class="text-muted">Visão geral do Mini ERP de Vendas</div>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url('orders/create'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-plus-lg"></i> Nova venda
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-tile stat-orders">
            <div class="text-white-50 small">Total de vendas</div>
            <h3><?= (int) $ordersCount ?></h3>
            <div class="small text-white-50">Pedidos registrados</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile stat-products">
            <div class="text-white-50 small">Total de produtos</div>
            <h3><?= (int) $productsCount ?></h3>
            <div class="small text-white-50">Itens no catálogo</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile stat-customers">
            <div class="text-white-50 small">Total de clientes</div>
            <h3><?= (int) $customersCount ?></h3>
            <div class="small text-white-50">Base cadastrada</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-soft p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <div class="fw-semibold">Atalhos</div>
                    <div class="text-muted small">Fluxos mais comuns</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="<?= htmlspecialchars($url('customers/create'), ENT_QUOTES, 'UTF-8') ?>">Novo cliente</a>
                <a class="btn btn-outline-primary" href="<?= htmlspecialchars($url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Consultar vendas</a>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-soft p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Alerta de estoque baixo</div>
                <span class="badge badge-low">&lt; <?= (int) $lowStockThreshold ?> un.</span>
            </div>
            <?php if ($lowStockProducts === []): ?>
                <div class="text-muted">Nenhum produto abaixo do limite.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lowStockProducts as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small">Estoque atual</div>
                            </div>
                            <span class="badge text-bg-warning"><?= (int) $p->stock ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
