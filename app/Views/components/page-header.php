<?php

declare(strict_types=1);

/**
 * Cabeçalho padronizado de página.
 *
 * @var string $title
 * @var string|null $subtitle
 * @var list<array{label: string, href?: string}>|null $breadcrumbs
 * @var string|null $actionsHtml HTML de botões/ações (opcional)
 */

$subtitle = $subtitle ?? null;
$breadcrumbs = $breadcrumbs ?? null;
$actionsHtml = $actionsHtml ?? null;

?>
<header class="page-header d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div class="page-header-main">
        <?php if ($breadcrumbs !== null && $breadcrumbs !== []): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <?php foreach ($breadcrumbs as $i => $crumb): ?>
                        <?php if ($i === count($breadcrumbs) - 1): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?= htmlspecialchars((string) $crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <?php if (!empty($crumb['href'])): ?>
                                    <a href="<?= htmlspecialchars((string) $crumb['href'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) $crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars((string) $crumb['label'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <h1 class="h3 page-title mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($subtitle !== null && $subtitle !== ''): ?>
            <p class="text-muted page-subtitle mb-0 mt-1"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actionsHtml !== null && $actionsHtml !== ''): ?>
        <div class="page-header-actions d-flex flex-wrap gap-2">
            <?= $actionsHtml ?>
        </div>
    <?php endif; ?>
</header>
