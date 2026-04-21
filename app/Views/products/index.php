<?php

declare(strict_types=1);

/** @var list<\App\Models\Product> $products */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $lowStockThreshold */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Produtos</h1>
        <div class="text-muted">Catálogo, preços e estoque</div>
    </div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url('products/create'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-plus-lg"></i> Novo produto
    </a>
</div>

<div class="card-soft p-3 p-md-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <?php $low = $p->stock < $lowStockThreshold; ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($p->description): ?>
                            <div class="text-muted small"><?= htmlspecialchars((string) $p->description, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>R$ <?= htmlspecialchars(number_format((float) $p->price, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($low): ?>
                            <span class="badge text-bg-warning"><?= (int) $p->stock ?> (baixo)</span>
                        <?php else: ?>
                            <span class="badge text-bg-light border"><?= (int) $p->stock ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($url('products/edit?id=' . $p->id), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                        <form class="d-inline" method="post" action="<?= htmlspecialchars($url('products/delete'), ENT_QUOTES, 'UTF-8') ?>"
                              onsubmit="return confirm('Remover este produto?');">
                            <input type="hidden" name="id" value="<?= (int) $p->id ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($products === []): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Nenhum produto cadastrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $path = 'products';
    $query = [];
    require dirname(__DIR__) . '/partials/pagination.php';
    ?>
</div>
