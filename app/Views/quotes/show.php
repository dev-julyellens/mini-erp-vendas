<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Helpers\Permission;
use App\Models\Quote;

/** @var callable(string):string $url */
/** @var \App\Models\Quote $quote */
/** @var list<\App\Models\QuoteItem> $items */

$title = 'Orçamento #' . (int) $quote->id;
$subtitle = 'Detalhes da proposta comercial';
$breadcrumbs = [
    ['label' => 'Orçamentos', 'href' => $url('quotes')],
    ['label' => 'Orçamento #' . (int) $quote->id],
];
$actionsHtml = '';
if ($quote->status === 'draft' && Permission::can('vendas', 'editar'))
{
    $actionsHtml .= '<form method="post" action="' . htmlspecialchars($url('quotes/mark-sent'), ENT_QUOTES, 'UTF-8') . '" class="d-inline">'
        . '<input type="hidden" name="id" value="' . (int) $quote->id . '">'
        . '<button type="submit" class="btn btn-outline"><i class="bi bi-send"></i> Marcar como enviado</button>'
        . '</form>';
}
if ($quote->canConvert() && Permission::can('vendas', 'criar'))
{
    $actionsHtml .= '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#convert-quote-modal">'
        . '<i class="bi bi-arrow-right-circle"></i> Converter em venda</button>';
}
if ($quote->canCancel() && Permission::can('vendas', 'excluir'))
{
    $actionsHtml .= '<button type="button" class="btn btn-destructive" data-bs-toggle="modal" data-bs-target="#cancel-quote-modal">'
        . '<i class="bi bi-x-circle"></i> Cancelar</button>';
}
if ($quote->converted_order_id !== null && Permission::can('vendas', 'visualizar'))
{
    $actionsHtml .= '<a class="btn btn-secondary" href="' . htmlspecialchars($url('orders/show?id=' . $quote->converted_order_id), ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="bi bi-receipt"></i> Ver venda #' . (int) $quote->converted_order_id . '</a>';
}
$actionsHtml .= '<a class="btn btn-ghost" href="' . htmlspecialchars($url('quotes'), ENT_QUOTES, 'UTF-8') . '">'
    . '<i class="bi bi-arrow-left"></i> Voltar</a>';
require dirname(__DIR__) . '/components/page-header.php';

?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4 h-100">
            <div class="text-muted small">Cliente</div>
            <div class="fs-5 fw-semibold"><?= htmlspecialchars((string) ($quote->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <hr>
            <div class="text-muted small">Status</div>
            <div class="mb-2">
                <span class="badge text-bg-<?= htmlspecialchars(Quote::statusBadge($quote->status), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(Quote::statusLabel($quote->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <hr>
            <div class="text-muted small">Total</div>
            <div class="fs-4 fw-bold">R$ <?= htmlspecialchars(number_format((float) $quote->total_amount, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small mt-2">
                Validade:
                <?= $quote->valid_until !== null
                    ? htmlspecialchars(DateHelper::toBrDate($quote->valid_until), ENT_QUOTES, 'UTF-8')
                    : '—' ?>
            </div>
            <div class="text-muted small mt-1">
                Criado em <?= htmlspecialchars(DateHelper::toBrDateTime($quote->created_at), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php if ($quote->notes !== null && $quote->notes !== ''): ?>
                <hr>
                <div class="text-muted small">Observações</div>
                <div><?= nl2br(htmlspecialchars($quote->notes, ENT_QUOTES, 'UTF-8')) ?></div>
            <?php endif; ?>
            <?php if ($quote->canConvert()): ?>
                <div class="alert alert-info small mt-3 mb-0">
                    A conversão em venda baixa estoque e gera parcelas conforme o parcelamento escolhido.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft p-3 p-md-4">
            <div class="fw-semibold mb-2">Itens</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
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

<?php if ($quote->canConvert() && Permission::can('vendas', 'criar')): ?>
    <div class="modal fade" id="convert-quote-modal" tabindex="-1" aria-labelledby="convert-quote-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= htmlspecialchars($url('quotes/convert'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $quote->id ?>">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="convert-quote-title">Converter em venda</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="installmentCount">Parcelas</label>
                    <select class="form-select" name="installment_count" id="installmentCount">
                        <option value="1">À vista (1x)</option>
                        <?php for ($n = 2; $n <= 12; $n++): ?>
                            <option value="<?= $n ?>"><?= $n ?>x</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-primary">Confirmar conversão</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($quote->canCancel() && Permission::can('vendas', 'excluir')): ?>
    <div class="modal fade" id="cancel-quote-modal" tabindex="-1" aria-labelledby="cancel-quote-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="<?= htmlspecialchars($url('quotes/cancel'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int) $quote->id ?>">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="cancel-quote-title">Cancelar orçamento</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Confirma o cancelamento do orçamento #<?= (int) $quote->id ?>? Esta ação não pode ser desfeita.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-destructive">Cancelar orçamento</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>