<?php

declare(strict_types=1);

/**
 * Cabeçalho de formulário em telas auth/onboarding (card central).
 *
 * @var string $title
 * @var string|null $subtitle
 */

$subtitle = $subtitle ?? null;

?>
<div class="auth-form-header mb-4">
    <h2 class="h5 page-title mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($subtitle !== null && $subtitle !== ''): ?>
        <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>