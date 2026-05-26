<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var \App\Models\User $user */
/** @var array<string, string> $errors */

use App\Services\PasswordPolicyService;

$hint = (new PasswordPolicyService())->requirementsHint();

$title = 'Redefinir senha';
$subtitle = $user->name . ' · ' . $user->email;
$breadcrumbs = [
    ['label' => 'Usuários', 'href' => $url('admin/users')],
    ['label' => $title],
];
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="card-soft p-3 p-md-4 col-lg-6">
    <p class="small text-muted"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="<?= htmlspecialchars($url('admin/users/reset-password'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <input type="hidden" name="id" value="<?= (int) $user->id ?>">
        <div class="mb-3">
            <label class="form-label" for="password">Nova senha</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">Confirmar</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
        </div>
        <?php
        $mode = 'form-footer';
        $cancelHref = $url('admin/users');
        $saveLabel = 'Redefinir';
        $saveLoadingText = 'Redefinindo...';
        require dirname(__DIR__) . '/components/action-buttons.php';
        ?>
    </form>
</div>