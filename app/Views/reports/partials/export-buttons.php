<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var string $exportPath */
/** @var array<string, string> $paginationQuery */

$baseQuery = http_build_query($paginationQuery);
$pdfUrl = $url($exportPath . '?format=pdf' . ($baseQuery !== '' ? '&' . $baseQuery : ''));
$xlsxUrl = $url($exportPath . '?format=xlsx' . ($baseQuery !== '' ? '&' . $baseQuery : ''));

?>
<div class="d-flex flex-wrap gap-2 export-bar">
    <a class="btn btn-outline-danger btn-sm" href="<?= htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>
    <a class="btn btn-outline-success btn-sm" href="<?= htmlspecialchars($xlsxUrl, ENT_QUOTES, 'UTF-8') ?>">
        <i class="bi bi-file-earmark-excel"></i> Excel
    </a>
</div>