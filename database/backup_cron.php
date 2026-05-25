<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\BackupException;
use App\Services\BackupService;

$service = new BackupService();

try
{
    if ($service->runScheduledIfDue())
    {
        echo "Backup automático executado com sucesso.\n";
        exit(0);
    }

    echo "Nenhum backup agendado para este momento.\n";
    exit(0);
}
catch (BackupException $e)
{
    fwrite(STDERR, 'Erro no backup automático: ' . $e->getMessage() . "\n");
    exit(1);
}
catch (Throwable $e)
{
    fwrite(STDERR, 'Falha inesperada: ' . $e->getMessage() . "\n");
    exit(1);
}
