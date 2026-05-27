<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\InventoryCount;

/** @var callable(string):string $url */
/** @var list<\App\Models\InventoryCount> $counts */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array{status: string} $filters */
/** @var list<string> $statusOptions */
/** @var bool $hasOpenCount */

$title = 'Inventário físico';
$subtitle = 'Contagem de estoque com ajustes automáticos ao finalizar';
$actionsHtml = '';
if (Permission::can('estoque', 'criar') && !$hasOpenCount)
{
    $actionsHtml = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#start-inventory-modal">'
        . '<i class="bi bi-clipboard-check"></i> Iniciar inventário</button>';
}
require dirname(__DIR__) . '/components/page-header.php';

if ($hasOpenCount)
{
    echo '<div class="alert alert-warning">Existe um inventário em andamento. Finalize ou cancele antes de abrir outro.</div>';
}

ob_start();
?>
<div class="col-md-4">
    <label class="form-label" for="filter_status">Status</label>
    <select class="form-select" id="filter_status" name="status">
        <option value="">Todos</option>
        <?php foreach ($statusOptions as $status): ?>
            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                <?= htmlspecialchars(InventoryCount::statusLabel($status), ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('inventory');
$filterClearHref = $url('inventory');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="5">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Responsável</th>
                    <th>Início</th>
                    <th>Encerramento</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($counts as $c): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int) $c->id ?></td>
                        <td>
                            <span class="badge text-bg-<?= htmlspecialchars(InventoryCount::statusBadge($c->status), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(InventoryCount::statusLabel($c->status), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars((string) ($c->created_by_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($c->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-muted small">
                            <?= $c->finalized_at !== null
                                ? htmlspecialchars(DateHelper::toBrDateTime($c->finalized_at), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="text-end">
                            <?php
                            $mode = 'table-row';
                            $canEdit = false;
                            $canDelete = false;
                            $detailsHref = $url('inventory/show?id=' . $c->id);
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($counts === []): ?>
                    <tr class="empty-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="bi bi-clipboard-check"></i>
                                Nenhum inventário registrado.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'inventory';
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>

<?php if (Permission::can('estoque', 'criar') && !$hasOpenCount): ?>
    <div class="modal fade" id="start-inventory-modal" tabindex="-1" aria-labelledby="start-inventory-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= htmlspecialchars($url('inventory/start'), ENT_QUOTES, 'UTF-8') ?>">
                <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="start-inventory-title">Iniciar inventário físico</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Será criada uma lista com todos os produtos físicos e o estoque do sistema no momento.
                        Ao finalizar, as diferenças geram movimentações do tipo inventário.
                    </p>
                    <label class="form-label" for="inventoryNotes">Observações</label>
                    <input class="form-control" type="text" name="notes" id="inventoryNotes" maxlength="500" placeholder="Opcional">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Iniciar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>