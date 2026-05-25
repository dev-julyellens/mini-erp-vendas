<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\BackupLog> $logs */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<array{filename: string, size: int, created_at: string}> $files */
/** @var \App\Models\BackupSettings $settings */
/** @var bool $isAdmin */
/** @var array<string, string> $operationLabels */
/** @var array<string, string> $triggerLabels */
/** @var array<string, string> $statusLabels */
/** @var string $cronHint */
/** @var array{success?: string, error?: string} $flash */
/** @var callable(int): string $formatBytes */

$totalPages = max(1, (int) ceil($total / $perPage));

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Backup PostgreSQL</h1>
        <div class="text-muted">Exportação manual, agendamento automático e restauração do banco</div>
    </div>
    <?php if ($isAdmin): ?>
        <form method="post" action="<?= htmlspecialchars($url('backups/create'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-cloud-arrow-up"></i> Criar backup agora
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if (!$isAdmin): ?>
    <div class="alert alert-warning">
        Apenas administradores podem criar, restaurar ou configurar backups. Você pode visualizar os registros abaixo.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-5">
        <div class="card-soft p-3 p-md-4 h-100">
            <h2 class="h5 mb-3"><i class="bi bi-clock-history"></i> Agendamento automático</h2>
            <?php if ($isAdmin): ?>
                <form method="post" action="<?= htmlspecialchars($url('backups/settings'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" <?= $settings->enabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enabled">Backup automático diário</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="run_hour">Hora</label>
                        <input type="number" class="form-control" id="run_hour" name="run_hour" min="0" max="23"
                            value="<?= (int) $settings->run_hour ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="run_minute">Minuto</label>
                        <input type="number" class="form-control" id="run_minute" name="run_minute" min="0" max="59"
                            value="<?= (int) $settings->run_minute ?>" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Salvar agendamento</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="mb-1">
                    Status:
                    <span class="badge <?= $settings->enabled ? 'text-bg-success' : 'text-bg-secondary' ?>">
                        <?= $settings->enabled ? 'Ativo' : 'Inativo' ?>
                    </span>
                </p>
                <p class="text-muted mb-0 small">
                    Horário: <?= sprintf('%02d:%02d', $settings->run_hour, $settings->run_minute) ?> (diário)
                </p>
            <?php endif; ?>
            <hr>
            <p class="small text-muted mb-1">Comando para o agendador do sistema (cron / Task Scheduler):</p>
            <code class="small d-block text-break"><?= htmlspecialchars($cronHint, ENT_QUOTES, 'UTF-8') ?></code>
            <?php if ($settings->last_run_at !== null): ?>
                <p class="small text-muted mt-2 mb-0">Última execução automática: <?= htmlspecialchars($settings->last_run_at, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0"><i class="bi bi-file-earmark-zip"></i> Arquivos de backup</h2>
                <?php if ($isAdmin): ?>
                    <form method="post" action="<?= htmlspecialchars($url('backups/cleanup'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm" data-confirm="Remover backups antigos conforme política de retenção?">
                            Limpar antigos
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($files === []): ?>
                <p class="text-muted mb-0">Nenhum arquivo de backup disponível.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Criado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="font-monospace small"><?= htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($formatBytes($file['size']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($file['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url('backups/download?file=' . rawurlencode($file['filename'])), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php if ($isAdmin): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                data-filename="<?= htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card-soft p-3 p-md-4">
    <h2 class="h5 mb-3"><i class="bi bi-journal-text"></i> Logs de backup</h2>
    <?php if ($logs === []): ?>
        <p class="text-muted mb-0">Nenhum registro ainda.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Operação</th>
                        <th>Origem</th>
                        <th>Arquivo</th>
                        <th>Status</th>
                        <th>Usuário</th>
                        <th>Duração</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap small"><?= htmlspecialchars($log->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($operationLabels[$log->operation] ?? $log->operation, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($triggerLabels[$log->trigger_type] ?? $log->trigger_type, ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="font-monospace small"><?= htmlspecialchars($log->filename ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php
                                $badge = match ($log->status)
                                {
                                    'success' => 'text-bg-success',
                                    'failed' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($statusLabels[$log->status] ?? $log->status, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($log->status === 'failed' && $log->message !== null): ?>
                                    <div class="small text-danger mt-1"><?= htmlspecialchars($log->message, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= htmlspecialchars($log->user_name ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="small text-nowrap">
                                <?= $log->duration_ms !== null ? (int) $log->duration_ms . ' ms' : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <?php
            $path = 'backups';
            $query = [];
            require dirname(__DIR__) . '/partials/pagination.php';
            ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= htmlspecialchars($url('backups/restore'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="restoreModalLabel">Restaurar backup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger fw-semibold">Atenção: esta operação substitui os dados atuais do banco.</p>
                        <p class="mb-2">Arquivo: <code id="restoreFilename">—</code></p>
                        <input type="hidden" name="filename" id="restoreFilenameInput" value="">
                        <label class="form-label" for="confirm">Digite <strong>RESTAURAR</strong> para confirmar</label>
                        <input type="text" class="form-control" id="confirm" name="confirm" autocomplete="off" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Restaurar banco</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('restoreModal')?.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;
            var filename = button.getAttribute('data-filename') || '';
            document.getElementById('restoreFilename').textContent = filename;
            document.getElementById('restoreFilenameInput').value = filename;
            document.getElementById('confirm').value = '';
        });
    </script>
<?php endif; ?>