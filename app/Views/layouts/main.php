<?php

declare(strict_types=1);

/** @var string $appName */
/** @var string $baseUrl */
/** @var string $__viewFile */

$currentPath = \App\Helpers\PathHelper::requestPath();

$url = static function (string $path = '') use ($baseUrl): string
{
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
};

$navActive = static function (string $path) use ($currentPath): string
{
    return $currentPath === $path ? 'active' : '';
};

$navPrefix = static function (string $prefix) use ($currentPath): string
{
    $prefix = '/' . trim($prefix, '/');
    if ($prefix === '//')
    {
        $prefix = '/';
    }
    if ($prefix !== '/' && strpos($currentPath, $prefix) === 0)
    {
        return 'active';
    }
    return '';
};

$canClientes = \App\Helpers\Permission::canView('clientes');
$canProdutos = \App\Helpers\Permission::canView('produtos');
$canVendas = \App\Helpers\Permission::canView('vendas');
$canEstoque = \App\Helpers\Permission::canView('estoque');
$canFinanceiro = \App\Helpers\Permission::canView('financeiro');
$canUsuarios = \App\Helpers\Permission::canView('usuarios');
$canRelatorios = $canVendas || $canEstoque || $canFinanceiro;
$isPlatformAdmin = (new \App\Services\PlatformAdminService())->isPlatformAdmin();
$canManageLinks = $isPlatformAdmin || \App\Helpers\Permission::can('usuarios', 'editar');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <script>
        (function() {
            try {
                var t = localStorage.getItem("mini-erp-theme");
                if (t === "dark") document.documentElement.setAttribute("data-theme", "dark");
            } catch (e) {}
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($url('favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="sidebar-backdrop" aria-hidden="true"></div>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">M</div>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></div>
                    <small class="text-secondary">Painel</small>
                </div>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= $navActive('/') ?>" href="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link <?= $navPrefix('/notifications') ?>" href="<?= htmlspecialchars($url('notifications'), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-bell"></i> Notificações
                    <?php if (!empty($notificationUnreadCount) && (int) $notificationUnreadCount > 0): ?>
                        <span class="badge rounded-pill text-bg-danger ms-1"><?= (int) $notificationUnreadCount > 99 ? '99+' : (int) $notificationUnreadCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($canClientes): ?>
                    <a class="nav-link <?= $navPrefix('/customers') ?>" href="<?= htmlspecialchars($url('customers'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                <?php endif; ?>
                <?php if ($canProdutos): ?>
                    <a class="nav-link <?= $navPrefix('/products') ?>" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-box-seam"></i> Produtos
                    </a>
                    <a class="nav-link <?= $navPrefix('/services') ?>" href="<?= htmlspecialchars($url('services'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-tools"></i> Serviços
                    </a>
                <?php endif; ?>
                <?php if ($canVendas): ?>
                    <a class="nav-link <?= $navPrefix('/orders') ?>" href="<?= htmlspecialchars($url('orders'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-receipt"></i> Vendas
                    </a>
                <?php endif; ?>
                <?php if ($canEstoque): ?>
                    <a class="nav-link <?= $navPrefix('/stock-movements') ?>" href="<?= htmlspecialchars($url('stock-movements'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-archive"></i> Estoque
                    </a>
                <?php endif; ?>
                <?php if ($canFinanceiro): ?>
                    <a class="nav-link <?= $navPrefix('/finance') ?>" href="<?= htmlspecialchars($url('finance'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-cash-stack"></i> Financeiro
                    </a>
                <?php endif; ?>
                <?php if ($canRelatorios): ?>
                    <a class="nav-link <?= $navPrefix('/reports') ?>" href="<?= htmlspecialchars($url('reports'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-bar-chart-line"></i> Relatórios
                    </a>
                <?php endif; ?>
                <?php if ($canUsuarios): ?>
                    <a class="nav-link <?= $navPrefix('/audit-logs') ?>" href="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-journal-text"></i> Auditoria
                    </a>
                    <a class="nav-link <?= $navPrefix('/access-logs') ?>" href="<?= htmlspecialchars($url('access-logs'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-door-open"></i> Logs de acesso
                    </a>
                    <a class="nav-link <?= $navPrefix('/backups') ?>" href="<?= htmlspecialchars($url('backups'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-hdd-stack"></i> Backup
                    </a>
                    <a class="nav-link <?= $navActive('/subscription') ?>" href="<?= htmlspecialchars($url('subscription'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-credit-card"></i> Assinatura
                    </a>
                    <?php if ($isPlatformAdmin): ?>
                        <a class="nav-link <?= $navPrefix('/admin/users') ?>" href="<?= htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-person-gear"></i> Usuários
                        </a>
                        <a class="nav-link <?= $navPrefix('/admin/companies') ?>" href="<?= htmlspecialchars($url('admin/companies'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-buildings"></i> Empresas
                        </a>
                        <a class="nav-link <?= $navPrefix('/admin/saas') ?>" href="<?= htmlspecialchars($url('admin/saas'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-cloud"></i> SaaS
                        </a>
                    <?php endif; ?>
                    <?php if ($canManageLinks): ?>
                        <a class="nav-link <?= $navPrefix('/user-companies') ?>" href="<?= htmlspecialchars($url('user-companies'), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-link-45deg"></i> Vínculos
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($canProdutos || $canVendas): ?>
                    <hr class="border-secondary-subtle">
                    <div class="px-2 pb-1 small text-secondary text-uppercase">API</div>
                    <?php if ($canProdutos): ?>
                        <a class="nav-link" href="<?= htmlspecialchars($url('api/products'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
                            <i class="bi bi-braces"></i> /api/products
                        </a>
                    <?php endif; ?>
                    <?php if ($canVendas): ?>
                        <a class="nav-link" href="<?= htmlspecialchars($url('api/orders'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">
                            <i class="bi bi-braces"></i> /api/orders
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </aside>
        <div class="content">
            <header class="topbar">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" data-sidebar-toggle aria-label="Abrir menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="badge text-bg-light border d-none d-sm-inline">PHP + PostgreSQL</span>
                    <span class="text-muted small d-none d-md-inline">MVC · Services · Repositories</span>
                </div>
                <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle-btn" data-theme-toggle aria-label="Modo escuro" title="Modo escuro">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <?php require dirname(__DIR__) . '/partials/notifications-bell.php'; ?>
                    <?php $authUser = \App\Helpers\Auth::sessionSnapshot(); ?>
                    <?php if ($authUser !== null): ?>
                        <?php if (!empty($authUser['company_name'])): ?>
                            <a class="btn btn-sm btn-outline-primary text-nowrap d-inline-flex align-items-center"
                                href="<?= htmlspecialchars($url('select-company'), ENT_QUOTES, 'UTF-8') ?>"
                                title="Trocar empresa: <?= htmlspecialchars((string) $authUser['company_name'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-building me-1"></i>
                                <span class="text-truncate d-none d-sm-inline" style="max-width: 8rem;">
                                    <?= htmlspecialchars((string) $authUser['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </a>
                        <?php endif; ?>
                        <a class="text-end text-decoration-none" href="<?= htmlspecialchars($url('profile'), ENT_QUOTES, 'UTF-8') ?>" title="Meu perfil">
                            <div class="small fw-semibold text-dark text-truncate" style="max-width: 10rem;">
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
                            <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                                <i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline"> Sair</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div class="text-muted small text-nowrap">
                        <?= htmlspecialchars(\App\Helpers\DateHelper::nowBr(), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </header>
            <main class="page">
                <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
                <?php require $__viewFile; ?>
            </main>
        </div>
    </div>

    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

    <div id="globalLoading" class="global-loading" aria-hidden="true" aria-busy="false">
        <div class="spinner-wrap">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <span class="text-muted">Processando...</span>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/partials/confirm-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
        integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs"
        crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= htmlspecialchars($url('assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php if (!empty($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $scriptSrc): ?>
            <?php
            $scriptPath = is_string($scriptSrc) ? $scriptSrc : '';
            if ($scriptPath === '')
            {
                continue;
            }
            $scriptUrl = str_starts_with($scriptPath, 'http') ? $scriptPath : $url($scriptPath);
            ?>
            <script src="<?= htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>