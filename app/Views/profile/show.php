<?php

declare(strict_types=1);

use App\Services\CompanyRoleService;

/** @var callable(string):string $url */
/** @var \App\Models\User $user */
/** @var string|null $companyName */
/** @var string|null $companyRole */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$old = $old ?? [];
$roleLabel = $companyRole !== null ? (new CompanyRoleService())->label($companyRole) : null;

?>
<div class="mb-3">
    <h1 class="h3 mb-1">Meu perfil</h1>
    <div class="text-muted">Dados da conta e contexto da empresa ativa</div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-soft p-3 p-md-4">
            <form method="post" action="<?= htmlspecialchars($url('profile/update'), ENT_QUOTES, 'UTF-8') ?>">
                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" required
                        value="<?= htmlspecialchars((string) ($old['name'] ?? $user->name), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" required
                        value="<?= htmlspecialchars((string) ($old['email'] ?? $user->email), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Salvar perfil</button>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('profile/password'), ENT_QUOTES, 'UTF-8') ?>">Alterar senha</a>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-soft p-3 p-md-4">
            <h2 class="h6 text-muted text-uppercase">Sessão</h2>
            <dl class="mb-0">
                <dt class="small text-muted">Papel global</dt>
                <dd><span class="badge text-bg-light border"><?= htmlspecialchars($user->role, ENT_QUOTES, 'UTF-8') ?></span></dd>
                <dt class="small text-muted mt-2">Empresa ativa</dt>
                <dd><?= htmlspecialchars($companyName ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                <?php if ($roleLabel !== null): ?>
                    <dt class="small text-muted mt-2">Papel na empresa</dt>
                    <dd><span class="badge text-bg-primary"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span></dd>
                <?php endif; ?>
            </dl>
            <a class="btn btn-sm btn-outline-primary mt-3" href="<?= htmlspecialchars($url('select-company'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-building"></i> Trocar empresa
            </a>
        </div>
    </div>
</div>