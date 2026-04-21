<?php

declare(strict_types=1);

use App\Helpers\DateHelper;

/** @var \App\Models\Order $order */
/** @var list<\App\Models\OrderItem> $items */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Venda #<?= (int) $order->id ?></h1>
        <div class="text-muted">Itens e valores registrados no momento da venda</div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="text-muted small">Cliente</div>
            <div class="fs-5 fw-semibold"><?= htmlspecialchars((string) ($order->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <hr>
            <div class="text-muted small">Total</div>
            <div class="fs-4 fw-bold">R$ <?= htmlspecialchars(number_format((float) $order->total_amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small mt-2">Criado em <?= htmlspecialchars(DateHelper::toBrDateTime($order->created_at), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft p-3 p-md-4">
            <div class="fw-semibold mb-2">Itens</div>
            <div class="alert alert-info small mb-3">
                O preço unitário exibido abaixo foi armazenado no fechamento da venda para preservar o histórico,
                mesmo que o cadastro do produto seja alterado depois.
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Preço unit.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($it->product_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $it->quantity ?></td>
                            <td>R$ <?= htmlspecialchars(number_format((float) $it->unit_price, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">R$ <?= htmlspecialchars(number_format((float) $it->subtotal, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
