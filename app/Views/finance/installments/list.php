<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\Installment;

/** @var callable(string):string $url */
/** @var list<Installment> $installments */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var list<\App\Models\Customer> $customers */
/** @var array{customer_id: ?int, due_from: string, due_to: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var string $listType */
/** @var string $title */
/** @var string $subtitle */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$canPay = Permission::can('financeiro', 'criar');

$routes = [
    'overdue' => 'finance/installments/overdue',
    'open' => 'finance/installments/open',
    'history' => 'finance/installments/history',
];

$currentPath = $routes[$listType] ?? 'finance/installments/open';

$pageTitle = $title;
$pageSubtitle = $subtitle;
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => $title],
];
$actionsHtml = '<a class="btn btn-secondary" href="' . htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-speedometer2"></i> Dashboard</a>';
$title = $pageTitle;
$subtitle = $pageSubtitle;
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item">
        <a class="nav-link <?= $listType === 'overdue' ? 'active' : '' ?>" href="<?= htmlspecialchars($url('finance/installments/overdue'), ENT_QUOTES, 'UTF-8') ?>">
            Vencidas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $listType === 'open' ? 'active' : '' ?>" href="<?= htmlspecialchars($url('finance/installments/open'), ENT_QUOTES, 'UTF-8') ?>">
            Abertas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $listType === 'history' ? 'active' : '' ?>" href="<?= htmlspecialchars($url('finance/installments/history'), ENT_QUOTES, 'UTF-8') ?>">
            Histórico
        </a>
    </li>
</ul>

<?php
ob_start();
?>
<div class="col-12 col-md-4">
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
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_due_from">Vencimento de</label>
    <input class="form-control" type="date" id="filter_due_from" name="due_from"
        value="<?= htmlspecialchars($filters['due_from'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="filter_due_to">Vencimento até</label>
    <input class="form-control" type="date" id="filter_due_to" name="due_to"
        value="<?= htmlspecialchars($filters['due_to'], ENT_QUOTES, 'UTF-8') ?>">
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url($currentPath);
$filterClearHref = $url($currentPath);
require dirname(__DIR__, 2) . '/components/filter-panel.php';
?>

<div class="card-soft p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="-1">
            <thead class="table-light">
                <tr>
                    <th>Venda</th>
                    <th>Parcela</th>
                    <th>Cliente</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <?php if ($listType === 'history'): ?>
                        <th>Pago em</th>
                    <?php endif; ?>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($installments === []): ?>
                    <tr>
                        <td colspan="<?= $listType === 'history' ? 8 : 7 ?>" class="text-center text-muted py-4">
                            Nenhuma parcela encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($installments as $inst): ?>
                        <tr>
                            <td>
                                <a href="<?= htmlspecialchars($url('orders/show?id=' . $inst->order_id), ENT_QUOTES, 'UTF-8') ?>">
                                    #<?= (int) $inst->order_id ?>
                                </a>
                            </td>
                            <td><?= (int) $inst->installment_number ?></td>
                            <td><?= htmlspecialchars((string) ($inst->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= htmlspecialchars($fmt($inst->amount), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(DateHelper::toBrDate($inst->due_date), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= htmlspecialchars(Installment::statusBadge($inst->status), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(Installment::statusLabel($inst->status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <?php if ($listType === 'history'): ?>
                                <td>
                                    <?= $inst->paid_at !== null
                                        ? htmlspecialchars(DateHelper::toBrDateTime($inst->paid_at), ENT_QUOTES, 'UTF-8')
                                        : '—' ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-end">
                                <?php
                                $mode = 'table-row';
                                $canEdit = false;
                                $canDelete = false;
                                $extraActions = [];
                                $detailsHref = null;
                                if ($canPay && $inst->canPay())
                                {
                                    $extraActions[] = [
                                        'href' => $url('finance/installments/pay?id=' . $inst->id),
                                        'label' => 'Baixar',
                                        'variant' => 'primary',
                                    ];
                                }
                                else
                                {
                                    $detailsHref = $url('orders/show?id=' . $inst->order_id);
                                    $detailsLabel = 'Venda';
                                }
                                require dirname(__DIR__, 2) . '/components/action-buttons.php';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$path = $currentPath;
$query = $paginationQuery;
require dirname(__DIR__, 2) . '/partials/pagination.php';
?>