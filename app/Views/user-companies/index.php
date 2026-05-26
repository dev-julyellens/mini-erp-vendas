<?php

declare(strict_types=1);

use App\Services\CompanyRoleService;

/** @var callable(string):string $url */
/** @var list<\App\Models\UserCompany> $links */
/** @var list<string> $companyRoles */
/** @var list<\App\Models\Company> $companies */
/** @var bool $isPlatformAdmin */
/** @var int $companyId */
/** @var string $search */
/** @var string $status */
/** @var string $roleFilter */

$roleService = new CompanyRoleService();

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Vínculos usuário ↔ empresa</h1>
        <div class="text-muted">Papéis e acesso por empresa</div>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attachModal">
        <i class="bi bi-link-45deg"></i> Novo vínculo
    </button>
</div>

<form class="row g-2 mb-3" method="get" action="<?= htmlspecialchars($url('user-companies'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="col-md-4">
        <input type="search" name="q" class="form-control" placeholder="Buscar" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-2">
        <select name="role" class="form-select">
            <option value="">Papel</option>
            <?php foreach ($companyRoles as $r): ?>
                <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= htmlspecialchars($roleService->label($r), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativos</option>
        </select>
    </div>
    <?php if ($isPlatformAdmin): ?>
        <div class="col-md-3">
            <select name="company_id" class="form-select">
                <option value="">Todas empresas</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= (int) $c->id ?>" <?= $companyId === $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-auto"><button class="btn btn-primary btn-md" type="submit"><i class="bi bi-funnel"></i> Filtrar</button></div>
</form>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="4">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Empresa</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($link->user_name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($link->user_email, ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars($link->company_name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-primary"><?= htmlspecialchars($link->roleLabel(), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= $link->active ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                            <form class="d-inline" method="post" action="<?= htmlspecialchars($url('user-companies/update-role'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                <input type="hidden" name="user_id" value="<?= (int) $link->user_id ?>">
                                <input type="hidden" name="company_id" value="<?= (int) $link->company_id ?>">
                                <select name="role" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <?php foreach ($companyRoles as $r): ?>
                                        <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" <?= $link->role === $r ? 'selected' : '' ?>><?= htmlspecialchars($roleService->label($r), ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <form class="d-inline" method="post" action="<?= htmlspecialchars($url('user-companies/toggle-active'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                <input type="hidden" name="user_id" value="<?= (int) $link->user_id ?>">
                                <input type="hidden" name="company_id" value="<?= (int) $link->company_id ?>">
                                <input type="hidden" name="active" value="<?= $link->active ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-warning" type="submit"><?= $link->active ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                            <form class="d-inline" method="post" action="<?= htmlspecialchars($url('user-companies/detach'), ENT_QUOTES, 'UTF-8') ?>"
                                data-confirm="Remover este vínculo?">
                                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                <input type="hidden" name="user_id" value="<?= (int) $link->user_id ?>">
                                <input type="hidden" name="company_id" value="<?= (int) $link->company_id ?>">
                                <button class="btn btn-sm btn-destructive" type="submit">Remover</button>
                            </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = 'user-companies';
    $query = array_filter(['q' => $search, 'status' => $status, 'role' => $roleFilter, 'company_id' => $companyId > 0 ? (string) $companyId : '']);
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>

<div class="modal fade" id="attachModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="<?= htmlspecialchars($url('user-companies/attach'), ENT_QUOTES, 'UTF-8') ?>">
            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
            <div class="modal-header">
                <h5 class="modal-title">Novo vínculo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ID do usuário</label>
                    <input type="number" name="user_id" class="form-control" required min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">ID da empresa</label>
                    <input type="number" name="company_id" class="form-control" required min="1"
                        value="<?= $isPlatformAdmin ? '' : (string) \App\Helpers\CompanyContext::id() ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Papel na empresa</label>
                    <select name="role" class="form-select" required>
                        <?php foreach ($companyRoles as $r): ?>
                            <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($roleService->label($r), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Vincular</button>
            </div>
        </form>
    </div>
</div>