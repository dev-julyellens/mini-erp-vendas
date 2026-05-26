<?php

declare(strict_types=1);

use App\Helpers\DateHelper;
use App\Services\NotificationService;

/** @var callable(string):string $url */
/** @var list<\App\Models\Notification> $notifications */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array{type: string, unread: string} $filters */
/** @var array<string, string> $paginationQuery */
/** @var array<string, string> $typeLabels */
/** @var list<string> $types */

$displayType = in_array($filters['type'], $types, true) ? $filters['type'] : '';

$title = 'Notificações';
$subtitle = 'Alertas operacionais persistidos por empresa';
ob_start();
?>
<form method="post" action="<?= htmlspecialchars($url('notifications/read-all'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
    <button type="submit" class="btn btn-secondary btn-sm">
        <i class="bi bi-check2-all"></i> Marcar todas como lidas
    </button>
</form>
<?php
$actionsHtml = ob_get_clean();
require dirname(__DIR__) . '/components/page-header.php';

ob_start();
?>
<div class="col-12 col-md-4">
    <label class="form-label" for="filter_type">Tipo</label>
    <select class="form-select" id="filter_type" name="type">
        <option value="">Todos</option>
        <?php foreach ($types as $typeKey): ?>
            <option value="<?= htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8') ?>" <?= $displayType === $typeKey ? 'selected' : '' ?>>
                <?= htmlspecialchars($typeLabels[$typeKey] ?? $typeKey, ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-12 col-md-4 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="filter_unread" name="unread" value="1"
            <?= $filters['unread'] === '1' ? 'checked' : '' ?>>
        <label class="form-check-label" for="filter_unread">Somente não lidas</label>
    </div>
</div>
<?php
$filterContent = ob_get_clean();
$filterAction = $url('notifications');
$filterClearHref = $url('notifications');
$filterActionsColClass = 'col-12 col-md-auto';
require dirname(__DIR__) . '/components/filter-panel.php';
?>

<div class="card-soft p-3 p-md-4">
    <?php if ($notifications === []): ?>
        <div class="text-muted py-4 text-center">
            <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
            Nenhuma notificação encontrada.
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush notification-history">
            <?php foreach ($notifications as $n): ?>
                <?php
                $levelClass = NotificationService::levelBootstrapClass($n->level);
                $isUnread = !$n->isRead();
                $openButtonClass = 'flex-grow-1 text-decoration-none text-body notification-item-link text-start border-0 bg-transparent p-0 w-100';
                ob_start();
                ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge text-bg-<?= htmlspecialchars($levelClass, ENT_QUOTES, 'UTF-8') ?> notification-type-badge">
                        <?= htmlspecialchars(NotificationService::typeLabel($n->type), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($isUnread): ?>
                        <span class="badge text-bg-primary">Nova</span>
                    <?php endif; ?>
                </div>
                <h2 class="h6 mb-1"><?= htmlspecialchars($n->title, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="mb-1 text-muted small"><?= htmlspecialchars($n->message, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="small text-secondary">
                    <?= htmlspecialchars(DateHelper::toBrDateTime($n->created_at), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php
                $openButtonInner = ob_get_clean();
                ?>
                <div class="list-group-item px-0 notification-item <?= $isUnread ? 'notification-item-unread' : '' ?>">
                    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-2">
                        <?php
                        $notificationId = $n->id;
                        $buttonClass = $openButtonClass;
                        $buttonInnerHtml = $openButtonInner;
                        require dirname(__DIR__) . '/partials/notification-open-form.php';
                        ?>
                        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                            <?php
                            $buttonClass = 'btn btn-sm btn-ghost';
                            ob_start();
                            ?>Ver detalhes<?php
                                            $buttonInnerHtml = ob_get_clean();
                                            require dirname(__DIR__) . '/partials/notification-open-form.php';
                                            ?>
                            <?php if ($isUnread): ?>
                                <form method="post" action="<?= htmlspecialchars($url('notifications/read'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                                    <?php require dirname(__DIR__) . '/partials/csrf.php'; ?>
                                    <input type="hidden" name="id" value="<?= (int) $n->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Marcar como lida</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $path = 'notifications';
        $query = $paginationQuery;
        require dirname(__DIR__) . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</div>