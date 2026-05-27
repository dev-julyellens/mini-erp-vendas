<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\InventoryCount;

/** @var callable(string):string $url */
/** @var \App\Models\InventoryCount $count */
/** @var list<\App\Models\InventoryCountLine> $lines */
/** @var int $pendingLines */

$title = 'Inventário #' . (int) $count->id;
$subtitle = $count->isOpen()
    ? ($pendingLines > 0 ? $pendingLines . ' item(ns) sem contagem' : 'Pronto para finalizar')
    : 'Inventário encerrado';
$breadcrumbs = [
    ['label' => 'Inventário físico', 'href' => $url('inventory')],
    ['label' => 'Inventário #' . (int) $count->id],
];
$actionsHtml = '<a class="btn btn-ghost" href="' . htmlspecialchars($url('inventory'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left"></i> Voltar</a>';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card-soft p-3 p-md-4">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <div>
                    <div class="text-muted small">Status</div>
                    <span class="badge text-bg-<?= htmlspecialchars(InventoryCount::statusBadge($count->status), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(InventoryCount::statusLabel($count->status), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div>
                    <div class="text-muted small">Início</div>
                    <div><?= htmlspecialchars(DateHelper::toBrDateTime($count->created_at), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($count->finalized_at !== null): ?>
                    <div>
                        <div class="text-muted small">Finalizado em</div>
                        <div><?= htmlspecialchars(DateHelper::toBrDateTime($count->finalized_at), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($count->notes !== null && $count->notes !== ''): ?>
                <hr>
                <div class="text-muted small">Observações</div>
                <div><?= htmlspecialchars($count->notes, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($count->isOpen() && Permission::can('estoque', 'editar')): ?>
    <form method="post" action="<?= htmlspecialchars($url('inventory/save-lines'), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="inventory_count_id" value="<?= (int) $count->id ?>">
    <?php endif; ?>

    <div class="card-soft p-3 p-md-4">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>SKU</th>
                        <th class="text-end">Sistema</th>
                        <th class="text-end">Contado</th>
                        <th class="text-end">Variação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <?php
                        $variance = $line->variance();
                        $varianceClass = '';
                        if ($variance !== null)
                        {
                            if ($variance > 0)
                            {
                                $varianceClass = 'text-success';
                            }
                            elseif ($variance < 0)
                            {
                                $varianceClass = 'text-danger';
                            }
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($line->product_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small"><?= htmlspecialchars((string) ($line->product_sku ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= (int) $line->system_qty ?></td>
                            <td class="text-end" style="min-width: 7rem;">
                                <?php if ($count->isOpen() && Permission::can('estoque', 'editar')): ?>
                                    <input
                                        class="form-control form-control-sm text-end"
                                        type="number"
                                        min="0"
                                        step="1"
                                        name="lines[<?= (int) $line->id ?>]"
                                        value="<?= $line->counted_qty !== null ? (int) $line->counted_qty : '' ?>"
                                        aria-label="Quantidade contada de <?= htmlspecialchars((string) ($line->product_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <?= $line->counted_qty !== null ? (int) $line->counted_qty : '—' ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold <?= htmlspecialchars($varianceClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($variance === null): ?>
                                    —
                                <?php elseif ($variance > 0): ?>
                                    +<?= $variance ?>
                                <?php else: ?>
                                    <?= $variance ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($count->isOpen() && Permission::can('estoque', 'editar')): ?>
            <div class="d-flex flex-wrap gap-2 mt-3 justify-content-between align-items-center">
                <button type="submit" class="btn btn-outline">
                    <i class="bi bi-save"></i> Salvar contagens
                </button>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (Permission::can('estoque', 'excluir')): ?>
                        <button type="button" class="btn btn-destructive" data-bs-toggle="modal" data-bs-target="#cancel-inventory-modal">
                            Cancelar inventário
                        </button>
                    <?php endif; ?>
                    <?php if (Permission::can('estoque', 'criar')): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#finalize-inventory-modal"
                            <?= $pendingLines > 0 ? 'disabled title="Preencha todas as contagens"' : '' ?>>
                            Finalizar inventário
                        </button>
                    <?php endif; ?>
                </div>
            </div>
    </form>
<?php endif; ?>
</div>

<?php if ($count->isOpen() && Permission::can('estoque', 'criar')): ?>
    <div class="modal fade" id="finalize-inventory-modal" tabindex="-1" aria-labelledby="finalize-inventory-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= htmlspecialchars($url('inventory/finalize'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $count->id ?>">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="finalize-inventory-title">Finalizar inventário</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    As diferenças serão aplicadas ao estoque com movimentações do tipo inventário. Deseja continuar?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($count->isOpen() && Permission::can('estoque', 'excluir')): ?>
    <div class="modal fade" id="cancel-inventory-modal" tabindex="-1" aria-labelledby="cancel-inventory-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= htmlspecialchars($url('inventory/cancel'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $count->id ?>">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="cancel-inventory-title">Cancelar inventário</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    O inventário será cancelado sem alterar o estoque. Confirma?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-destructive">Cancelar inventário</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>