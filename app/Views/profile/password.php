<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, string> $errors */
/** @var string $hint */

$title = 'Alterar senha';
$subtitle = htmlspecialchars($hint, ENT_QUOTES, 'UTF-8');
$breadcrumbs = [
    ['label' => 'Meu perfil', 'href' => $url('profile')],
    ['label' => 'Alterar senha'],
];
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="card-soft p-3 p-md-4 col-lg-6">
    <form method="post" action="<?= htmlspecialchars($url('profile/password'), ENT_QUOTES, 'UTF-8') ?>" class="needs-validation" novalidate>
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <div class="mb-3">
            <label class="form-label" for="current_password">Senha atual</label>
            <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                id="current_password" name="current_password" required autocomplete="current-password">
            <?php if (isset($errors['current_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['current_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Nova senha</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                id="password" name="password" required autocomplete="new-password" data-password-strength>
            <div class="password-strength mt-2" data-password-strength-meter hidden>
                <div class="password-strength-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <span class="password-strength-fill"></span>
                </div>
                <div class="password-strength-label small text-muted" data-password-strength-label></div>
            </div>
            <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">Confirmar nova senha</label>
            <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                id="password_confirm" name="password_confirm" required autocomplete="new-password">
            <?php if (isset($errors['password_confirm'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <?php
        $mode = 'form-footer';
        $cancelHref = $url('profile');
        $saveLabel = 'Atualizar senha';
        $saveLoadingText = 'Salvando...';
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>