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

$title = 'Logs de acesso';
$subtitle = 'Registro de requisições autenticadas ao sistema';
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-3">
    <label class="form-label" for="filter_user_id">Usuário</label>
    <select class="form-select" id="filter_user_id" name="user_id">
        <option value="">Todos</option>
        <?php foreach ($users as $u): ?>
            <option value="<?= (int) $u['id'] ?>" <?= $filters['user_id'] === $u['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-md-3">
    <label class="form-label" for="filter_path">Caminho</label>
    <input type="text" class="form-control" id="filter_path" name="path"
        value="<?= htmlspecialchars($filters['path'], ENT_QUOTES, 'UTF-8') ?>"
        placeholder="/customers">
</div>
<div class="col-6 col-md-2">
    <label class="form-label" for="filter_date_from">De</label>
    <input type="date" class="form-control" id="filter_date_from" name="date_from"
        value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-2">
    <label class="form-label" for="filter_date_to">Até</label>
    <input type="date" class="form-control" id="filter_date_to" name="date_to"
        value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('access-logs');
$filterClearHref = $url('access-logs');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

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