<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var ?\App\Models\Category $category */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$errors = $errors ?? [];
$old = $old ?? null;
$isEdit = $category !== null;

$name = $old['name'] ?? ($category ? $category->name : '');
$description = $old['description'] ?? ($category ? (string) ($category->description ?? '') : '');

$title = $isEdit ? 'Editar categoria' : 'Nova categoria';
$subtitle = 'Nome único; usada no cadastro de produtos.';
$breadcrumbs = [
    ['label' => 'Categorias', 'href' => $url('categories')],
    ['label' => $title],
];
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="card-soft p-3 p-md-4" style="max-width: 720px;">
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

    <form method="post" action="<?= htmlspecialchars($url($isEdit ? 'categories/update' : 'categories/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $category->id ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                name="name" value="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>" required maxlength="120">
            <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string) $description, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <?php
        $mode = 'form-footer';
        $cancelHref = $url('categories');
        $saveLoadingText = 'Salvando...';
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>