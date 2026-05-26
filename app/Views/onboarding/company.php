<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var \App\Models\Company|null $company */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$errors = $errors ?? [];
$old = $old ?? [];
$name = (string) ($old['name'] ?? $company?->name ?? '');
$taxId = (string) ($old['tax_id'] ?? $company?->tax_id ?? '');
$slug = (string) ($old['slug'] ?? $company?->slug ?? '');

$title = 'Configurar empresa';
$subtitle = 'Passo 1 de 2 — dados do seu tenant no sistema.';
require dirname(__DIR__) . '/components/auth-form-header.php';

?>
<?php if (isset($errors['name'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (isset($errors['slug'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['slug'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('onboarding/company'), ENT_QUOTES, 'UTF-8') ?>">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="mb-3">
        <label class="form-label" for="name">Nome da empresa</label>
        <input type="text" class="form-control" id="name" name="name" required maxlength="255"
            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label" for="tax_id">CNPJ / CPF (opcional)</label>
        <input type="text" class="form-control" id="tax_id" name="tax_id" data-mask-document autocomplete="off"
            placeholder="00.000.000/0000-00" maxlength="20"
            value="<?= htmlspecialchars($taxId, ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label" for="slug">Identificador (URL)</label>
        <input type="text" class="form-control" id="slug" name="slug" maxlength="80"
            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
            value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-text">Letras minúsculas, números e hífens. Ex.: minha-loja</div>
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading-text="Continuando...">Continuar</button>
</form>