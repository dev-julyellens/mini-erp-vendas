<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var list<\App\Models\AuditLog> $logs */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<array{id: int, name: string, email: string}> $users */
/** @var array{user_id: ?int, date_from: string, date_to: string, entity: string} $filters */
/** @var array<string, string> $actionLabels */
/** @var array<string, string> $entityLabels */
/** @var list<string> $entities */

$formatJson = static function (?array $data): string
{
    if ($data === null || $data === [])
    {
        return '—';
    }

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return $json !== false ? $json : '—';
};

$queryBase = static function (array $override = []) use ($filters, $page): string
{
    $params = array_filter([
        'user_id' => $filters['user_id'],
        'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
        'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null,
        'entity' => $filters['entity'] !== '' ? $filters['entity'] : null,
        'page' => $page > 1 ? $page : null,
    ], static fn($v) => $v !== null && $v !== '');

    $params = array_merge($params, $override);
    $params = array_filter($params, static fn($v) => $v !== null && $v !== '');

    return $params === [] ? '' : '?' . http_build_query($params);
};

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Auditoria</h1>
        <div class="text-muted">Rastreabilidade de operações críticas do sistema</div>
    </div>
</div>

<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-md-3">
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
        <div class="col-md-2">
            <label class="form-label" for="entity">Entidade</label>
            <select class="form-select" id="entity" name="entity">
                <option value="">Todas</option>
                <?php foreach ($entities as $ent): ?>
                    <option value="<?= htmlspecialchars($ent, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['entity'] === $ent ? 'selected' : '' ?>>
                        <?= htmlspecialchars($entityLabels[$ent] ?? $ent, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_from">De</label>
            <input type="date" class="form-control" id="date_from" name="date_from"
                value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">Até</label>
            <input type="date" class="form-control" id="date_to" name="date_to"
                value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>ID</th>
                    <th>IP</th>
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
                        <td class="small text-muted"><?= htmlspecialchars((string) ($log->ip_address ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
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
                            <dt class="col-sm-3">User-Agent</dt>
                            <dd class="col-sm-9 text-break"><?= htmlspecialchars((string) ($log->user_agent ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h3 class="h6">Valores anteriores</h3>
                                <pre class="bg-light border rounded p-2 small mb-0" style="max-height: 14rem; overflow: auto;"><?= htmlspecialchars($formatJson($log->old_values), ENT_QUOTES, 'UTF-8') ?></pre>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6">Valores novos</h3>
                                <pre class="bg-light border rounded p-2 small mb-0" style="max-height: 14rem; overflow: auto;"><?= htmlspecialchars($formatJson($log->new_values), ENT_QUOTES, 'UTF-8') ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php
    $totalPages = (int) ceil($total / max(1, $perPage));
    if ($totalPages > 1):
    ?>
        <nav class="mt-3" aria-label="Paginação auditoria">
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($url('audit-logs' . $queryBase(['page' => $p])), ENT_QUOTES, 'UTF-8') ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>