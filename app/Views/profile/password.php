<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, string> $errors */
/** @var string $hint */

?>
<div class="mb-3">
    <h1 class="h3 mb-1">Alterar senha</h1>
    <a class="small text-muted" href="<?= htmlspecialchars($url('profile'), ENT_QUOTES, 'UTF-8') ?>">&larr; Voltar ao perfil</a>
</div>

<div class="card-soft p-3 p-md-4 col-lg-6">
    <p class="small text-muted"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="<?= htmlspecialchars($url('profile/password'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <div class="mb-3">
            <label class="form-label" for="current_password">Senha atual</label>
            <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Nova senha</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">Confirmar nova senha</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
        </div>
        <button type="submit" class="btn btn-primary">Atualizar senha</button>
    </form>
</div>