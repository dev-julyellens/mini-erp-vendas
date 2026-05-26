<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\PixCharge;

/** @var callable(string):string $url */
/** @var PixCharge $charge */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');

$title = 'Comprovante PIX';
$subtitle = 'Cobrança #' . (int) $charge->id;
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => 'Comprovante PIX'],
];
$actionsHtml = '';
if ($charge->payment_id !== null && $charge->accounts_receivable_id !== null)
{
    $actionsHtml .= '<a class="btn btn-ghost" href="' . htmlspecialchars($url('finance/accounts-receivable/show?id=' . $charge->accounts_receivable_id), ENT_QUOTES, 'UTF-8') . '">'
        . 'Ver conta a receber</a>';
}
$actionsHtml .= '<button type="button" class="btn btn-secondary" onclick="window.print()">'
    . '<i class="bi bi-printer"></i> Imprimir</button>';
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-soft p-4" id="pix-receipt">
            <div class="text-center mb-4">
                <div class="text-success fs-1"><i class="bi bi-check-circle-fill"></i></div>
                <h2 class="h5 mb-0">Pagamento recebido</h2>
            </div>

            <dl class="row mb-0">
                <dt class="col-sm-5 text-muted">Valor</dt>
                <dd class="col-sm-7 fw-semibold">R$ <?= htmlspecialchars($fmt($charge->amount), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-5 text-muted">Cliente</dt>
                <dd class="col-sm-7"><?= htmlspecialchars((string) ($charge->customer_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>

                <?php if ($charge->order_id !== null): ?>
                    <dt class="col-sm-5 text-muted">Venda</dt>
                    <dd class="col-sm-7">#<?= (int) $charge->order_id ?></dd>
                <?php endif; ?>

                <?php if ($charge->installment_number !== null): ?>
                    <dt class="col-sm-5 text-muted">Parcela</dt>
                    <dd class="col-sm-7"><?= (int) $charge->installment_number ?></dd>
                <?php endif; ?>

                <dt class="col-sm-5 text-muted">ID transação</dt>
                <dd class="col-sm-7 font-monospace small"><?= htmlspecialchars($charge->external_id, ENT_QUOTES, 'UTF-8') ?></dd>

                <?php if ($charge->receipt_reference !== null && $charge->receipt_reference !== ''): ?>
                    <dt class="col-sm-5 text-muted">Comprovante</dt>
                    <dd class="col-sm-7 font-monospace small"><?= htmlspecialchars($charge->receipt_reference, ENT_QUOTES, 'UTF-8') ?></dd>
                <?php endif; ?>

                <dt class="col-sm-5 text-muted">Pago em</dt>
                <dd class="col-sm-7"><?= htmlspecialchars(DateHelper::toBrDateTime((string) $charge->paid_at), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt class="col-sm-5 text-muted">Gateway</dt>
                <dd class="col-sm-7"><?= htmlspecialchars($charge->gateway, ENT_QUOTES, 'UTF-8') ?></dd>

                <?php if ($charge->payment_id !== null): ?>
                    <dt class="col-sm-5 text-muted">Recebimento ERP</dt>
                    <dd class="col-sm-7">#<?= (int) $charge->payment_id ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div>