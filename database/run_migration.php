<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$sqlFile = __DIR__ . '/migrations/001_create_users.sql';
if (!is_file($sqlFile))
{
    fwrite(STDERR, "Migration file not found.\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false)
{
    fwrite(STDERR, "Unable to read migration.\n");
    exit(1);
}

try
{
    Database::getConnection()->exec($sql);
    echo "Migration applied successfully.\n";
}
catch (Throwable $e)
{
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
