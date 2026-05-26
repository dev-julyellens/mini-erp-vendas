<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Company> $companies */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $search */
/** @var string $status */

$title = 'Empresas';
$subtitle = 'Gestão administrativa de empresas (multiempresa)';
$actionsHtml = '<a class="btn btn-primary" href="' . htmlspecialchars($url('admin/companies/create'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-plus-lg"></i> Nova empresa</a>';
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-5">
    <label class="form-label" for="filter_q">Buscar</label>
    <input type="search" class="form-control" id="filter_q" name="q" placeholder="Nome, slug ou documento"
        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
</div>
<div class="col-12 col-md-4">
    <label class="form-label" for="filter_status">Status</label>
    <select class="form-select" id="filter_status" name="status">
        <option value="">Todos os status</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativas</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativas</option>
    </select>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('admin/companies');
$filterClearHref = $url('admin/companies');
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 js-datatable" data-dt-actions-col="5">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Documento</th>
                    <th>Status</th>
                    <th>Criada em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($c->slug, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= htmlspecialchars($c->tax_id ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($c->active): ?>
                                <span class="badge text-bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars(substr($c->created_at, 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end text-nowrap">
                            <?php
                            $mode = 'table-row';
                            $editHref = $url('admin/companies/edit?id=' . $c->id);
                            $canEdit = true;
                            $canDelete = false;
                            $extraActions = [
                                [
                                    'action' => $url('admin/companies/toggle-active'),
                                    'label' => $c->active ? 'Desativar' : 'Ativar',
                                    'variant' => $c->active ? 'warning' : 'outline',
                                    'confirm' => $c->active ? 'Desativar esta empresa?' : 'Ativar esta empresa?',
                                    'extras' => [
                                        'id' => (string) $c->id,
                                        'active' => $c->active ? '0' : '1',
                                    ],
                                ],
                            ];
                            require dirname(__DIR__) . '/components/action-buttons.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $path = 'admin/companies';
    $query = array_filter(['q' => $search, 'status' => $status]);
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>