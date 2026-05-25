<?php

declare(strict_types=1);

use App\Helpers\Permission;
use App\Services\StockService;

/** @var callable(string):string $url */
/** @var list<\App\Models\StockMovement> $movements */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<\App\Models\Product> $products */
/** @var array{product_id: ?int, date_from: string, date_to: string, type: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var list<string> $types */
/** @var array<string, string> $typeLabels */

$displayType = in_array($filters['type'], $types, true) ? $filters['type'] : '';
$canCreate = Permission::can('estoque', 'criar');

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Movimentações de estoque</h1>
        <div class="text-muted">Histórico rastreável; o saldo em produtos é atualizado automaticamente</div>
    </div>
    <?php if ($canCreate): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url('stock-movements/create'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-plus-lg"></i> Nova movimentação
        </a>
    <?php endif; ?>
</div>

<div class="card-soft p-3 p-md-4 mb-3">
    <form method="get" action="<?= htmlspecialchars($url('stock-movements'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label" for="product_id">Produto</label>
            <select class="form-select" id="product_id" name="product_id">
                <option value="">Todos</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p->id ?>" <?= $filters['product_id'] === $p->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="type">Tipo</label>
            <select class="form-select" id="type" name="type">
                <option value="">Todos</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $displayType === $t ? 'selected' : '' ?>>
                        <?= htmlspecialchars($typeLabels[$t] ?? $t, ENT_QUOTES, 'UTF-8') ?>
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
        <div class="col-12 col-md-2 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('stock-movements'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        </div>
    </form>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th class="text-end">Quantidade</th>
                    <th class="d-none d-md-table-cell">Referência</th>
                    <th class="d-none d-sm-table-cell">Usuário</th>
                    <th class="d-none d-lg-table-cell">Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                    <?php
                    $typeLabel = $typeLabels[$movement->type] ?? $movement->type;
                    $ref = '—';
                    if ($movement->reference_type !== null)
                    {
                        $ref = $movement->reference_type;
                        if ($movement->reference_id !== null)
                        {
                            $ref .= ' #' . $movement->reference_id;
                        }
                    }
                    ?>
                    <tr>
                        <td class="text-muted small text-nowrap">
                            <?= htmlspecialchars(\App\Helpers\DateHelper::toBrDateTime($movement->created_at), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string) $movement->product_name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small">ID <?= (int) $movement->product_id ?></div>
                        </td>
                        <td><span class="badge text-bg-light border"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-end font-monospace"><?= htmlspecialchars(StockService::signedQuantityDisplay($movement), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="d-none d-md-table-cell small"><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="d-none d-sm-table-cell small">
                            <?php if ($movement->user_name !== null): ?>
                                <?= htmlspecialchars($movement->user_name, ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="text-muted">Sistema</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= htmlspecialchars((string) ($movement->notes ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($movements === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhuma movimentação encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'stock-movements';
    $query = $paginationQuery;
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>