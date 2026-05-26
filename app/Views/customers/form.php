<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var ?\App\Models\Customer $customer */
/** @var array<string, string> $errors */
/** @var array<string, mixed>|null $old */

$errors = $errors ?? [];
$old = $old ?? null;
$isEdit = $customer !== null;

$name = $old['name'] ?? ($customer ? $customer->name : '');
$email = $old['email'] ?? ($customer ? $customer->email : '');
$phone = $old['phone'] ?? ($customer ? (string) ($customer->phone ?? '') : '');

?>
<div class="mb-3">
    <h1 class="h3 mb-1"><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?></h1>
    <div class="text-muted">Campos obrigatórios: nome e e-mail</div>
</div>

<div class="card-soft p-3 p-md-4" style="max-width: 820px;">
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrija os campos abaixo</div>
            <ul class="mb-0">
                <?php foreach ($errors as $msg): ?>
                    <li><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($url($isEdit ? 'customers/update' : 'customers/store'), ENT_QUOTES, 'UTF-8') ?>">
        <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $customer->id ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Telefone</label>
            <input class="form-control" name="phone" data-mask-phone autocomplete="tel"
                value="<?= htmlspecialchars((string) $phone, ENT_QUOTES, 'UTF-8') ?>" placeholder="(00) 00000-0000">
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit" data-loading-text="Salvando...">Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url('customers'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
        </div>
    </form>
</div>