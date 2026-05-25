<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, string> $errors */
/** @var list<\App\Models\Company> $companies */
/** @var bool $canSwitch */

$errors = $errors ?? [];
$companies = $companies ?? [];
$canSwitch = $canSwitch ?? false;
$title = $canSwitch ? 'Trocar empresa' : 'Selecionar empresa';

?>
<h1 class="h4 mb-3"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<p class="text-muted small mb-4">
    <?= $canSwitch
        ? 'Escolha a empresa com a qual deseja trabalhar nesta sessão.'
        : 'Você tem acesso a mais de uma empresa. Selecione qual deseja utilizar.' ?>
</p>

<?php if (isset($errors['company_id'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['company_id'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (isset($errors['login'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['login'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('select-company'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="mb-3">
        <div class="list-group mb-3">
            <?php foreach ($companies as $company): ?>
                <label class="list-group-item list-group-item-action d-flex gap-2 align-items-center"
                    for="company_<?= (int) $company->id ?>">
                    <input class="form-check-input flex-shrink-0 mt-0" type="radio" name="company_id"
                        id="company_<?= (int) $company->id ?>" value="<?= (int) $company->id ?>" required>
                    <span class="text-break"><?= htmlspecialchars($company->name, ENT_QUOTES, 'UTF-8') ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-2" data-loading-text="Confirmando...">
        <i class="bi bi-building me-1"></i> Continuar
    </button>

    <?php if ($canSwitch): ?>
        <a class="btn btn-outline-secondary w-100" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
            Cancelar
        </a>
    <?php endif; ?>
</form>