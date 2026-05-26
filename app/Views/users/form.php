<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var \App\Models\User|null $user */
/** @var list<string> $globalRoles */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$isEdit = $user !== null;
$old = $old ?? [];

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar usuário' : 'Novo usuário' ?></h1>
    <a class="small text-muted" href="<?= htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') ?>">&larr; Voltar</a>
</div>

<div class="card-soft p-3 p-md-4 col-lg-8">
    <form method="post" action="<?= htmlspecialchars($url($isEdit ? 'admin/users/update' : 'admin/users/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $user->id ?>"><?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="name">Nome *</label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" required
                value="<?= htmlspecialchars((string) ($old['name'] ?? $user->name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">E-mail *</label>
            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" required
                value="<?= htmlspecialchars((string) ($old['email'] ?? $user->email ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="role">Papel global *</label>
            <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" id="role" name="role" required>
                <?php foreach ($globalRoles as $r): ?>
                    <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($old['role'] ?? $user->role ?? '') === $r ? 'selected' : '' ?>><?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$isEdit): ?>
            <div class="mb-3">
                <label class="form-label" for="password">Senha *</label>
                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirm">Confirmar senha *</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
</div>