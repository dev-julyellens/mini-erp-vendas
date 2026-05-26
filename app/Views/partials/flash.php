<?php

declare(strict_types=1);

/** @var array $flash */
$flash = $flash ?? [];

if (!empty($flash['success'])): ?>
    <div data-flash-toast data-flash-variant="success" data-flash-title="Sucesso" class="visually-hidden" role="status">
        <?= htmlspecialchars((string) $flash['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="alert alert-success alert-dismissible fade show flash-fallback" role="alert">
        <i class="bi bi-check-circle me-1"></i>
        <?= htmlspecialchars((string) $flash['success'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flash['error'])): ?>
    <div data-flash-toast data-flash-variant="danger" data-flash-title="Erro" class="visually-hidden" role="alert">
        <?= htmlspecialchars((string) $flash['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="alert alert-danger alert-dismissible fade show flash-fallback" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        <?= htmlspecialchars((string) $flash['error'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flash['warning'])): ?>
    <div data-flash-toast data-flash-variant="warning" data-flash-title="Atenção" class="visually-hidden" role="status">
        <?= htmlspecialchars((string) $flash['warning'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="alert alert-warning alert-dismissible fade show flash-fallback" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <?= htmlspecialchars((string) $flash['warning'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>

<?php if (!empty($flash['info'])): ?>
    <div data-flash-toast data-flash-variant="info" data-flash-title="Informação" class="visually-hidden" role="status">
        <?= htmlspecialchars((string) $flash['info'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="alert alert-primary alert-dismissible fade show flash-fallback" role="status">
        <i class="bi bi-info-circle me-1"></i>
        <?= htmlspecialchars((string) $flash['info'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endif; ?>