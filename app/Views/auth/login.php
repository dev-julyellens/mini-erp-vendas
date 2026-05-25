<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$errors = $errors ?? [];
$old = $old ?? [];
$email = (string) ($old['email'] ?? '');
$loginError = $errors['login'] ?? null;

?>
<?php if ($loginError !== null): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>" novalidate>
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
            id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="username" required autofocus>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Senha</label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
            id="password" name="password" autocomplete="current-password" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3" data-loading-text="Entrando...">
        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
    </button>

    <div class="text-center">
        <a class="small text-decoration-none" href="<?= htmlspecialchars($url('forgot-password'), ENT_QUOTES, 'UTF-8') ?>">
            Esqueci minha senha
        </a>
    </div>
</form>

<?php if (\App\Helpers\AppConfig::isDebug()): ?>
    <p class="text-muted small text-center mt-4 mb-0">
        Dev: <code>admin@mini-erp.local</code> / <code>Admin@123</code>
    </p>
<?php endif; ?>