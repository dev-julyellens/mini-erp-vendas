<?php

declare(strict_types=1);

use App\Helpers\Permission;
use App\Models\AccountsReceivable;

/** @var callable(string):string $url */
/** @var array<string, mixed> $dashboard */
/** @var int $ordersCount */
/** @var int $productsCount */
/** @var int $customersCount */
/** @var list<\App\Models\Product> $lowStockProducts */

$canVendas = Permission::canView('vendas');
$canProdutos = Permission::canView('produtos');
$canClientes = Permission::canView('clientes');
$canEstoque = Permission::canView('estoque');
$canFinanceiro = Permission::canView('financeiro');

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');

$counts = $dashboard['counts'] ?? [];
$sales = $dashboard['sales'] ?? [];
$stock = $dashboard['stock'] ?? [];
$finance = $dashboard['finance'] ?? [];

$snapshot = $sales['snapshot'] ?? [];
$dailySeries = $sales['dailySeries'] ?? [];
$monthlySeries = $sales['monthlySeries'] ?? [];
$topProducts = $sales['topProducts'] ?? [];

$lowStockTotal = (int) ($stock['lowStockTotal'] ?? count($lowStockProducts));
$overdueAccounts = $finance['overdueAccounts'] ?? [];
$overdueCount = (int) ($finance['overdueCount'] ?? 0);
$overdueTotal = (string) ($finance['overdueTotal'] ?? '0');

$hasCharts = $canVendas && ($dailySeries !== [] || $monthlySeries !== [] || $topProducts !== []);

$jsonFlags = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

