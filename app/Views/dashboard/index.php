<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
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

$actionsHtml = '';
if (Permission::can('vendas', 'criar'))
{
    $actionsHtml = '<a class="btn btn-primary" href="' . htmlspecialchars($url('orders/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Nova venda</a>';
}
$title = 'Dashboard gerencial';
$subtitle = 'KPIs e indicadores por área';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div id="dashboardRoot">
    <ul class="nav dashboard-tabs" role="tablist" aria-label="Áreas do dashboard">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link active" data-dash-tab="overview" role="tab" aria-selected="true">Visão geral</button>
        </li>
        <?php if ($canVendas): ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" data-dash-tab="comercial" role="tab" aria-selected="false">Comercial</button>
            </li>
        <?php endif; ?>
        <?php if ($canFinanceiro): ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" data-dash-tab="financeiro" role="tab" aria-selected="false">Financeiro</button>
            </li>
        <?php endif; ?>
        <?php if ($canEstoque || $canProdutos): ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" data-dash-tab="operacional" role="tab" aria-selected="false">Operacional</button>
            </li>
        <?php endif; ?>
        <?php if ($canVendas || $canFinanceiro): ?>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link" data-dash-tab="executivo" role="tab" aria-selected="false">Executivo</button>
            </li>
        <?php endif; ?>
    </ul>

    <section class="dash-panel is-active" data-dash-panel="overview" role="tabpanel">
        <div class="kpi-grid mb-4">
            <?php if ($canVendas): ?>
                <?php
                $label = 'Faturamento hoje';
                $value = 'R$ ' . htmlspecialchars($fmt((string) ($snapshot['today_amount'] ?? '0')), ENT_QUOTES, 'UTF-8');
                $hint = (int) ($snapshot['today_orders'] ?? 0) . ' pedido(s) pagos';
                $variant = 'stat-revenue-today';
                $href = $url('orders');
                require dirname(__DIR__) . '/components/kpi-card.php';
                $label = 'Vendas no mês';
                $value = 'R$ ' . htmlspecialchars($fmt((string) ($snapshot['month_amount'] ?? '0')), ENT_QUOTES, 'UTF-8');
                $hint = (int) ($snapshot['month_orders'] ?? 0) . ' pedido(s) no período';
                $variant = 'stat-revenue-month';
                $href = $url('reports/sales-period');
                require dirname(__DIR__) . '/components/kpi-card.php';
                ?>
            <?php endif; ?>
            <?php if ($canFinanceiro): ?>
                <?php
                $label = 'Contas vencidas';
                $value = (string) $overdueCount;
                $hint = 'R$ ' . $fmt($overdueTotal) . ' em aberto';
                $variant = 'stat-overdue';
                $href = $url('finance/accounts-receivable');
                require dirname(__DIR__) . '/components/kpi-card.php';
                ?>
            <?php endif; ?>
            <?php if ($canProdutos || $canEstoque): ?>
                <?php
                $label = 'Estoque baixo';
                $value = (string) $lowStockTotal;
                $hint = 'Produto(s) abaixo do mínimo';
                $variant = 'stat-stock-alert';
                $href = $url('reports/low-stock');
                require dirname(__DIR__) . '/components/kpi-card.php';
                ?>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card-soft p-3 p-md-4">
                    <div class="fw-semibold mb-2">Atalhos rápidos</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (Permission::can('clientes', 'criar')): ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($url('customers/create'), ENT_QUOTES, 'UTF-8') ?>">Novo cliente</a>
                        <?php endif; ?>
                        <?php if (Permission::can('produtos', 'criar')): ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($url('products/create'), ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
                        <?php endif; ?>
                        <?php if ($canVendas): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Vendas</a>
                        <?php endif; ?>
                        <?php if ($canFinanceiro): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') ?>">Financeiro</a>
                        <?php endif; ?>
                        <?php if ($canVendas || $canEstoque || $canFinanceiro): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($url('reports'), ENT_QUOTES, 'UTF-8') ?>">Relatórios</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($canProdutos || $canEstoque): ?>
                <div class="col-lg-5">
                    <div class="card-soft p-3 p-md-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Alertas de estoque</div>
                            <span class="badge badge-low"><?= $lowStockTotal ?></span>
                        </div>
                        <?php if ($lowStockProducts === []): ?>
                            <div class="text-muted small">Nenhum produto abaixo do limite.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach (array_slice($lowStockProducts, 0, 5) as $p): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="small fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge text-bg-warning badge-status"><?= (int) $p->stock ?> un.</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($canVendas): ?>
        <section class="dash-panel" data-dash-panel="comercial" role="tabpanel" hidden>
            <div class="kpi-grid mb-4">
                <?php
                $label = 'Total de vendas';
                $value = (string) (int) ($counts['orders'] ?? $ordersCount);
                $hint = 'Pedidos registrados';
                $variant = 'stat-orders';
                $href = $url('orders');
                require dirname(__DIR__) . '/components/kpi-card.php';
                if ($canClientes)
                {
                    $label = 'Clientes';
                    $value = (string) (int) ($counts['customers'] ?? $customersCount);
                    $hint = 'Base cadastrada';
                    $variant = 'stat-customers';
                    $href = $url('customers');
                    require dirname(__DIR__) . '/components/kpi-card.php';
                }
                ?>
            </div>
            <?php if ($hasCharts): ?>
                <div id="dashboardCharts" class="row g-3">
                    <div class="col-lg-7">
                        <div class="card-soft p-3 p-md-4 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <div class="fw-semibold">Faturamento diário</div>
                                    <div class="text-muted small">Últimos <?= count($dailySeries) ?> dias</div>
                                </div>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('reports/sales-period'), ENT_QUOTES, 'UTF-8') ?>">Relatório</a>
                            </div>
                            <div class="dashboard-chart-wrap">
                                <canvas id="chartDailyRevenue" aria-label="Faturamento diário"></canvas>
                            </div>
                            <script type="application/json" id="dashboardDailyData"><?= $chartDailyJson ?></script>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card-soft p-3 p-md-4 h-100">
                            <div class="fw-semibold mb-2">Top produtos (30 dias)</div>
                            <div class="dashboard-chart-wrap dashboard-chart-wrap--tall">
                                <canvas id="chartTopProducts" aria-label="Produtos mais vendidos"></canvas>
                            </div>
                            <script type="application/json" id="dashboardTopProductsData"><?= $chartTopJson ?></script>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-soft p-4 text-muted">Sem dados de vendas para gráficos no período.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($canFinanceiro): ?>
        <section class="dash-panel" data-dash-panel="financeiro" role="tabpanel" hidden>
            <div class="kpi-grid mb-4">
                <?php
                $label = 'Contas vencidas';
                $value = (string) $overdueCount;
                $hint = 'R$ ' . $fmt($overdueTotal) . ' em aberto';
                $variant = 'stat-overdue';
                $href = $url('finance/accounts-receivable');
                require dirname(__DIR__) . '/components/kpi-card.php';
                ?>
            </div>
            <?php if ($overdueAccounts !== []): ?>
                <div class="card-soft p-3 p-md-4 table-card">
                    <div class="fw-semibold mb-3">Contas vencidas recentes</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
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
                                        <td><?= htmlspecialchars(DateHelper::toBrDate($ar->due_date), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end">R$ <?= htmlspecialchars($fmt((string) $remaining), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= AccountsReceivable::STATUS_BADGE[$ar->status] ?? 'secondary' ?> badge-status">
                                                <?= htmlspecialchars(AccountsReceivable::STATUS_LABELS[$ar->status] ?? $ar->status, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-soft p-4 text-muted">Nenhuma conta vencida no momento.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($canEstoque || $canProdutos): ?>
        <section class="dash-panel" data-dash-panel="operacional" role="tabpanel" hidden>
            <div class="kpi-grid mb-4">
                <?php if ($canProdutos): ?>
                    <?php
                    $label = 'Produtos no catálogo';
                    $value = (string) (int) ($counts['products'] ?? $productsCount);
                    $hint = 'Itens cadastrados';
                    $variant = 'stat-products';
                    $href = $url('products');
                    require dirname(__DIR__) . '/components/kpi-card.php';
                    ?>
                <?php endif; ?>
                <?php
                $label = 'Alertas de estoque';
                $value = (string) $lowStockTotal;
                $hint = 'Abaixo do mínimo';
                $variant = 'stat-stock-alert';
                $href = $url('stock-movements');
                require dirname(__DIR__) . '/components/kpi-card.php';
                ?>
            </div>
            <div class="card-soft p-3 p-md-4">
                <div class="fw-semibold mb-3">Produtos com estoque baixo</div>
                <?php if ($lowStockProducts === []): ?>
                    <div class="text-muted">Nenhum produto abaixo do limite.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lowStockProducts as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= (int) $p->stock ?> un. · mín. <?= (int) $p->minStock ?></div>
                                </div>
                                <span class="badge text-bg-warning badge-status">Baixo</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($canVendas || $canFinanceiro): ?>
        <section class="dash-panel" data-dash-panel="executivo" role="tabpanel" hidden>
            <?php if ($hasCharts && $monthlySeries !== []): ?>
                <div class="card-soft p-3 p-md-4 mb-3">
                    <div class="fw-semibold mb-2">Vendas mensais</div>
                    <div class="text-muted small mb-3">Últimos <?= count($monthlySeries) ?> meses · pedidos pagos</div>
                    <div class="dashboard-chart-wrap">
                        <canvas id="chartMonthlySales" aria-label="Vendas mensais"></canvas>
                    </div>
                    <script type="application/json" id="dashboardMonthlyData"><?= $chartMonthlyJson ?></script>
                </div>
            <?php endif; ?>
            <div class="row g-3">
                <?php if ($canVendas): ?>
                    <div class="col-md-6">
                        <div class="card-soft p-3">
                            <div class="text-muted small">Faturamento mensal</div>
                            <div class="h4 mb-0">R$ <?= htmlspecialchars($fmt((string) ($snapshot['month_amount'] ?? '0')), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($canFinanceiro): ?>
                    <div class="col-md-6">
                        <div class="card-soft p-3">
                            <div class="text-muted small">Inadimplência (vencidas)</div>
                            <div class="h4 mb-0 text-danger">R$ <?= htmlspecialchars($fmt($overdueTotal), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
