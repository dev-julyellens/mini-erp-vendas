<?php

declare(strict_types=1);

use App\Models\AccountsReceivable;

/** @var callable(string):string $url */
/** @var AccountsReceivable $account */
/** @var list<string> $methods */
/** @var array<string, string> $methodLabels */
/** @var array<string, string> $errors */
/** @var array{amount: string, payment_method: string, paid_at: string, notes: string} $old */

$fmt = static fn(string $v): string => number_format((float) $v, 2, ',', '.');
$remaining = $account->remaining_amount ?? $account->amount;

/** @var \App\Models\PixCharge|null $pendingPix */
$pendingPix = $pendingPix ?? null;

$title = 'Registrar recebimento';
$subtitle = 'Conta #' . (int) $account->id . ' · restante R$ ' . $fmt($remaining);
$breadcrumbs = [
    ['label' => 'Financeiro', 'href' => $url('finance')],
    ['label' => 'Contas a receber', 'href' => $url('finance/accounts-receivable')],
    ['label' => 'Conta #' . (int) $account->id, 'href' => $url('finance/accounts-receivable/show?id=' . $account->id)],
    ['label' => 'Recebimento'],
];
require dirname(__DIR__, 2) . '/components/page-header.php';

?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <?php if ($pendingPix !== null): ?>
            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <span>Já existe uma cobrança PIX pendente para esta conta.</span>
                <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($url('finance/pix/charge?id=' . $pendingPix->id), ENT_QUOTES, 'UTF-8') ?>">Abrir QR Code</a>
            </div>
        <?php else: ?>
            <div class="card-soft p-3 p-md-4 mb-3">
                <h2 class="h6 mb-2"><i class="bi bi-qr-code"></i> Receber via PIX (QR Code)</h2>
                <p class="text-muted small mb-3">Gera cobrança automática com conciliação no financeiro após o pagamento.</p>
                <form method="post" action="<?= htmlspecialchars($url('finance/pix/create-account'), ENT_QUOTES, 'UTF-8') ?>">
                    <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                    <input type="hidden" name="accounts_receivable_id" value="<?= (int) $account->id ?>">
                    <div class="mb-3">
                        <label class="form-label" for="pix_amount">Valor da cobrança (R$)</label>
                        <input type="text" class="form-control" id="pix_amount" name="amount" data-mask-money inputmode="decimal"
                            value="<?= htmlspecialchars($old['amount'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Gerar QR Code PIX</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card-soft p-3 p-md-4">
            <h2 class="h6 mb-3">Registro manual (já recebido)</h2>
            <form method="post" action="<?= htmlspecialchars($url('finance/accounts-receivable/receive'), ENT_QUOTES, 'UTF-8') ?>">
                <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                <input type="hidden" name="accounts_receivable_id" value="<?= (int) $account->id ?>">

                <div class="mb-3">
                    <label class="form-label" for="amount">Valor recebido (R$)</label>
                    <input type="text" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
                        id="amount" name="amount" data-mask-money inputmode="decimal"
                        value="<?= htmlspecialchars($old['amount'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['amount'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['amount'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="form-text">Máximo: R$ <?= htmlspecialchars($fmt($remaining), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="payment_method">Forma de pagamento</label>
                    <select class="form-select <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>"
                        id="payment_method" name="payment_method" required>
                        <?php foreach ($methods as $m): ?>
                            <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $old['payment_method'] === $m ? 'selected' : '' ?>>
                                <?= htmlspecialchars($methodLabels[$m] ?? $m, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['payment_method'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['payment_method'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="paid_at">Data do pagamento</label>
                    <input type="datetime-local" class="form-control <?= isset($errors['paid_at']) ? 'is-invalid' : '' ?>"
                        id="paid_at" name="paid_at"
                        value="<?= htmlspecialchars($old['paid_at'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (isset($errors['paid_at'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['paid_at'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="notes">Observações</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= htmlspecialchars($old['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <?php if (isset($errors['status'])): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (isset($errors['auth'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['auth'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php
                $mode = 'form-footer';
                $cancelHref = $url('finance/accounts-receivable/show?id=' . $account->id);
                $saveLabel = 'Confirmar recebimento';
                $saveLoadingText = 'Registrando...';
                require dirname(__DIR__, 2) . '/components/action-buttons.php';
                ?>
            </form>
        </div>
    </div>
</div>