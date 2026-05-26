<?php

declare(strict_types=1);

/**
 * Verifica UTF-8 sem BOM nos arquivos-fonte do projeto.
 * Uso: php bin/check-encoding.php
 */

$root = dirname(__DIR__);
$extensions = ['php', 'js', 'css', 'sql', 'md', 'json', 'neon', 'xml', 'htaccess'];
$skipDirs = ['vendor', 'node_modules', '.git', '.phpunit.cache', 'storage/backups', 'storage/logs', 'storage/avatars'];

$bomFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file)
{
    if (!$file->isFile())
    {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

    foreach ($skipDirs as $skip)
    {
        if (str_starts_with($relative, $skip . '/') || $relative === $skip)
        {
            continue 2;
        }
    }

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $extensions, true))
    {
        continue;
    }

    $handle = fopen($path, 'rb');
    if ($handle === false)
    {
        continue;
    }
    $head = fread($handle, 3);
    fclose($handle);

    if ($head === "\xEF\xBB\xBF")
    {
        $bomFiles[] = $relative;
    }
}

if ($bomFiles === [])
{
    echo "OK: nenhum arquivo com UTF-8 BOM encontrado.\n";
    exit(0);
}

echo "ERRO: arquivos com UTF-8 BOM (remover EF BB BF):\n";
foreach ($bomFiles as $file)
{
    echo "  - {$file}\n";
}

exit(1);
