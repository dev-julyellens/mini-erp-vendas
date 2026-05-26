<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var \App\Models\Company|null $company */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$isEdit = $company !== null;
$old = $old ?? [];
$name = (string) ($old['name'] ?? ($company->name ?? ''));
$taxId = (string) ($old['tax_id'] ?? ($company->tax_id ?? ''));
$slug = (string) ($old['slug'] ?? ($company->slug ?? ''));

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar empresa' : 'Nova empresa' ?></h1>
    <a class="small text-muted" href="<?= htmlspecialchars($url('admin/companies'), ENT_QUOTES, 'UTF-8') ?>">&larr; Voltar</a>
</div>

<div class="card-soft p-3 p-md-4 col-lg-8">
    <form method="post" action="<?= htmlspecialchars($url($isEdit ? 'admin/companies/update' : 'admin/companies/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $company->id ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="name">Nome *</label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="slug">Slug *</label>
            <input class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" required value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['slug'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="tax_id">CNPJ / Documento</label>
            <input class="form-control <?= isset($errors['tax_id']) ? 'is-invalid' : '' ?>" id="tax_id" name="tax_id" value="<?= htmlspecialchars($taxId, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['tax_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['tax_id'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <?php
        $mode = 'form-footer';
        $cancelHref = $url('admin/companies');
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>