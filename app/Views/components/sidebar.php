<?php

declare(strict_types=1);

use App\Helpers\NavigationMenu;

/** @var callable(string):string $url */
/** @var string $appName */
/** @var int|null $notificationUnreadCount */

$currentPath = \App\Helpers\PathHelper::requestPath();
$menuGroups = NavigationMenu::groups($currentPath, $url, $notificationUnreadCount ?? null);

?>
<aside class="sidebar" id="appSidebar" aria-label="Menu principal">
    <div class="sidebar-header">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">M</div>
            <div class="brand-text">
                <div class="fw-semibold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></div>
                <small class="text-secondary">ERP Comercial</small>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-sidebar-collapse d-none d-lg-inline-flex"
            data-sidebar-collapse aria-label="Recolher menu" title="Recolher menu">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>
    <nav class="sidebar-nav" aria-label="Navegação">
        <?php foreach ($menuGroups as $group): ?>
            <?php
            $hasActive = false;
            foreach ($group['items'] as $item)
            {
                if (!empty($item['active']))
                {
                    $hasActive = true;
                    break;
                }
            }
            $groupOpen = $hasActive ? 'is-open' : '';
            ?>
            <div class="nav-group <?= $groupOpen ?>" data-nav-group="<?= htmlspecialchars($group['id'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="button" class="nav-group-toggle" data-nav-group-toggle
                    aria-expanded="<?= $hasActive ? 'true' : 'false' ?>">
                    <span><?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="bi bi-chevron-down nav-group-chevron" aria-hidden="true"></i>
                </button>
                <div class="nav-group-items">
                    <?php foreach ($group['items'] as $item): ?>
                        <?php
                        $activeClass = !empty($item['active']) ? 'active' : '';
                        $external = !empty($item['external']);
                        $badge = $item['badge'] ?? null;
                        ?>
                        <a class="nav-link <?= $activeClass ?>"
                            href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $external ? 'target="_blank" rel="noreferrer"' : '' ?>
                            title="<?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi <?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                            <span class="nav-link-label"><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if ($badge !== null && $badge !== '' && $badge !== 0): ?>
                                <span class="badge rounded-pill text-bg-danger ms-auto"><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a class="nav-link" href="<?= htmlspecialchars($url('profile'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-person-circle"></i>
            <span class="nav-link-label">Meu perfil</span>
        </a>
    </div>
</aside>
