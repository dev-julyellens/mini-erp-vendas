<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Product> $products */
/** @var list<string> $types */
/** @var array<string, string> $typeLabels */
/** @var array<string, string> $errors */
/** @var array<string, string> $old */

$productId = $old['product_id'] ?? '';
$type = $old['type'] ?? 'entrada';
$quantity = $old['quantity'] ?? '';
$notes = $old['notes'] ?? '';

$title = 'Nova movimentação';
$subtitle = 'Entrada, saída, ajuste, perda ou inventário';
$breadcrumbs = [
    ['label' => 'Estoque', 'href' => $url('stock-movements')],
    ['label' => 'Nova movimentação'],
];
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="card-soft p-3 p-md-4" style="max-width: 36rem;">
    <form method="post" action="<?= htmlspecialchars($url('stock-movements/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

        <div class="mb-3">
            <label class="form-label" for="product_id">Produto</label>
            <select class="form-select <?= isset($errors['product_id']) ? 'is-invalid' : '' ?>" id="product_id" name="product_id" required>
                <option value="">Selecione…</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p->id ?>" <?= (string) $p->id === (string) $productId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?> (saldo: <?= (int) $p->stock ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['product_id'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['product_id'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="type">Tipo</label>
            <select class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" id="type" name="type" required>
                <?php foreach ($types as $t): ?>
                    <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $type === $t ? 'selected' : '' ?>>
                        <?= htmlspecialchars($typeLabels[$t] ?? $t, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['type'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['type'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="form-text">
                Para ajuste e inventário use quantidade com sinal (+ aumenta, − reduz). Demais tipos: valor positivo.
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="quantity">Quantidade</label>
            <input type="number" class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>"
                id="quantity" name="quantity" value="<?= htmlspecialchars($quantity, ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (isset($errors['quantity'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['quantity'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="notes">Observações</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <?php
        $mode = 'form-footer';
        $cancelHref = $url('stock-movements');
        $saveLabel = 'Registrar';
        $saveLoadingText = 'Registrando...';
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>