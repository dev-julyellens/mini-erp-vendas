<?php

declare(strict_types=1);

/**
 * Painel de filtros padronizado (card + form + ações Filtrar/Limpar).
 *
 * Defina `$filterContent` com o HTML das colunas (ex.: ob_start() na view).
 *
 * @var string $filterAction URL do formulário
 * @var string $filterContent HTML dos campos (colunas Bootstrap)
 * @var string|null $filterClearHref URL do botão Limpar (omitir para ocultar)
 * @var string $filterMethod Método HTTP (padrão: get)
 * @var string $filterActionsColClass Classe da coluna das ações (padrão: col-12 col-md-auto)
 */

$filterMethod = $filterMethod ?? 'get';
$filterClearHref = $filterClearHref ?? null;
$filterActionsColClass = $filterActionsColClass ?? 'col-12 col-md-auto';

?>
<div class="card-soft filter-panel p-3 p-md-4 mb-3">
    <form class="row g-2 align-items-end filter-form"
        method="<?= htmlspecialchars($filterMethod, ENT_QUOTES, 'UTF-8') ?>"
        action="<?= htmlspecialchars($filterAction, ENT_QUOTES, 'UTF-8') ?>">
        <?= $filterContent ?>
        <div class="<?= htmlspecialchars($filterActionsColClass, ENT_QUOTES, 'UTF-8') ?>">
            <?php
            $mode = 'filter';
            $clearHref = $filterClearHref;
            require __DIR__ . '/action-buttons.php';
            ?>
        </div>
    </form>
</div>