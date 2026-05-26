<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Models\Installment;

/** @var callable(string):string $url */
/** @var Installment $installment */
/** @var list<string> $methods */
/** @var array<string, string> $methodLabels */
/** @var array<string, string> $errors */
/** @var array{payment_method: string, paid_at: string, notes: string} $old */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');

/** @var \App\Models\PixCharge|null $pendingPix */
$pendingPix = $pendingPix ?? null;

$title = 'Baixa de parcela';
$subtitle = 'Venda #' . (int) $installment->order_id . ' — parcela ' . (int) $installment->installment_number;
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => 'Parcelas abertas', 'href' => $url('finance/installments/open')],
    ['label' => 'Baixa de parcela'],
];
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3 p-md-4">
            <div class="text-muted small">Cliente</div>
            <div class="fw-semibold"><?= htmlspecialchars((string) ($installment->customer_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <hr>
            <div class="text-muted small">Valor da parcela</div>
            <div class="fs-4 fw-bold">R$ <?= htmlspecialchars($fmt($installment->amount), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small mt-2">Vencimento: <?= htmlspecialchars(DateHelper::toBrDate($installment->due_date), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="mt-2">
                <span class="badge text-bg-<?= htmlspecialchars(Installment::statusBadge($installment->status), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(Installment::statusLabel($installment->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?php if ($pendingPix !== null): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <span>Cobrança PIX pendente para esta parcela.</span>
                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($url('finance/pix/charge?id=' . $pendingPix->id), ENT_QUOTES, 'UTF-8') ?>">Abrir QR Code</a>
            </div>
        <?php else: ?>
            <div class="card-soft p-3 p-md-4 mb-3">
                <h2 class="h6 mb-2"><i class="bi bi-qr-code"></i> Pagar parcela via PIX</h2>
                <form method="post" action="<?= htmlspecialchars($url('finance/pix/create-installment'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                    <input type="hidden" name="installment_id" value="<?= (int) $installment->id ?>">
                    <button type="submit" class="btn btn-success">Gerar QR Code PIX (R$ <?= htmlspecialchars($fmt($installment->amount), ENT_QUOTES, 'UTF-8') ?>)</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card-soft p-3 p-md-4">
            <h2 class="h6 mb-3">Baixa manual</h2>
            <form method="post" action="<?= htmlspecialchars($url('finance/installments/pay'), ENT_QUOTES, 'UTF-8') ?>">
                <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                <input type="hidden" name="installment_id" value="<?= (int) $installment->id ?>">

                <div class="mb-3">
                    <label class="form-label" for="payment_method">Forma de pagamento</label>
                    <select class="form-select <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" id="payment_method" name="payment_method" required>
                        <?php foreach ($methods as $method): ?>
                            <option value="<?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?>" <?= $old['payment_method'] === $method ? 'selected' : '' ?>>
                                <?= htmlspecialchars($methodLabels[$method] ?? $method, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['payment_method'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['payment_method'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="paid_at">Data do pagamento</label>
                    <input class="form-control <?= isset($errors['paid_at']) ? 'is-invalid' : '' ?>" type="datetime-local" id="paid_at" name="paid_at" value="<?= htmlspecialchars($old['paid_at'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (isset($errors['paid_at'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['paid_at'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= htmlspecialchars($old['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $message): ?>
                                <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php
                $mode = 'form-footer';
                $cancelHref = $url('finance/installments/open');
                $saveLabel = 'Confirmar baixa';
                $saveLoadingText = 'Registrando...';
                require dirname(__DIR__, 2) . '/components/action-buttons.php';
                ?>
            </form>
        </div>
    </div>
</div>