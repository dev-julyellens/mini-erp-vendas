<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BackupException;
use App\Core\Logger;
use App\Helpers\PgCli;
use App\Models\BackupSettings;
use App\Repositories\BackupLogRepository;
use App\Repositories\BackupSettingsRepository;

final class BackupService
{
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_AUTOMATIC = 'automatic';
    public const TRIGGER_CRON = 'cron';

    public const OPERATION_LABELS = [
        'backup' => 'Backup',
        'restore' => 'Restauração',
        'cleanup' => 'Limpeza',
    ];

    public const TRIGGER_LABELS = [
        'manual' => 'Manual',
        'automatic' => 'Automático',
        'cron' => 'Agendador (cron)',
    ];

    public const STATUS_LABELS = [
        'success' => 'Sucesso',
        'failed' => 'Falha',
        'running' => 'Em execução',
    ];

    private BackupLogRepository $logs;
    private BackupSettingsRepository $settings;
    private PermissionService $permissions;

    /** @var array<string, mixed>|null */
    private ?array $config = null;

    public function __construct(
        ?BackupLogRepository $logs = null,
        ?BackupSettingsRepository $settings = null,
        ?PermissionService $permissions = null
    )
    {
        $this->logs = $logs ?? new BackupLogRepository();
        $this->settings = $settings ?? new BackupSettingsRepository();
        $this->permissions = $permissions ?? new PermissionService();
    }

    /**
     * @return array{items: list<\App\Models\BackupLog>, total: int}
     */
    public function listLogs(int $page, int $perPage): array
    {
        return $this->logs->search($page, $perPage);
    }

