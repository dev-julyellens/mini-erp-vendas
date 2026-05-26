<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var bool $canSales */
/** @var bool $canStock */
/** @var bool $canFinance */

$title = 'Relatórios';
$subtitle = 'Relatórios gerenciais com filtros e exportação PDF / Excel';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="row g-3">
    <?php if ($canSales): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-calendar-range text-primary fs-4"></i>
                    <h2 class="h6 mb-0">Vendas por período</h2>
                </div>
                <p class="text-muted small mb-3">Total de vendas agrupado por dia no intervalo selecionado.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/sales-period'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-people text-primary fs-4"></i>
                    <h2 class="h6 mb-0">Vendas por cliente</h2>
                </div>
                <p class="text-muted small mb-3">Ranking de clientes por volume e valor de vendas.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/sales-customer'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-box-seam text-primary fs-4"></i>
                    <h2 class="h6 mb-0">Vendas por produto</h2>
                </div>
                <p class="text-muted small mb-3">Receita e quantidade vendida por item do catálogo.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/sales-product'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-trophy text-warning fs-4"></i>
                    <h2 class="h6 mb-0">Produtos mais vendidos</h2>
                </div>
                <p class="text-muted small mb-3">Itens com maior quantidade vendida no período.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/top-products'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($canStock): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                    <h2 class="h6 mb-0">Estoque mínimo</h2>
                </div>
                <p class="text-muted small mb-3">Produtos com estoque igual ou abaixo do mínimo configurado.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/low-stock'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($canFinance): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-cash-stack text-success fs-4"></i>
                    <h2 class="h6 mb-0">Fluxo de caixa</h2>
                </div>
                <p class="text-muted small mb-3">Entradas e saídas com totais do período.</p>
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($url('reports/cash-flow'), ENT_QUOTES, 'UTF-8') ?>">Abrir</a>
            </div>
        </div>
    <?php endif; ?>
</div>