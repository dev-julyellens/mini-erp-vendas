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

if ($totalPages <= 1 && $total <= $perPage)
{
    if ($total > 0): ?>
        <div class="pagination-summary mt-3">
            <?= (int) $total ?> registro<?= $total === 1 ? '' : 's' ?>
        </div>
<?php endif;
    return;
}

$build = static function (int $p) use ($url, $path, $query): string
{
    $q = array_merge($query, ['page' => (string) $p]);
    return $url(trim($path, '/') . '?' . http_build_query($q));
};

$from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
$to = min($page * $perPage, $total);

$window = 2;
$pages = [];
$pages[] = 1;
for ($i = max(2, $page - $window); $i <= min($totalPages - 1, $page + $window); $i++)
{
    $pages[] = $i;
}
if ($totalPages > 1)
{
    $pages[] = $totalPages;
}
$pages = array_values(array_unique($pages));
sort($pages);

?>
<nav aria-label="Paginação" class="pagination-wrap mt-3">
    <div class="pagination-summary">
        Exibindo <strong><?= (int) $from ?>–<?= (int) $to ?></strong> de <strong><?= (int) $total ?></strong>
    </div>
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars($build($page - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Página anterior">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php
        $prev = 0;
        foreach ($pages as $i):
            if ($prev > 0 && $i - $prev > 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($build($i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
            </li>
        <?php $prev = $i;
        endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($build($page + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="Próxima página">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>