<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\Quote;

/** @var callable(string):string $url */
/** @var list<\App\Models\Quote> $quotes */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<\App\Models\Customer> $customers */
/** @var array{customer_id: ?int, status: string} $filters */
/** @var list<string> $statusOptions */

$title = 'Orçamentos';
$subtitle = 'Propostas comerciais sem baixa de estoque até a conversão em venda';
$actionsHtml = '';
if (\App\Helpers\Permission::can('vendas', 'criar'))
{
    $actionsHtml = '<a class="btn btn-primary" href="' . htmlspecialchars($url('quotes/create'), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-plus-lg"></i> Novo orçamento</a>';
}
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-md-4">
    <label class="form-label" for="filter_customer_id">Cliente</label>
    <select class="form-select" id="filter_customer_id" name="customer_id">
        <option value="">Todos</option>
        <?php foreach ($customers as $c): ?>
            <option value="<?= (int) $c->id ?>" <?= $filters['customer_id'] === $c->id ? 'selected' : '' ?>>
                <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-4">
    <label class="form-label" for="filter_status">Status</label>
    <select class="form-select" id="filter_status" name="status">
        <option value="">Todos</option>
        <?php foreach ($statusOptions as $status): ?>
            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                <?= htmlspecialchars(Quote::statusLabel($status), ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('quotes');
$filterClearHref = $url('quotes');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="6">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Validade</th>
                    <th>Criado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td class="fw-semibold">#<?= (int) $q->id ?></td>
                        <td><?= htmlspecialchars((string) ($q->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= htmlspecialchars(number_format((float) $q->total_amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge text-bg-<?= htmlspecialchars(Quote::statusBadge($q->status), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(Quote::statusLabel($q->status), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?= $q->valid_until !== null
                                ? htmlspecialchars(DateHelper::toBrDate($q->valid_until), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars(DateHelper::toBrDateTime($q->created_at), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <?php
                            $mode = 'table-row';
                            $canEdit = false;
                            $canDelete = false;
                            $detailsHref = $url('quotes/show?id=' . $q->id);
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($quotes === []): ?>
                    <tr class="empty-row">
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                Nenhum orçamento encontrado para os filtros atuais.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'quotes';
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>