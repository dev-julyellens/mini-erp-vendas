<?php

declare(strict_types=1);

use App\Services\CompanyRoleService;

/** @var callable(string):string $url */
/** @var \App\Models\User $user */
/** @var array{theme: string, sidebar_collapsed: bool, sidebar_pinned: bool, dashboard_tab: string} $userPrefs */
/** @var bool $hasAvatar */
/** @var string|null $companyName */
/** @var string|null $companyRole */
/** @var list<array{company_id: int, company_name: string, role: string, active: bool}> $companyBindings */
/** @var string $effectiveRole */
/** @var list<string> $permissionKeys */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$old = $old ?? [];
$userPrefs = $userPrefs ?? [
    'theme' => 'light',
    'sidebar_collapsed' => false,
    'sidebar_pinned' => false,
    'dashboard_tab' => 'overview',
];
$hasAvatar = $hasAvatar ?? false;
$companyBindings = $companyBindings ?? [];
$permissionKeys = $permissionKeys ?? [];
$roleLabel = $companyRole !== null ? (new CompanyRoleService())->label($companyRole) : null;
$companyRoleService = new CompanyRoleService();

$initials = '';
foreach (preg_split('/\s+/', trim($user->name)) ?: [] as $part)
{
    if ($part !== '')
    {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        if (mb_strlen($initials) >= 2)
        {
            break;
        }
    }
}
if ($initials === '')
{
    $initials = '?';
}

