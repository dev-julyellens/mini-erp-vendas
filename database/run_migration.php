<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$files = glob(__DIR__ . '/migrations/*.sql');
if ($files === false || $files === [])
{
    fwrite(STDERR, "No migration files found.\n");
    exit(1);
}

sort($files);
$db = Database::getConnection();

foreach ($files as $sqlFile)
{
    $sql = file_get_contents($sqlFile);
    if ($sql === false)
    {
        fwrite(STDERR, 'Unable to read migration: ' . basename($sqlFile) . "\n");
        exit(1);
    }

    try
    {
        $db->exec($sql);
        echo 'Applied: ' . basename($sqlFile) . "\n";
    }
    catch (Throwable $e)
    {
        fwrite(STDERR, 'Migration failed (' . basename($sqlFile) . '): ' . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "All migrations applied successfully.\n";
