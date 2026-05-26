<?php

declare(strict_types=1);

/**
 * Card KPI reutilizável.
 *
 * @var string $label
 * @var string $value
 * @var string|null $hint
 * @var string $variant stat-orders|stat-products|...
 * @var string|null $href
 */

$hint = $hint ?? null;
$href = $href ?? null;
$variant = $variant ?? 'stat-orders';

$tagOpen = $href !== null && $href !== ''
    ? '<a class="kpi-card stat-tile ' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') . ' text-decoration-none" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
    : '<div class="kpi-card stat-tile ' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') . '">';
$tagClose = $href !== null && $href !== '' ? '</a>' : '</div>';

echo $tagOpen;
?>
<div class="kpi-label small"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
<h3 class="kpi-value mb-0"><?= $value ?></h3>
<?php if ($hint !== null && $hint !== ''): ?>
    <div class="kpi-hint small"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?= $tagClose ?>