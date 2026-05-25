<?php

declare(strict_types=1);

/** @var string $token */
/** @var array<string, string> $errors */

$errors = $errors ?? [];

?>
<p class="text-muted small mb-4">Defina uma nova senha para sua conta.</p>

<form method="post" action="<?= htmlspecialchars($url('reset-password'), ENT_QUOTES, 'UTF-8') ?>">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label" for="password">Nova senha</label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
            id="password" name="password" autocomplete="new-password" minlength="8" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password_confirm">Confirmar senha</label>
        <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
            id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
        <?php if (isset($errors['password_confirm'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <?php if (isset($errors['token'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['token'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-100 mb-3">Redefinir senha</button>

    <div class="text-center">
        <a class="small text-decoration-none" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">
            Voltar ao login
        </a>
    </div>
</form>