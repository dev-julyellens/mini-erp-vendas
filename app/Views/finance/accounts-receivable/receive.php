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

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Registrar recebimento</h1>
        <div class="text-muted">Conta #<?= (int) $account->id ?> · restante R$ <?= htmlspecialchars($fmt($remaining), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable/show?id=' . $account->id), ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-soft p-3 p-md-4">
            <form method="post" action="<?= htmlspecialchars($url('finance/accounts-receivable/receive'), ENT_QUOTES, 'UTF-8') ?>">
                <?php require dirname(__DIR__, 2) . '/partials/csrf.php'; ?>
                <input type="hidden" name="accounts_receivable_id" value="<?= (int) $account->id ?>">

                <div class="mb-3">
                    <label class="form-label" for="amount">Valor recebido (R$)</label>
                    <input type="text" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
                        id="amount" name="amount" inputmode="decimal"
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

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Confirmar recebimento</button>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('finance/accounts-receivable/show?id=' . $account->id), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>