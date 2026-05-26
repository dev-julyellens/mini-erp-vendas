<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var list<\App\Models\Plan> $plans */
/** @var array<string, string> $errors */

$errors = $errors ?? [];

?>
<h1 class="h4 mb-2">Escolha seu plano</h1>
<p class="text-muted small mb-4">Passo 2 de 2 — define limites e cobrança recorrente da plataforma.</p>

<?php if (isset($errors['plan_code'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['plan_code'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (isset($errors['plan_limit'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['plan_limit'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($url('onboarding/plan'), ENT_QUOTES, 'UTF-8') ?>">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>

    <div class="row g-3">
        <?php foreach ($plans as $plan): ?>
            <div class="col-12">
                <label class="card h-100 border cursor-pointer">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="plan_code"
                            value="<?= htmlspecialchars($plan->code, ENT_QUOTES, 'UTF-8') ?>" required>
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($plan->name, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($plan->description !== null): ?>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($plan->description, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="small">
                                <strong>R$ <?= number_format((float) $plan->price_monthly, 2, ',', '.') ?></strong>/mês
                                <?php if ($plan->trial_days > 0): ?>
                                    <span class="text-success">· <?= (int) $plan->trial_days ?> dias de teste</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-4">Ativar plano e concluir</button>
</form>