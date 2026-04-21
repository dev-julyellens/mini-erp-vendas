<?php

declare(strict_types=1);

/** @var int $page */
/** @var int $total */
/** @var int $perPage */
/** @var callable $url */
/** @var string $path */
/** @var array<string, string> $query */

$totalPages = (int) max(1, (int) ceil($total / $perPage));
$query = $query ?? [];

if ($totalPages <= 1) {
    return;
}

$build = static function (int $p) use ($url, $path, $query): string {
    $q = array_merge($query, ['page' => (string) $p]);
    return $url(trim($path, '/') . '?' . http_build_query($q));
};

?>
<nav aria-label="Paginação" class="mt-3">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars($build($page - 1), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($build($i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($build($page + 1), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
        </li>
    </ul>
</nav>
