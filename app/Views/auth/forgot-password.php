<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var ?string $resetUrl */

$errors = $errors ?? [];
$old = $old ?? [];
$email = (string) ($old['email'] ?? '');

?>
<p class="text-muted small mb-4">Informe seu e-mail cadastrado. Enviaremos um link para redefinir a senha.</p>

<?php if (!empty($resetUrl)): ?>
    <div class="alert alert-info small">
        <strong>Ambiente de desenvolvimento:</strong> use o link abaixo para redefinir a senha.
        <div class="mt-2 text-break">
            <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('forgot-password'), ENT_QUOTES, 'UTF-8') ?>">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
            id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email" required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">Enviar link de recuperação</button>

    <div class="text-center">
        <a class="small text-decoration-none" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">
            Voltar ao login
        </a>
    </div>
</form>