if ($hasCharts)
{
    $chartDailyJson = json_encode([
        'labels' => array_column($dailySeries, 'label'),
        'amounts' => array_map(static fn(array $r): float => (float) $r['amount'], $dailySeries),
    ], $jsonFlags);

    $chartMonthlyJson = json_encode([
        'labels' => array_column($monthlySeries, 'label'),
        'amounts' => array_map(static fn(array $r): float => (float) $r['amount'], $monthlySeries),
    ], $jsonFlags);

    $topLabels = [];
    $topQty = [];
    foreach ($topProducts as $row)
    {
        $name = (string) ($row['product_name'] ?? 'Produto');
        $topLabels[] = mb_strlen($name) > 28 ? mb_substr($name, 0, 25) . '…' : $name;
        $topQty[] = (int) ($row['quantity_sold'] ?? 0);
    }
    $chartTopJson = json_encode([
        'labels' => $topLabels,
        'quantities' => $topQty,
    ], $jsonFlags);
}

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Dashboard gerencial</h1>
        <div class="text-muted">Visão consolidada de vendas, estoque e financeiro</div>
    </div>
    <?php if (Permission::can('vendas', 'criar')): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url('orders/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-plus-lg"></i> Nova venda
        </a>
    <?php endif; ?>
</div>

<?php if ($canVendas): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-tile stat-revenue-today">
                <div class="text-white-50 small">Faturamento hoje</div>
                <h3>R$ <?= htmlspecialchars($fmt((string) ($snapshot['today_amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="small text-white-50">
                    <?= (int) ($snapshot['today_orders'] ?? 0) ?> pedido(s) pagos
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-tile stat-revenue-month">
                <div class="text-white-50 small">Vendas no mês</div>
                <h3>R$ <?= htmlspecialchars($fmt((string) ($snapshot['month_amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="small text-white-50">
                    <?= (int) ($snapshot['month_orders'] ?? 0) ?> pedido(s) no período
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-tile stat-orders">
                <div class="text-white-50 small">Total de vendas</div>
                <h3><?= (int) ($counts['orders'] ?? $ordersCount) ?></h3>
                <div class="small text-white-50">Pedidos registrados</div>
            </div>
        </div>
        <?php if ($canProdutos): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-tile stat-products">
                    <div class="text-white-50 small">Total de produtos</div>
                    <h3><?= (int) ($counts['products'] ?? $productsCount) ?></h3>
                    <div class="small text-white-50">Itens no catálogo</div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canClientes): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-tile stat-customers">
                    <div class="text-white-50 small">Total de clientes</div>
                    <h3><?= (int) ($counts['customers'] ?? $customersCount) ?></h3>
                    <div class="small text-white-50">Base cadastrada</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php if ($canClientes): ?>
            <div class="col-md-4">
                <div class="stat-tile stat-customers">
                    <div class="text-white-50 small">Total de clientes</div>
                    <h3><?= (int) ($counts['customers'] ?? $customersCount) ?></h3>
                    <div class="small text-white-50">Base cadastrada</div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canProdutos): ?>
            <div class="col-md-4">
                <div class="stat-tile stat-products">
                    <div class="text-white-50 small">Total de produtos</div>
                    <h3><?= (int) ($counts['products'] ?? $productsCount) ?></h3>
                    <div class="small text-white-50">Itens no catálogo</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($canFinanceiro): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="stat-tile stat-overdue">
                <div class="text-white-50 small">Contas vencidas</div>
                <h3><?= $overdueCount ?></h3>
                <div class="small text-white-50">
                    R$ <?= htmlspecialchars($fmt($overdueTotal), ENT_QUOTES, 'UTF-8') ?> em aberto
                </div>
            </div>
        </div>
        <?php if ($canProdutos || $canEstoque): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-tile stat-stock-alert">
                    <div class="text-white-50 small">Estoque baixo</div>
                    <h3><?= $lowStockTotal ?></h3>
                    <div class="small text-white-50">Produto(s) abaixo do mínimo</div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($canProdutos && !$canVendas): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-tile stat-products">
                    <div class="text-white-50 small">Total de produtos</div>
                    <h3><?= (int) ($counts['products'] ?? $productsCount) ?></h3>
                    <div class="small text-white-50">Itens no catálogo</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($canProdutos || $canEstoque): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="stat-tile stat-stock-alert">
                <div class="text-white-50 small">Estoque baixo</div>
                <h3><?= $lowStockTotal ?></h3>
                <div class="small text-white-50">Produto(s) abaixo do mínimo</div>
            </div>
        </div>
        <?php if ($canProdutos): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="stat-tile stat-products">
                    <div class="text-white-50 small">Total de produtos</div>
                    <h3><?= (int) ($counts['products'] ?? $productsCount) ?></h3>
                    <div class="small text-white-50">Itens no catálogo</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($hasCharts): ?>
    <div id="dashboardCharts" class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card-soft p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="fw-semibold">Faturamento diário</div>
                        <div class="text-muted small">Últimos <?= count($dailySeries) ?> dias · pedidos pagos</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('reports/sales-period'), ENT_QUOTES, 'UTF-8') ?>">
                        Relatório
                    </a>
                </div>
                <div class="dashboard-chart-wrap">
                    <canvas id="chartDailyRevenue" aria-label="Gráfico de faturamento diário"></canvas>
                </div>
                <script type="application/json" id="dashboardDailyData">
                    <?= $chartDailyJson ?>
                </script>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card-soft p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="fw-semibold">Produtos mais vendidos</div>
                        <div class="text-muted small">Últimos 30 dias · por quantidade</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('reports/top-products'), ENT_QUOTES, 'UTF-8') ?>">
                        Ver todos
                    </a>
                </div>
                <div class="dashboard-chart-wrap dashboard-chart-wrap--tall">
                    <canvas id="chartTopProducts" aria-label="Gráfico de produtos mais vendidos"></canvas>
                </div>
                <script type="application/json" id="dashboardTopProductsData">
                    <?= $chartTopJson ?>
                </script>
            </div>
        </div>
        <div class="col-12">
            <div class="card-soft p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="fw-semibold">Vendas mensais</div>
                        <div class="text-muted small">Últimos <?= count($monthlySeries) ?> meses · pedidos pagos</div>
                    </div>
                </div>
                <div class="dashboard-chart-wrap">
                    <canvas id="chartMonthlySales" aria-label="Gráfico de vendas mensais"></canvas>
                </div>
                <script type="application/json" id="dashboardMonthlyData">
                    <?= $chartMonthlyJson ?>
                </script>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                <?php if (Permission::can('clientes', 'criar')): ?>
                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars($url('customers/create'), ENT_QUOTES, 'UTF-8') ?>">Novo cliente</a>
                <?php endif; ?>
                <?php if (Permission::can('produtos', 'criar')): ?>
                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars($url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
                <?php endif; ?>
                <?php if ($canVendas): ?>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Consultar vendas</a>
                <?php endif; ?>
                <?php if ($canFinanceiro): ?>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">Contas a receber</a>
                <?php endif; ?>
                <?php if ($canVendas || $canEstoque || $canFinanceiro): ?>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('reports'), ENT_QUOTES, 'UTF-8') ?>">Relatórios</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($canProdutos || $canEstoque): ?>
        <div class="col-lg-5">
            <div class="card-soft p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Estoque baixo</div>
                    <span class="badge badge-low"><?= $lowStockTotal ?> item(ns)</span>
                </div>
                <?php if ($lowStockProducts === []): ?>
                    <div class="text-muted">Nenhum produto abaixo do limite.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lowStockProducts as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small">
                                        <?= (int) $p->stock ?> un. · mín. <?= (int) $p->minStock ?>
                                    </div>
                                </div>
                                <span class="badge text-bg-warning">Baixo</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($lowStockTotal > count($lowStockProducts) && $canEstoque): ?>
                        <div class="mt-2">
                            <a class="small" href="<?= htmlspecialchars($url('reports/low-stock'), ENT_QUOTES, 'UTF-8') ?>">
                                Ver todos (<?= $lowStockTotal ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($canFinanceiro && $overdueAccounts !== []): ?>
    <div class="row g-3 mt-0">
        <div class="col-12">
            <div class="card-soft p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="fw-semibold">Contas vencidas</div>
                        <div class="text-muted small">Pendente ou parcial com vencimento ultrapassado</div>
                    </div>
                    <a class="btn btn-sm btn-outline-danger" href="<?= htmlspecialchars($url('finance/accounts-receivable'), ENT_QUOTES, 'UTF-8') ?>">
                        Gerenciar contas
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 js-datatable">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Vencimento</th>
                                <th class="text-end">Em aberto</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueAccounts as $ar): ?>
                                <?php
                                if (!$ar instanceof AccountsReceivable)
                                {
                                    continue;
                                }
                                $remaining = $ar->remaining_amount ?? $ar->amount;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $ar->customer_name, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($ar->due_date)), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end">R$ <?= htmlspecialchars($fmt((string) $remaining), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= AccountsReceivable::STATUS_BADGE[$ar->status] ?? 'secondary' ?>">
                                            <?= htmlspecialchars(AccountsReceivable::STATUS_LABELS[$ar->status] ?? $ar->status, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($overdueCount > count($overdueAccounts)): ?>
                    <div class="mt-2 text-muted small">
                        Exibindo <?= count($overdueAccounts) ?> de <?= $overdueCount ?> conta(s) vencida(s).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>