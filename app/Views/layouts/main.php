<?php

declare(strict_types=1);

use App\Helpers\Asset;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Services\ProfileService;

/** @var string $appName */
/** @var string $baseUrl */
/** @var string $__viewFile */
/** @var bool $needsDataTables */

$url = static function (string $path = '') use ($baseUrl): string
{
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
};

$userPrefsJson = '';
$bodyUserId = '';
$bodyCompanyId = '';
if (Auth::check())
{
    $uid = Auth::id();
    if ($uid !== null)
    {
        $bodyUserId = (string) $uid;
        $encoded = json_encode(
            (new ProfileService())->preferencesForUser($uid),
            JSON_UNESCAPED_UNICODE
        );
        $userPrefsJson = is_string($encoded) ? $encoded : '';
    }
    $snapshot = Auth::sessionSnapshot();
    if (!empty($snapshot['company_id']))
    {
        $bodyCompanyId = (string) $snapshot['company_id'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/theme-boot.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($url('favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if (!empty($needsDataTables)): ?>
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/css/design-system.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-app-name="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>"
    <?php if ($bodyUserId !== ''): ?> data-user-id="<?= htmlspecialchars($bodyUserId, ENT_QUOTES, 'UTF-8') ?>" <?php endif; ?>
    <?php if ($bodyCompanyId !== ''): ?> data-company-id="<?= htmlspecialchars($bodyCompanyId, ENT_QUOTES, 'UTF-8') ?>" <?php endif; ?>
    <?php if (Auth::check()): ?>
    data-csrf-token="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"
    <?php endif; ?>
    <?php if ($userPrefsJson !== ''): ?>
    data-user-prefs="<?= htmlspecialchars($userPrefsJson, ENT_QUOTES, 'UTF-8') ?>"
    data-prefs-url="<?= htmlspecialchars($url('profile/preferences'), ENT_QUOTES, 'UTF-8') ?>"
    <?php endif; ?>>
    <a class="skip-link" href="#mainContent">Ir para o conteúdo principal</a>
    <div id="srAnnounce" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>
    <div class="sidebar-backdrop" aria-hidden="true"></div>
    <div class="app-shell">
        <?php require dirname(__DIR__) . '/components/sidebar.php'; ?>
        <div class="content">
            <header class="topbar" role="banner">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-secondary d-lg-none" data-sidebar-toggle aria-label="Abrir menu">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-sm btn-secondary theme-toggle-btn" data-theme-toggle aria-label="Modo escuro" title="Modo escuro">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <?php require dirname(__DIR__) . '/partials/notifications-bell.php'; ?>
                    <?php $authUser = \App\Helpers\Auth::sessionSnapshot(); ?>
                    <?php if ($authUser !== null): ?>
                        <?php if (!empty($authUser['company_name'])): ?>
                            <a class="btn btn-sm btn-primary text-nowrap d-inline-flex align-items-center"
                                href="<?= htmlspecialchars($url('select-company'), ENT_QUOTES, 'UTF-8') ?>"
                                title="Trocar empresa: <?= htmlspecialchars((string) $authUser['company_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-building me-1"></i>
                                <span class="text-truncate d-none d-sm-inline" style="max-width: 8rem;">
                                    <?= htmlspecialchars((string) $authUser['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </a>
                        <?php endif; ?>
                        <a class="text-end text-decoration-none user-menu-link" href="<?= htmlspecialchars($url('profile'), ENT_QUOTES, 'UTF-8') ?>" title="Meu perfil">
                            <div class="small fw-semibold text-truncate user-menu-name" style="max-width: 10rem;">
                                <?= htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="text-muted d-none d-md-block" style="font-size: 0.75rem;">
                                <?= htmlspecialchars($authUser['role'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($authUser['company_role'])): ?>
                                    · <?= htmlspecialchars((string) $authUser['company_role'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                        </a>
                        <form method="post" action="<?= htmlspecialchars($url('logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0" data-global-loading="false">
                            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                            <button type="submit" class="btn btn-sm btn-secondary text-nowrap" aria-label="Sair da conta">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="d-none d-sm-inline"> Sair</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div class="text-muted small text-nowrap d-none d-lg-block">
                        <?= htmlspecialchars(\App\Helpers\DateHelper::nowBr(), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </header>
            <main class="page" id="mainContent" tabindex="-1">
                <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
                <?php require $__viewFile; ?>
            </main>
        </div>
    </div>

    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

    <div id="globalLoading" class="global-loading" role="status" aria-live="polite" aria-hidden="true" aria-busy="false">
        <div class="spinner-wrap">
            <div class="spinner-border text-primary" aria-hidden="true"></div>
            <span class="text-muted">Processando...</span>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/confirm-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <?php if (!empty($needsDataTables)): ?>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
            integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs"
            crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <?php endif; ?>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/input-masks.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/a11y.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $scriptSrc): ?>
            <?php
            $scriptPath = is_string($scriptSrc) ? $scriptSrc : '';
            if ($scriptPath === '')
            {
                continue;
            }
            $scriptUrl = str_starts_with($scriptPath, 'http')
                ? $scriptPath
                : Asset::versionedUrl($baseUrl, $scriptPath);
            ?>
            <script src="<?= htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>