    /**
     * @return list<array{filename: string, size: int, created_at: string}>
     */
    public function listBackupFiles(): array
    {
        $dir = $this->storagePath();
        if (!is_dir($dir))
        {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
        if ($files === false)
        {
            return [];
        }

        $items = [];
        foreach ($files as $path)
        {
            if (!is_file($path))
            {
                continue;
            }

            $filename = basename($path);
            if (!$this->isValidBackupFilename($filename))
            {
                continue;
            }

            $mtime = filemtime($path);
            $items[] = [
                'filename' => $filename,
                'size' => (int) filesize($path),
                'created_at' => $mtime !== false ? date('Y-m-d H:i:s', $mtime) : date('Y-m-d H:i:s'),
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        return $items;
    }

    public function getSettings(): BackupSettings
    {
        return $this->settings->get();
    }

    public function updateSchedule(bool $enabled, int $runHour, int $runMinute, string $role, ?int $userId): void
    {
        $this->assertAdmin($role);

        if ($runHour < 0 || $runHour > 23)
        {
            throw new BackupException('Hora inválida. Use um valor entre 0 e 23.');
        }

        if ($runMinute < 0 || $runMinute > 59)
        {
            throw new BackupException('Minuto inválido. Use um valor entre 0 e 59.');
        }

        $this->settings->update($enabled, $runHour, $runMinute, $userId);
    }

    /**
     * @return array{filename: string, size: int, duration_ms: int}
     */
    public function createBackup(string $trigger, string $role, ?int $userId): array
    {
        $this->assertAdmin($role);

        $started = microtime(true);
        $filename = $this->generateFilename();
        $path = $this->storagePath() . DIRECTORY_SEPARATOR . $filename;

        $this->ensureStorageDirectory();

        try
        {
            $this->runPgDump($path);

            if (!is_file($path) || filesize($path) === 0)
            {
                throw new BackupException('Arquivo de backup não foi gerado.');
            }

            $size = (int) filesize($path);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $this->logs->insert(
                'backup',
                $trigger,
                'success',
                $filename,
                $size,
                'Backup concluído com sucesso.',
                $userId,
                $durationMs
            );

            $this->cleanupOldBackups($role, $userId, false);

            Logger::info('Backup concluído.', [
                'filename' => $filename,
                'size' => $size,
                'trigger' => $trigger,
            ]);

            return [
                'filename' => $filename,
                'size' => $size,
                'duration_ms' => $durationMs,
            ];
        }
        catch (\Throwable $e)
        {
            if (is_file($path))
            {
                @unlink($path);
            }

            Logger::exception($e, 'Falha ao criar backup.', ['trigger' => $trigger]);

            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $this->logs->insert(
                'backup',
                $trigger,
                'failed',
                $filename,
                null,
                $e->getMessage(),
                $userId,
                $durationMs
            );

            throw $e instanceof BackupException ? $e : new BackupException($e->getMessage(), 0, $e);
        }
    }

    public function restore(string $filename, string $role, ?int $userId): void
    {
        $this->assertAdmin($role);

        $path = $this->resolveBackupFilePath($filename);

        $started = microtime(true);

        try
        {
            $this->runPsqlRestore($path);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $this->logs->insert(
                'restore',
                self::TRIGGER_MANUAL,
                'success',
                $filename,
                (int) filesize($path),
                'Restauração concluída com sucesso.',
                $userId,
                $durationMs
            );

            Logger::warning('Backup restaurado.', ['filename' => $filename, 'user_id' => $userId]);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'Falha ao restaurar backup.', ['filename' => $filename]);

            $durationMs = (int) round((microtime(true) - $started) * 1000);
            $this->logs->insert(
                'restore',
                self::TRIGGER_MANUAL,
                'failed',
                $filename,
                (int) filesize($path),
                $e->getMessage(),
                $userId,
                $durationMs
            );

            throw $e instanceof BackupException ? $e : new BackupException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function resolveDownload(string $filename, string $role): array
    {
        $this->assertAdmin($role);

        $path = $this->resolveBackupFilePath($filename);

        return ['path' => $path, 'filename' => $filename];
    }

    public function runScheduledIfDue(): bool
    {
        $settings = $this->settings->get();
        if (!$settings->enabled)
        {
            return false;
        }

        if (!$this->isDue($settings))
        {
            return false;
        }

        $this->createBackup(self::TRIGGER_CRON, PermissionService::ROLE_ADMIN, null);
        $this->settings->markLastRun();

        return true;
    }

    public function cleanupOldBackups(string $role, ?int $userId, bool $assertAdmin = true): int
    {
        if ($assertAdmin)
        {
            $this->assertAdmin($role);
        }

        $retentionDays = max(1, (int) (($this->config()['backup']['retention_days'] ?? 30)));
        $cutoff = time() - ($retentionDays * 86400);
        $removed = 0;

        foreach ($this->listBackupFiles() as $file)
        {
            try
            {
                $path = $this->resolveBackupFilePath($file['filename']);
            }
            catch (BackupException $ignored)
            {
                continue;
            }

            $mtime = filemtime($path);
            if ($mtime === false || $mtime >= $cutoff)
            {
                continue;
            }

            if (@unlink($path))
            {
                $removed++;
                $this->logs->insert(
                    'cleanup',
                    $userId !== null ? self::TRIGGER_MANUAL : self::TRIGGER_AUTOMATIC,
                    'success',
                    $file['filename'],
                    $file['size'],
                    'Backup antigo removido (retenção: ' . $retentionDays . ' dias).',
                    $userId,
                    null
                );
            }
        }

        return $removed;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)
        {
            return $bytes . ' B';
        }

        if ($bytes < 1048576)
        {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    public function cronCommandHint(): string
    {
        $root = dirname(__DIR__, 2);

        return 'php ' . $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'backup_cron.php';
    }

    private function isDue(BackupSettings $settings): bool
    {
        $now = new \DateTimeImmutable('now');
        $scheduledToday = $now->setTime($settings->run_hour, $settings->run_minute, 0);

        if ($now < $scheduledToday)
        {
            return false;
        }

        if ($settings->last_run_at === null)
        {
            return true;
        }

        $lastRun = new \DateTimeImmutable($settings->last_run_at);

        return $lastRun->format('Y-m-d') !== $now->format('Y-m-d');
    }

    private function assertAdmin(string $role): void
    {
        if (!$this->permissions->isAdminRole($role))
        {
            throw new BackupException('Apenas administradores podem gerenciar backups.');
        }
    }

    private function generateFilename(): string
    {
        return 'backup_' . date('Y-m-d_His') . '.sql';
    }

    private function isValidBackupFilename(string $filename): bool
    {
        return (bool) preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $filename);
    }

    private function resolveBackupFilePath(string $filename): string
    {
        if (!$this->isValidBackupFilename($filename))
        {
            throw new BackupException('Nome de arquivo inválido.');
        }

        $this->ensureStorageDirectory();

        $storageDir = $this->storagePath();
        $realStorage = realpath($storageDir);
        if ($realStorage === false)
        {
            throw new BackupException('Diretório de backups indisponível.');
        }

        $candidate = $realStorage . DIRECTORY_SEPARATOR . $filename;
        $realPath = realpath($candidate);
        if ($realPath === false || !is_file($realPath))
        {
            throw new BackupException('Arquivo de backup não encontrado.');
        }

        if (!$this->isPathInsideDirectory($realPath, $realStorage))
        {
            throw new BackupException('Arquivo de backup inválido.');
        }

        return $realPath;
    }

    private function isPathInsideDirectory(string $path, string $directory): bool
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $directory = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory), DIRECTORY_SEPARATOR);
        $prefix = $directory . DIRECTORY_SEPARATOR;

        if (PHP_OS_FAMILY === 'Windows')
        {
            return str_starts_with(strtolower($path), strtolower($prefix));
        }

        return str_starts_with($path, $prefix);
    }

    private function ensureStorageDirectory(): void
    {
        $dir = $this->storagePath();
        if (is_dir($dir))
        {
            return;
        }

        if (!mkdir($dir, 0750, true) && !is_dir($dir))
        {
            throw new BackupException('Não foi possível criar o diretório de backups.');
        }
    }

    private function storagePath(): string
    {
        $backup = $this->backupConfig();

        $configured = (string) ($backup['storage_path'] ?? '');
        if ($configured !== '')
        {
            return $configured;
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
    }

    /**
     * @return array<string, mixed>
     */
    private function backupConfig(): array
    {
        $backup = $this->config()['backup'] ?? [];

        return is_array($backup) ? $backup : [];
    }

    private function runPgDump(string $outputPath): void
    {
        $db = $this->databaseConfig();
        $backup = $this->backupConfig();

        $pgDump = PgCli::resolveBinary((string) ($backup['pg_dump_path'] ?? ''), 'pg_dump');

        $args = [
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--username=' . $db['username'],
            '--dbname=' . $db['database'],
            '--no-owner',
            '--no-acl',
            '--clean',
            '--if-exists',
            '--format=plain',
            '--file=' . $outputPath,
        ];

        $result = PgCli::run($pgDump, $args, ['PGPASSWORD' => $db['password']]);
        if ($result['exitCode'] !== 0)
        {
            throw new BackupException('pg_dump falhou: ' . $result['output']);
        }
    }

    private function runPsqlRestore(string $inputPath): void
    {
        $db = $this->databaseConfig();
        $backup = $this->backupConfig();

        $psql = PgCli::resolveBinary((string) ($backup['psql_path'] ?? ''), 'psql');

        $args = [
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--username=' . $db['username'],
            '--dbname=' . $db['database'],
            '--file=' . $inputPath,
            '--single-transaction',
            '--set=ON_ERROR_STOP=1',
        ];

        $result = PgCli::run($psql, $args, ['PGPASSWORD' => $db['password']]);
        if ($result['exitCode'] !== 0)
        {
            throw new BackupException('psql falhou: ' . $result['output']);
        }
    }

    /**
     * @return array{host: string, port: string, database: string, username: string, password: string}
     */
    private function databaseConfig(): array
    {
        $db = $this->config()['database'] ?? [];
        if (!is_array($db))
        {
            $db = [];
        }

        return [
            'host' => (string) ($db['host'] ?? '127.0.0.1'),
            'port' => (string) ($db['port'] ?? '5432'),
            'database' => (string) ($db['database'] ?? 'mini_erp_vendas'),
            'username' => (string) ($db['username'] ?? 'postgres'),
            'password' => (string) ($db['password'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        if ($this->config === null)
        {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            $this->config = is_array($app) ? $app : [];
        }

        return $this->config;
    }
}
