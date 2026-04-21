<?php

declare(strict_types=1);

/** @var ?\App\Models\Product $product */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$errors = $errors ?? [];
$old = $old ?? null;
$isEdit = $product !== null;

$name = $old['name'] ?? ($product ? $product->name : '');
$description = $old['description'] ?? ($product ? (string) ($product->description ?? '') : '');
$price = $old['price'] ?? ($product ? (string) $product->price : '');
$stock = $old['stock'] ?? ($product ? (string) $product->stock : '');

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>
    <div class="text-muted">Preço deve ser maior que zero; estoque não pode ser negativo.</div>
</div>

<div class="card-soft p-3 p-md-4" style="max-width: 920px;">
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrija os campos abaixo</div>
            <ul class="mb-0">
                <?php foreach ($errors as $msg): ?>
                    <li><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($url($isEdit ? 'products/update' : 'products/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string) $description, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Preço (R$)</label>
                <input class="form-control" name="price" value="<?= htmlspecialchars((string) $price, ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: 19.90" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estoque</label>
                <input class="form-control" name="stock" type="number" min="0" step="1" value="<?= htmlspecialchars((string) $stock, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
        </div>
    </form>
</div>
