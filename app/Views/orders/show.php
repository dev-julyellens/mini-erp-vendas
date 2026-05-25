<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\Installment;
use App\Models\Order;

/** @var callable(string):string $url */
/** @var \App\Models\Order $order */
/** @var list<\App\Models\OrderItem> $items */
/** @var list<Installment> $installments */

$statusLabel = Order::statusLabel($order->status);
$statusBadge = Order::statusBadge($order->status);

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Venda #<?= (int) $order->id ?></h1>
        <div class="text-muted">Itens e valores registrados no momento da venda</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($order->canCancel() && \App\Helpers\Permission::can('vendas', 'excluir')): ?>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancel-order-modal">
                <i class="bi bi-x-circle"></i> Cancelar venda
            </button>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="text-muted small">Cliente</div>
            <div class="fs-5 fw-semibold"><?= htmlspecialchars((string) ($order->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <hr>
            <div class="text-muted small">Status</div>
            <div class="mb-2">
                <span class="badge text-bg-<?= htmlspecialchars($statusBadge, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php if ($order->status === 'canceled' && $order->canceled_at !== null): ?>
                <div class="text-muted small">
                    Cancelada em <?= htmlspecialchars(DateHelper::toBrDateTime($order->canceled_at), ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($order->canceled_by_name !== null): ?>
                        por <?= htmlspecialchars($order->canceled_by_name, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <hr>
            <div class="text-muted small">Total</div>
            <div class="fs-4 fw-bold">R$ <?= htmlspecialchars(number_format((float) $order->total_amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small mt-2">Criado em <?= htmlspecialchars(DateHelper::toBrDateTime($order->created_at), ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($order->isLocked()): ?>
                <div class="alert alert-warning small mt-3 mb-0">
                    Esta venda está bloqueada para alterações.
                </div>
            <?php endif; ?>
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

<?php if (($installments ?? []) !== []): ?>
    <div class="card-soft p-3 p-md-4 mt-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="fw-semibold">Parcelamento</div>
            <?php if (Permission::can('financeiro', 'visualizar')): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url('finance/installments/open'), ENT_QUOTES, 'UTF-8') ?>">
                    Ver parcelas abertas
                </a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                        <tr>
                            <td><?= (int) $inst->installment_number ?></td>
                            <td>R$ <?= htmlspecialchars(number_format((float) $inst->amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(DateHelper::toBrDate($inst->due_date), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge text-bg-<?= htmlspecialchars(Installment::statusBadge($inst->status), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(Installment::statusLabel($inst->status), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($order->canCancel() && \App\Helpers\Permission::can('vendas', 'excluir')): ?>
    <div class="modal fade" id="cancel-order-modal" tabindex="-1" aria-labelledby="cancel-order-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= htmlspecialchars($url('orders/cancel'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                    <input type="hidden" name="id" value="<?= (int) $order->id ?>">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="cancel-order-modal-label">Confirmar cancelamento</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            Deseja cancelar a venda <strong>#<?= (int) $order->id ?></strong>?
                        </p>
                        <ul class="small text-muted mb-0">
                            <li>O estoque dos itens será devolvido automaticamente.</li>
                            <li>A venda ficará bloqueada para edição.</li>
                            <li>Esta ação será registrada na auditoria.</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn btn-danger">Confirmar cancelamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>