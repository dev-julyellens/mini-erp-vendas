<?php

declare(strict_types=1);

use App\Helpers\Asset;

/** @var string $appName */
/** @var string $baseUrl */
/** @var string $__viewFile */

$url = static function (string $path = '') use ($baseUrl): string
{
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
};

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> — Acesso</title>
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
    <link rel="stylesheet" href="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/css/design-system.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body class="auth-page" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-app-name="<?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>">
    <a class="skip-link" href="#authMain">Ir para o formulário de acesso</a>
    <div id="srAnnounce" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>
    <div class="auth-toolbar">
        <button type="button" class="btn btn-sm btn-secondary theme-toggle-btn" data-theme-toggle aria-label="Modo escuro" title="Modo escuro">
            <i class="bi bi-moon-stars"></i>
        </button>
    </div>
    <div class="auth-shell">
        <main class="auth-card card-soft p-4 p-md-5" id="authMain" tabindex="-1">
            <div class="text-center mb-4">
                <div class="auth-brand-mark mx-auto mb-3" aria-hidden="true">M</div>
                <h1 class="h4 mb-1"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted small mb-0">Acesso seguro ao painel</p>
            </div>
            <?php require dirname(__DIR__) . '/partials/flash.php'; ?>
            <?php require $__viewFile; ?>
        </main>
    </div>
    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/auth-lite.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(Asset::versionedUrl($baseUrl, 'assets/js/a11y.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>