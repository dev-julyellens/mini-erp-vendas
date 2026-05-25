<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var string $title */
/** @var string $exportPath */
/** @var array<string, string> $paginationQuery */

?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url('reports'), ENT_QUOTES, 'UTF-8') ?>">Relatórios</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <div class="w-100 w-md-auto">
        <?php require __DIR__ . '/partials/export-buttons.php'; ?>
    </div>
</div>