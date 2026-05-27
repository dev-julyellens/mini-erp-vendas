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

$title = 'Novo orçamento';
$subtitle = 'Sem movimentação de estoque até converter em venda.';
$breadcrumbs = [
    ['label' => 'Orçamentos', 'href' => $url('quotes')],
    ['label' => 'Novo orçamento'],
];
require dirname(__DIR__) . '/components/page-header.php';

?>
<div id="quoteAutosaveStatus" class="alert alert-light border py-2 px-3 mb-3 small d-none" role="status" aria-live="polite"></div>
<div class="card-soft p-3 p-md-4" id="quoteFormCard" data-ajax-skeleton data-ajax-skeleton-rows="4">
    <form id="quoteForm" class="needs-validation" novalidate data-no-loading>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="customerId">Cliente</label>
                <select class="form-select" id="customerId" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Selecione um cliente.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="validUntil">Validade</label>
                <input class="form-control" type="date" id="validUntil">
                <div class="form-text">Opcional.</div>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="quoteNotes">Observações</label>
                <input class="form-control" type="text" id="quoteNotes" maxlength="500" placeholder="Opcional">
            </div>
            <div class="col-12 d-flex justify-content-md-end">
                <button class="btn btn-outline" type="button" id="btnAddLine">
                    <i class="bi bi-plus-circle"></i> Adicionar item
                </button>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-semibold">Itens do orçamento</div>
            <div class="text-muted small">Mínimo: 1 item</div>
        </div>

        <div id="lines" class="vstack gap-2"></div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
            <div class="fs-5">
                Total: <span class="fw-bold" id="quoteTotal">R$ 0,00</span>
            </div>
            <?php
            $mode = 'form-footer';
            $cancelHref = $url('quotes');
            $saveLabel = 'Salvar orçamento';
            $submitButtonId = 'btnSubmit';
            $submitButtonExtraHtml = '<span class="spinner-border spinner-border-sm d-none ms-1" id="btnSpinner" role="status" aria-hidden="true"></span>';
            require dirname(__DIR__) . '/components/action-buttons.php';
            ?>
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
                <div class="form-control form-control-readonly unit-price" data-unit-price>R$ —</div>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-destructive" type="button" data-remove-line title="Remover">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="text-muted small mt-1" data-help></div>
    </div>
</template>

<script>
    window.__QUOTE_STORE_URL__ = <?= json_encode($url('quotes/store'), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
</script>
<script src="<?= htmlspecialchars($url('assets/js/autosave.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($url('assets/js/quote_create.js'), ENT_QUOTES, 'UTF-8') ?>"></script>