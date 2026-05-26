<?php

declare(strict_types=1);

use App\Helpers\ActionButton;

/**
 * Botões de ação reutilizáveis.
 *
 * Modos ($mode):
 * - table-row: grupo Editar / Excluir / extras em linha de tabela
 * - form-footer: Salvar + Cancelar
 * - filter: Filtrar + Limpar (opcional)
 *
 * @var string $mode
 * @var callable(string):string|null $url
 * @var string|null $editHref
 * @var string|null $cancelHref
 * @var string|null $clearHref
 * @var string|null $deleteAction
 * @var string|null $deleteConfirm
 * @var string|null $deleteTitle
 * @var int|null $deleteId
 * @var string|null $detailsHref
 * @var string|null $detailsLabel
 * @var list<array{href?: string, action?: string, label: string, variant?: string, size?: string, confirm?: string, method?: string, extras?: array<string, string>}>|null $extraActions
 * @var bool $canEdit
 * @var bool $canDelete
 * @var string $saveLabel
 * @var string|null $saveLoadingText
 * @var string $cancelLabel
 * @var string $filterLabel
 * @var string $clearLabel
 * @var string $csrfPartial
 */

$mode = $mode ?? 'table-row';
$url = $url ?? static fn (string $p): string => $p;
$canEdit = $canEdit ?? true;
$canDelete = $canDelete ?? true;
$saveLabel = $saveLabel ?? 'Salvar';
$saveLoadingText = $saveLoadingText ?? null;
$cancelLabel = $cancelLabel ?? 'Cancelar';
$filterLabel = $filterLabel ?? '<i class="bi bi-funnel"></i> Filtrar';
$clearLabel = $clearLabel ?? 'Limpar';
$detailsLabel = $detailsLabel ?? 'Detalhes';
$extraActions = $extraActions ?? [];
$csrfPartial = $csrfPartial ?? dirname(__DIR__) . '/partials/csrf.php';

if ($mode === 'form-footer'): ?>
    <div class="form-actions">
        <button type="submit" class="<?= htmlspecialchars(ActionButton::classes('primary', 'md'), ENT_QUOTES, 'UTF-8') ?>"
            <?php if ($saveLoadingText): ?>data-loading-text="<?= htmlspecialchars($saveLoadingText, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
            <?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?>
        </button>
        <?php if (!empty($cancelHref)): ?>
            <a class="<?= htmlspecialchars(ActionButton::classes('secondary', 'md'), ENT_QUOTES, 'UTF-8') ?>"
                href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>
    </div>
<?php elseif ($mode === 'filter'): ?>
    <div class="filter-actions">
        <button type="submit" class="<?= htmlspecialchars(ActionButton::classes('primary', 'md'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $filterLabel ?>
        </button>
        <?php if (!empty($clearHref)): ?>
            <a class="<?= htmlspecialchars(ActionButton::classes('secondary', 'md'), ENT_QUOTES, 'UTF-8') ?>"
                href="<?= htmlspecialchars($clearHref, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($clearLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-actions">
        <?php if ($canEdit && !empty($editHref)): ?>
            <a class="<?= htmlspecialchars(ActionButton::classes('outline', 'sm'), ENT_QUOTES, 'UTF-8') ?>"
                href="<?= htmlspecialchars($editHref, ENT_QUOTES, 'UTF-8') ?>">Editar</a>
        <?php endif; ?>
        <?php if (!empty($detailsHref)): ?>
            <a class="<?= htmlspecialchars(ActionButton::classes('ghost', 'sm'), ENT_QUOTES, 'UTF-8') ?>"
                href="<?= htmlspecialchars($detailsHref, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($detailsLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endif; ?>
        <?php foreach ($extraActions as $action): ?>
            <?php
            $variant = $action['variant'] ?? 'outline';
            $size = $action['size'] ?? 'sm';
            $label = $action['label'] ?? '';
            ?>
            <?php if (!empty($action['href'])): ?>
                <a class="<?= htmlspecialchars(ActionButton::classes($variant, $size), ENT_QUOTES, 'UTF-8') ?>"
                    href="<?= htmlspecialchars((string) $action['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php elseif (!empty($action['action'])): ?>
                <form class="d-inline" method="<?= htmlspecialchars($action['method'] ?? 'post', ENT_QUOTES, 'UTF-8') ?>"
                    action="<?= htmlspecialchars((string) $action['action'], ENT_QUOTES, 'UTF-8') ?>"
                    <?php if (!empty($action['confirm'])): ?>
                        data-confirm="<?= htmlspecialchars((string) $action['confirm'], ENT_QUOTES, 'UTF-8') ?>"
                    <?php endif; ?>>
                    <?php require $csrfPartial; ?>
                    <?php foreach (($action['extras'] ?? []) as $name => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
                            value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="<?= htmlspecialchars(ActionButton::classes($variant, $size), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($canDelete && !empty($deleteAction) && $deleteId !== null): ?>
            <form class="d-inline" method="post" action="<?= htmlspecialchars($deleteAction, ENT_QUOTES, 'UTF-8') ?>"
                <?php if ($deleteConfirm): ?>
                    data-confirm="<?= htmlspecialchars($deleteConfirm, ENT_QUOTES, 'UTF-8') ?>"
                <?php endif; ?>
                <?php if ($deleteTitle): ?>
                    data-confirm-title="<?= htmlspecialchars($deleteTitle, ENT_QUOTES, 'UTF-8') ?>"
                <?php endif; ?>>
                <?php require $csrfPartial; ?>
                <input type="hidden" name="id" value="<?= (int) $deleteId ?>">
                <button type="submit" class="<?= htmlspecialchars(ActionButton::classes('destructive', 'sm'), ENT_QUOTES, 'UTF-8') ?>">
                    Excluir
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
