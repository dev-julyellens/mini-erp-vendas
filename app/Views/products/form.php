<?php

declare(strict_types=1);

use App\Helpers\ProductPricing;

/** @var callable(string):string $url */
/** @var ?\App\Models\Product $product */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */
/** @var list<\App\Models\Category> $categories */
/** @var list<string> $units */

$errors = $errors ?? [];
$old = $old ?? null;
$isEdit = $product !== null;

$val = static function (string $key, mixed $default = '') use ($old, $product): string
{
    if ($old !== null && array_key_exists($key, $old))
    {
        return (string) $old[$key];
    }
    if ($product === null)
    {
        return (string) $default;
    }
    return match ($key)
    {
        'name' => $product->name,
        'description' => (string) ($product->description ?? ''),
        'sku' => $product->sku,
        'barcode' => (string) ($product->barcode ?? ''),
        'category_id' => $product->categoryId !== null ? (string) $product->categoryId : '',
        'unit_of_measure' => $product->unitOfMeasure,
        'type' => $product->type,
        'cost_price' => $product->costPrice,
        'price' => $product->price,
        'min_stock' => (string) $product->minStock,
        'stock' => (string) $product->stock,
        default => (string) $default,
    };
};

$name = $val('name');
$description = $val('description');
$sku = $val('sku');
$barcode = $val('barcode');
$categoryId = $val('category_id');
$unit = $val('unit_of_measure', 'UN');
$type = $val('type', ProductPricing::TYPE_PRODUCT);
$costPrice = $val('cost_price', '0');
$price = $val('price');
$minStock = $val('min_stock', '5');
$stock = $val('stock', '0');

$margins = ProductPricing::computeMargins($costPrice, $price !== '' ? $price : '0');
$marginDisplay = $margins['margin'] ?? '—';
$markupDisplay = $margins['markup'] ?? '—';

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>
    <div class="text-muted">SKU único, precificação com margem/markup e controle de estoque por tipo.</div>
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

    <form method="post" id="productForm"
        action="<?= htmlspecialchars($url($isEdit ? 'products/update' : 'products/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
        <?php endif; ?>

        <ul class="nav nav-pills mb-4 gap-2" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#tab-ident">Identificação</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#tab-price">Preços</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#tab-stock">Estoque</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-ident" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" id="productType">
                            <option value="<?= ProductPricing::TYPE_PRODUCT ?>" <?= $type === ProductPricing::TYPE_PRODUCT ? 'selected' : '' ?>>Produto</option>
                            <option value="<?= ProductPricing::TYPE_SERVICE ?>" <?= $type === ProductPricing::TYPE_SERVICE ? 'selected' : '' ?>>Serviço</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input class="form-control text-uppercase <?= isset($errors['sku']) ? 'is-invalid' : '' ?>"
                            name="sku" id="productSku" value="<?= htmlspecialchars($sku, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="ex: SKU-000100" required maxlength="50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Código de barras</label>
                        <input class="form-control <?= isset($errors['barcode']) ? 'is-invalid' : '' ?>"
                            name="barcode" value="<?= htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') ?>" maxlength="50">
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
                        <div class="form-text">
                            <a href="<?= htmlspecialchars($url('categories/create'), ENT_QUOTES, 'UTF-8') ?>">Nova categoria</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unidade</label>
                        <select class="form-select" name="unit_of_measure" id="productUnit">
                            <?php foreach ($units as $u): ?>
                                <option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= $unit === $u ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-price" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Preço de custo (R$)</label>
                        <input class="form-control" name="cost_price" id="costPrice"
                            value="<?= htmlspecialchars($costPrice, ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preço de venda (R$) <span class="text-danger">*</span></label>
                        <input class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                            name="price" id="salePrice" value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="ex: 19,90" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Margem %</label>
                        <input class="form-control bg-light" id="marginPercent" readonly
                            value="<?= htmlspecialchars((string) $marginDisplay, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Markup %</label>
                        <input class="form-control bg-light" id="markupPercent" readonly
                            value="<?= htmlspecialchars((string) $markupDisplay, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border mb-0 small">
                            Margem = (venda − custo) ÷ venda · Markup = (venda − custo) ÷ custo. Calculados automaticamente ao salvar.
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-stock" role="tabpanel">
                <div class="row g-3" id="stockFields">
                    <div class="col-md-4">
                        <label class="form-label">Estoque atual</label>
                        <input class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>"
                            name="stock" id="stockQty" type="number" min="0" step="1"
                            value="<?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estoque mínimo</label>
                        <input class="form-control <?= isset($errors['min_stock']) ? 'is-invalid' : '' ?>"
                            name="min_stock" id="minStockQty" type="number" min="0" step="1"
                            value="<?= htmlspecialchars($minStock, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">Alerta quando estoque &lt; mínimo.</div>
                    </div>
                </div>
                <div class="alert alert-info d-none mt-3 mb-0" id="serviceStockHint" role="alert">
                    Serviços não controlam estoque; quantidade fixada em zero.
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 pt-3 border-top">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
        </div>
    </form>
</div>

<script src="<?= htmlspecialchars($url('assets/js/product_form.js'), ENT_QUOTES, 'UTF-8') ?>"></script>