<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\BackupException;
use App\Services\BackupService;

$lockDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($lockDir) && !mkdir($lockDir, 0750, true) && !is_dir($lockDir))
{
    fwrite(STDERR, "Não foi possível preparar o diretório de backups.\n");
    exit(1);
}

$lockFile = $lockDir . DIRECTORY_SEPARATOR . '.backup_cron.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false)
{
    fwrite(STDERR, "Não foi possível criar lock do agendador.\n");
    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB))
{
    echo "Outro processo de backup já está em execução.\n";
    fclose($lockHandle);
    exit(0);
}

try
{
    $service = new BackupService();

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
finally
{
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}
