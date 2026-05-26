<?php

declare(strict_types=1);

use App\Helpers\ProductPricing;

/** @var callable(string):string $url */
/** @var ?\App\Models\Product $service */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */
/** @var list<\App\Models\Category> $categories */

$errors = $errors ?? [];
$old = $old ?? null;
$isEdit = $service !== null;

$val = static function (string $key, mixed $default = '') use ($old, $service): string
{
    if ($old !== null && array_key_exists($key, $old))
    {
        return (string) $old[$key];
    }
    if ($service === null)
    {
        return (string) $default;
    }
    return match ($key)
    {
        'name' => $service->name,
        'description' => (string) ($service->description ?? ''),
        'sku' => $service->sku,
        'barcode' => (string) ($service->barcode ?? ''),
        'category_id' => $service->categoryId !== null ? (string) $service->categoryId : '',
        'unit_of_measure' => $service->unitOfMeasure,
        'cost_price' => $service->costPrice,
        'price' => $service->price,
        'estimated_time_minutes' => $service->estimatedTimeMinutes !== null
            ? (string) $service->estimatedTimeMinutes
            : '',
        default => (string) $default,
    };
};

$name = $val('name');
$description = $val('description');
$sku = $val('sku');
$barcode = $val('barcode');
$categoryId = $val('category_id');
$unit = $val('unit_of_measure', 'HR');
$costPrice = $val('cost_price', '0');
$price = $val('price');
$estimatedTime = $val('estimated_time_minutes');

$margins = ProductPricing::computeMargins($costPrice, $price !== '' ? $price : '0');
$marginDisplay = $margins['margin'] ?? '—';
$markupDisplay = $margins['markup'] ?? '—';

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar serviço' : 'Novo serviço' ?></h1>
    <div class="text-muted">Valor padrão para vendas e tempo estimado opcional. Sem controle de estoque.</div>
</div>

<div class="card-soft p-3 p-md-4">
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrija os campos abaixo</div>
            <ul class="mb-0">
                <?php foreach ($errors as $field => $msg): ?>
                    <li><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" id="serviceForm"
        action="<?= htmlspecialchars($url($isEdit ? 'services/update' : 'services/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $service->id ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nome <span class="text-danger">*</span></label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                    name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">SKU <span class="text-danger">*</span></label>
                <input class="form-control text-uppercase <?= isset($errors['sku']) ? 'is-invalid' : '' ?>"
                    name="sku" id="serviceSku" value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="ex: SRV-000010" required maxlength="50">
            </div>
            <div class="col-md-4">
                <label class="form-label">Categoria</label>
                <select class="form-select" name="category_id">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat->id ?>" <?= (string) $cat->id === $categoryId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Unidade</label>
                <select class="form-select" name="unit_of_measure">
                    <?php foreach (['HR', 'UN'] as $u): ?>
                        <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= $unit === $u ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tempo estimado (min)</label>
                <input class="form-control <?= isset($errors['estimated_time_minutes']) ? 'is-invalid' : '' ?>"
                    type="number" min="1" step="1" name="estimated_time_minutes"
                    value="<?= htmlspecialchars($estimatedTime, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="ex: 60">
                <div class="form-text">Opcional. Referência para planejamento.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Custo (R$)</label>
                <input class="form-control" name="cost_price" id="serviceCostPrice"
                    value="<?= htmlspecialchars($costPrice, ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
            </div>
            <div class="col-md-4">
                <label class="form-label">Valor padrão (R$) <span class="text-danger">*</span></label>
                <input class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                    name="price" id="serviceSalePrice" value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="ex: 150,00" required>
                <div class="form-text">Usado como preço unitário ao incluir o serviço na venda.</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Margem %</label>
                <input class="form-control bg-light" id="serviceMarginPercent" readonly
                    value="<?= htmlspecialchars((string) $marginDisplay, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Markup %</label>
                <input class="form-control bg-light" id="serviceMarkupPercent" readonly
                    value="<?= htmlspecialchars((string) $markupDisplay, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <?php
        $mode = 'form-footer';
        $cancelHref = $url('services');
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>

<script src="<?= htmlspecialchars($url('assets/js/service_form.js'), ENT_QUOTES, 'UTF-8') ?>"></script>