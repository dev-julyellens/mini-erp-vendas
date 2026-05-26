<?php

declare(strict_types=1);

/**
 * Cabeçalho padronizado de relatório (page-header + exportação).
 *
 * @var callable(string):string $url
 * @var string $title
 * @var string $exportPath
 * @var array<string, string> $paginationQuery
 * @var string|null $subtitle
 */

$subtitle = $subtitle ?? null;
$breadcrumbs = [
    ['label' => 'Relatórios', 'href' => $url('reports')],
    ['label' => $title],
];
ob_start();
require __DIR__ . '/partials/export-buttons.php';
$actionsHtml = ob_get_clean();
require dirname(__DIR__) . '/components/page-header.php';
