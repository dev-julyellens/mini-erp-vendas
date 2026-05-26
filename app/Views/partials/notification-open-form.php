<?php

declare(strict_types=1);

/** @var callable(string):string $url */
/** @var int $notificationId */
/** @var string $buttonClass */
/** @var string $buttonInnerHtml */

?>
<form method="post" action="<?= htmlspecialchars($url('notifications/open'), ENT_QUOTES, 'UTF-8') ?>" class="notification-open-form m-0">
    <?php require __DIR__ . '/csrf.php'; ?>
    <input type="hidden" name="id" value="<?= (int) $notificationId ?>">
    <button type="submit" class="<?= htmlspecialchars($buttonClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= $buttonInnerHtml ?>
    </button>
</form>