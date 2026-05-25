<?php

declare(strict_types=1);

/** @var array{order_status: string} $filters */
/** @var list<string> $orderStatuses */

$current = $filters['order_status'] ?? '';

?>
<div class="col-6 col-md-3">
    <label class="form-label" for="order_status">Status do pedido</label>
    <select class="form-select" id="order_status" name="order_status">
        <option value="" <?= $current === '' ? 'selected' : '' ?>>Pago (padrão)</option>
        <?php foreach ($orderStatuses as $status): ?>
            <?php if ($status === 'paid')
            {
                continue;
            } ?>
            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $current === $status ? 'selected' : '' ?>>
                <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>