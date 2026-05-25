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

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars($url('favicon.svg'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
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
                <?php if ($canClientes): ?>
                    <a class="nav-link <?= $navPrefix('/customers') ?>" href="<?= htmlspecialchars($url('customers'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                <?php endif; ?>
                <?php if ($canProdutos): ?>
                    <a class="nav-link <?= $navPrefix('/products') ?>" href="<?= htmlspecialchars($url('products'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-box-seam"></i> Produtos
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
                    <span class="nav-link disabled text-secondary" title="Módulo em desenvolvimento">
                        <i class="bi bi-cash-stack"></i> Financeiro
                    </span>
                <?php endif; ?>
                <?php if ($canUsuarios): ?>
                    <a class="nav-link <?= $navPrefix('/audit-logs') ?>" href="<?= htmlspecialchars($url('audit-logs'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-journal-text"></i> Auditoria
                    </a>
                    <span class="nav-link disabled text-secondary" title="Módulo em desenvolvimento">
                        <i class="bi bi-person-gear"></i> Usuários
                    </span>
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
                    <span class="badge text-bg-light border">PHP + PostgreSQL</span>
                    <span class="text-muted small d-none d-md-inline">MVC · Services · Repositories</span>
                </div>
                <div class="d-flex align-items-center gap-2 gap-md-3 flex-wrap justify-content-end">
                    <?php $authUser = \App\Helpers\Auth::sessionSnapshot(); ?>
                    <?php if ($authUser !== null): ?>
                        <div class="text-end">
                            <div class="small fw-semibold text-dark text-truncate" style="max-width: 10rem;">
                                <?= htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="text-muted d-none d-md-block" style="font-size: 0.75rem;">
                                <?= htmlspecialchars($authUser['role'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <form method="post" action="<?= htmlspecialchars($url('logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                            <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                                <i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline"> Sair</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div class="text-muted small text-nowrap">
                        <?= date('d/m/Y H:i') ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="<?= htmlspecialchars($url('assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>