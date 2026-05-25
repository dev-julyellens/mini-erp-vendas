<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Services\AuditService;

/** @var callable(string):string $url */
/** @var list<\App\Models\AuditLog> $logs */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<array{id: int, name: string, email: string}> $users */
/** @var array{user_id: ?int, date_from: string, date_to: string, entity: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var array<string, string> $actionLabels */
/** @var array<string, string> $entityLabels */
/** @var list<string> $entities */

$displayEntity = in_array($filters['entity'], $entities, true) ? $filters['entity'] : '';

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Auditoria</h1>
        <div class="text-muted">Rastreabilidade de operações críticas do sistema</div>
    </div>
</div>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end filter-form">
        <div class="col-12 col-md-3">
            <label class="form-label" for="user_id">Usuário</label>
            <select class="form-select" id="user_id" name="user_id">
                <option value="">Todos</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= $filters['user_id'] === $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="entity">Entidade</label>
            <select class="form-select" id="entity" name="entity">
                <option value="">Todas</option>
                <?php foreach ($entities as $ent): ?>
                    <option value="<?= htmlspecialchars($ent, ENT_QUOTES, 'UTF-8') ?>" <?= $displayEntity === $ent ? 'selected' : '' ?>>
                        <?= htmlspecialchars($entityLabels[$ent] ?? $ent, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date_from">De</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date_to">Até</label>
            <input type="date" class="form-control" id="date_to" name="date_to"
                value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-md-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="6">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>ID</th>
                    <th class="d-none d-lg-table-cell">IP</th>
                    <th class="text-end">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $modalId = 'audit-modal-' . $log->id;
                    $actionLabel = $actionLabels[$log->action] ?? $log->action;
                    $entityLabel = $entityLabels[$log->entity] ?? $log->entity;
                    ?>
                    <tr>
                        <td class="text-muted small text-nowrap">
                            <?= htmlspecialchars(DateHelper::toBrDateTime($log->created_at), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?php if ($log->user_name !== null): ?>
                                <div class="fw-semibold"><?= htmlspecialchars($log->user_name, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) $log->user_email, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php else: ?>
                                <span class="text-muted">Sistema</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $log->entity_id !== null ? (int) $log->entity_id : '—' ?></td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= htmlspecialchars((string) ($log->ip_address ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>">
                                Ver
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($logs === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhum registro encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php foreach ($logs as $log): ?>
        <?php
        $modalId = 'audit-modal-' . $log->id;
        $actionLabel = $actionLabels[$log->action] ?? $log->action;
        ?>
        <div class="modal fade" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1"
            aria-labelledby="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>-label">
                            Registro #<?= (int) $log->id ?>
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row small mb-3">
                            <dt class="col-sm-3">Ação</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt class="col-sm-3">IP</dt>
                            <dd class="col-sm-9"><?= htmlspecialchars((string) ($log->ip_address ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt class="col-sm-3">User-Agent</dt>
                            <dd class="col-sm-9 text-break"><?= htmlspecialchars((string) ($log->user_agent ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h3 class="h6">Valores anteriores</h3>
                                <pre class="bg-light border rounded p-2 small mb-0" style="max-height: 14rem; overflow: auto;"><?= htmlspecialchars(AuditService::formatJsonDisplay($log->old_values), ENT_QUOTES, 'UTF-8') ?></pre>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6">Valores novos</h3>
                                <pre class="bg-light border rounded p-2 small mb-0" style="max-height: 14rem; overflow: auto;"><?= htmlspecialchars(AuditService::formatJsonDisplay($log->new_values), ENT_QUOTES, 'UTF-8') ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php
    $path = 'audit-logs';
    $query = $paginationQuery;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>