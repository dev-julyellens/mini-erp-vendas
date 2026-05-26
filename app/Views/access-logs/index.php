<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var callable(string):string $url */
/** @var list<\App\Models\AccessLog> $logs */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<array{id: int, name: string, email: string}> $users */
/** @var array{user_id: ?int, date_from: string, date_to: string, path: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var callable(string):string $maskEmail */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Logs de acesso</h1>
        <div class="text-muted">Registro de requisições autenticadas ao sistema</div>
    </div>
</div>

<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('access-logs'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end filter-form">
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
        <div class="col-12 col-md-3">
            <label class="form-label" for="path">Caminho</label>
            <input type="text" class="form-control" id="path" name="path"
                value="<?= htmlspecialchars($filters['path'], ENT_QUOTES, 'UTF-8') ?>"
                placeholder="/customers">
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
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-order='[[5, "desc"]]' data-dt-page-length="25">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>IP</th>
                    <th>Método</th>
                    <th>Caminho</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <?php if ($log->user_name !== null): ?>
                                <span class="fw-semibold"><?= htmlspecialchars($log->user_name, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($log->user_email !== null): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($maskEmail($log->user_email), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($log->ip_address, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($log->http_method, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="small font-monospace"><?= htmlspecialchars($log->path, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $log->status_code !== null ? (int) $log->status_code : '—' ?></td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($log->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($logs === []): ?>
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">Nenhum registro encontrado.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'access-logs';
    $query = $paginationQuery;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>