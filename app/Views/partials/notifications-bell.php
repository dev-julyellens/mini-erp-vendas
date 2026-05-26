<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var int $notificationUnreadCount */
/** @var list<\App\Models\Notification> $notificationRecent */
/** @var list<\App\Models\Notification> $notificationToasts */

use App\Services\NotificationService;

?>
<div class="dropdown notification-dropdown">
    <button type="button"
        class="btn btn-sm btn-secondary position-relative notification-bell-btn"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        aria-label="Notificações">
        <i class="bi bi-bell"></i>
        <?php if ($notificationUnreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger notification-badge">
                <?= $notificationUnreadCount > 99 ? '99+' : (int) $notificationUnreadCount ?>
            </span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow notification-menu p-0">
        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between gap-2">
            <span class="fw-semibold">Notificações</span>
            <?php if ($notificationUnreadCount > 0): ?>
                <span class="badge text-bg-primary"><?= (int) $notificationUnreadCount ?> nova(s)</span>
            <?php endif; ?>
        </div>
        <?php if ($notificationRecent === []): ?>
            <div class="px-3 py-4 text-muted small text-center">Nenhuma notificação recente.</div>
        <?php else: ?>
            <div class="notification-menu-list">
                <?php foreach ($notificationRecent as $n): ?>
                    <?php
                    $levelClass = NotificationService::levelBootstrapClass($n->level);
                    $buttonClass = 'dropdown-item notification-menu-item py-2 text-start border-0 w-100'
                        . (!$n->isRead() ? ' notification-menu-item-unread' : '');
                    ob_start();
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge text-bg-<?= htmlspecialchars($levelClass, ENT_QUOTES, 'UTF-8') ?> notification-type-badge">
                            <?= htmlspecialchars(NotificationService::typeLabel($n->type), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if (!$n->isRead()): ?>
                            <span class="notification-dot" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                    <div class="fw-semibold small"><?= htmlspecialchars($n->title, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted small text-truncate"><?= htmlspecialchars($n->message, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php
                    $buttonInnerHtml = ob_get_clean();
                    $notificationId = $n->id;
                    require dirname(__DIR__) . '/partials/notification-open-form.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="border-top p-2">
            <a class="btn btn-sm btn-secondary w-100" href="<?= htmlspecialchars($url('notifications'), ENT_QUOTES, 'UTF-8') ?>">
                Ver histórico completo
            </a>
        </div>
    </div>
</div>

<?php foreach ($notificationToasts as $toast): ?>
    <div class="visually-hidden" data-notification-toast
        data-toast-title="<?= htmlspecialchars($toast->title, ENT_QUOTES, 'UTF-8') ?>"
        data-toast-message="<?= htmlspecialchars($toast->message, ENT_QUOTES, 'UTF-8') ?>"
        data-toast-variant="<?= htmlspecialchars(NotificationService::levelBootstrapClass($toast->level), ENT_QUOTES, 'UTF-8') ?>"></div>
<?php endforeach; ?>