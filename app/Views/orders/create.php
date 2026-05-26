<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Customer> $customers */
/** @var list<\App\Models\Product> $products */

$productPayload = [];
foreach ($products as $p)
{
    $productPayload[] = [
        'id' => $p->id,
        'name' => $p->name,
        'price' => $p->price,
        'stock' => $p->stock,
        'type' => $p->type,
        'sku' => $p->sku,
    ];
}

?>
<div class="mb-3">
    <h1 class="h3 mb-1">Nova venda</h1>
    <div class="text-muted">Venda híbrida: produtos (com estoque) e serviços (sem estoque) na mesma venda.</div>
</div>

<div class="card-soft p-3 p-md-4">
    <form id="orderForm" class="needs-validation" novalidate>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Cliente</label>
                <select class="form-select" id="customerId" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Selecione um cliente.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="installmentCount">Parcelas</label>
                <select class="form-select" id="installmentCount">
                    <option value="1">À vista (1x — sem parcelamento)</option>
                    <?php for ($n = 2; $n <= 12; $n++): ?>
                        <option value="<?= $n ?>"><?= $n ?>x (vencimento a cada 30 dias)</option>
                    <?php endfor; ?>
                </select>
                <div class="form-text">A partir de 2x o valor é dividido automaticamente.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-md-end gap-2">
                <button class="btn btn-outline-primary" type="button" id="btnAddLine">
                    <i class="bi bi-plus-circle"></i> Adicionar item
                </button>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold">Itens da venda</div>
            <div class="text-muted small">Mínimo: 1 item</div>
        </div>

        <div id="lines" class="vstack gap-2"></div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
            <div class="fs-5">
                Total: <span class="fw-bold" id="orderTotal">R$ 0,00</span>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                <button class="btn btn-primary" type="submit" id="btnSubmit">
                    <span class="submit-label">Registrar venda</span>
                    <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<template id="lineTemplate">
    <div class="order-line" data-line>
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Produto / serviço</label>
                <select class="form-select product-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p->id ?>"
                            data-price="<?= htmlspecialchars((string) $p->price, ENT_QUOTES, 'UTF-8') ?>"
                            data-stock="<?= (int) $p->stock ?>"
                            data-type="<?= htmlspecialchars($p->type, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($p->isService()): ?>
                                (serviço)
                            <?php else: ?>
                                (estoque: <?= (int) $p->stock ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantidade</label>
                <input class="form-control qty-input" type="number" min="1" step="1" value="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Preço (snapshot)</label>
                <div class="form-control bg-light unit-price" data-unit-price>R$ —</div>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-outline-danger" type="button" data-remove-line title="Remover">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="text-muted small mt-1" data-help></div>
    </div>
</template>

<script>
    window.__PRODUCTS__ = <?= json_encode($productPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
    window.__ORDER_STORE_URL__ = <?= json_encode($url('api/orders'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
</script>
<script src="<?= htmlspecialchars($url('assets/js/order_create.js'), ENT_QUOTES, 'UTF-8') ?>"></script>