$title = 'Meu perfil';
$subtitle = 'Dados pessoais, preferências e permissões';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-soft p-3 p-md-4 mb-3">
            <div class="d-flex align-items-start gap-3 mb-4 flex-wrap">
                <div class="profile-avatar-wrap">
                    <?php if ($hasAvatar): ?>
                        <img class="profile-avatar profile-avatar-img" src="<?= htmlspecialchars($url('profile/avatar'), ENT_QUOTES, 'UTF-8') ?>"
                            alt="Foto de <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <div class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></div>
                    <form method="post" action="<?= htmlspecialchars($url('profile/avatar'), ENT_QUOTES, 'UTF-8') ?>"
                        enctype="multipart/form-data" class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                        <input type="file" class="form-control form-control-sm" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                        <button type="submit" class="btn btn-sm btn-secondary" data-loading-text="Enviando...">Enviar foto</button>
                    </form>
                    <?php if ($hasAvatar): ?>
                        <form method="post" action="<?= htmlspecialchars($url('profile/avatar/remove'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1">
                            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                            <button type="submit" class="btn btn-sm btn-link text-danger px-0">Remover foto</button>
                        </form>
                    <?php endif; ?>
                    <div class="text-muted small">JPG, PNG ou WebP — até 2 MB</div>
                </div>
            </div>
            <form method="post" action="<?= htmlspecialchars($url('profile/update'), ENT_QUOTES, 'UTF-8') ?>" class="needs-validation" novalidate>
                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                <div class="form-section">
                    <div class="form-section-title">Dados pessoais</div>
                    <div class="mb-3">
                        <label class="form-label" for="name">Nome</label>
                        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" required autocomplete="name"
                            value="<?= htmlspecialchars((string) ($old['name'] ?? $user->name), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" required autocomplete="email"
                            value="<?= htmlspecialchars((string) ($old['email'] ?? $user->email), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" data-loading-text="Salvando...">Salvar perfil</button>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($url('profile/password'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-shield-lock"></i> Alterar senha
                    </a>
                </div>
            </form>
        </div>

        <div class="card-soft p-3 p-md-4 prefs-card" id="profilePrefsCard"
            data-prefs-url="<?= htmlspecialchars($url('profile/preferences'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-section-title">Preferências de interface</div>
            <p class="text-muted small">Sincronizadas com sua conta em todos os dispositivos.</p>
            <div class="mb-3">
                <label class="form-label" for="prefTheme">Tema</label>
                <select class="form-select" id="prefTheme" data-pref-theme>
                    <option value="light" <?= $userPrefs['theme'] === 'light' ? 'selected' : '' ?>>Claro</option>
                    <option value="dark" <?= $userPrefs['theme'] === 'dark' ? 'selected' : '' ?>>Escuro</option>
                </select>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="prefSidebarPinned" data-pref-sidebar-pinned
                    <?= $userPrefs['sidebar_pinned'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="prefSidebarPinned">Fixar menu lateral expandido</label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="prefSidebarCollapsed" data-pref-sidebar
                    <?= $userPrefs['sidebar_collapsed'] ? 'checked' : '' ?>
                    <?= $userPrefs['sidebar_pinned'] ? 'disabled' : '' ?>>
                <label class="form-check-label" for="prefSidebarCollapsed">Menu lateral recolhido por padrão</label>
            </div>
            <div class="mb-0">
                <label class="form-label" for="prefDashboardTab">Aba favorita do dashboard</label>
                <select class="form-select" id="prefDashboardTab" data-pref-dashboard-tab>
                    <option value="overview" <?= $userPrefs['dashboard_tab'] === 'overview' ? 'selected' : '' ?>>Visão geral</option>
                    <option value="comercial" <?= $userPrefs['dashboard_tab'] === 'comercial' ? 'selected' : '' ?>>Comercial</option>
                    <option value="financeiro" <?= $userPrefs['dashboard_tab'] === 'financeiro' ? 'selected' : '' ?>>Financeiro</option>
                    <option value="operacional" <?= $userPrefs['dashboard_tab'] === 'operacional' ? 'selected' : '' ?>>Operacional</option>
                    <option value="executivo" <?= $userPrefs['dashboard_tab'] === 'executivo' ? 'selected' : '' ?>>Executivo</option>
                </select>
            </div>
            <div class="small text-muted mt-2" id="prefSaveStatus" aria-live="polite"></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4 mb-3">
            <h2 class="h6 text-muted text-uppercase mb-3">Sessão atual</h2>
            <dl class="mb-0">
                <dt class="small text-muted">Papel global</dt>
                <dd><span class="badge text-bg-light border badge-status"><?= htmlspecialchars($user->role, ENT_QUOTES, 'UTF-8') ?></span></dd>
                <dt class="small text-muted mt-2">Papel efetivo (ACL)</dt>
                <dd><span class="badge text-bg-secondary badge-status"><?= htmlspecialchars($effectiveRole, ENT_QUOTES, 'UTF-8') ?></span></dd>
                <dt class="small text-muted mt-2">Empresa ativa</dt>
                <dd><?= htmlspecialchars($companyName ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                <?php if ($roleLabel !== null): ?>
                    <dt class="small text-muted mt-2">Papel na empresa</dt>
                    <dd><span class="badge text-bg-primary badge-status"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span></dd>
                <?php endif; ?>
            </dl>
            <a class="btn btn-sm btn-secondary mt-3 w-100" href="<?= htmlspecialchars($url('select-company'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-building"></i> Trocar empresa
            </a>
        </div>

        <?php if ($companyBindings !== []): ?>
            <div class="card-soft p-3 p-md-4 mb-3">
                <h2 class="h6 text-muted text-uppercase mb-3">Empresas vinculadas</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($companyBindings as $binding): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($binding['company_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($companyRoleService->label($binding['role']), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <span class="badge <?= $binding['active'] ? 'text-bg-success' : 'text-bg-secondary' ?> badge-status">
                                <?= $binding['active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card-soft p-3 p-md-4" id="permissoes">
            <h2 class="h6 text-muted text-uppercase mb-3">Permissões</h2>
            <?php if ($effectiveRole === 'admin'): ?>
                <p class="small text-muted mb-2">Administrador com acesso total ao sistema.</p>
            <?php elseif ($permissionKeys === []): ?>
                <p class="small text-muted mb-0">Nenhuma permissão explícita configurada.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap">
                    <?php foreach ($permissionKeys as $key): ?>
                        <span class="permission-chip"><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>