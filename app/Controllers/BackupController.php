<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BackupException;
use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Flash;
use App\Services\BackupService;
use App\Services\PermissionService;

final class BackupController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $service = new BackupService();
        $user = Auth::user();
        $role = $user !== null ? $user->role : '';

        $logs = $service->listLogs($page, self::PER_PAGE);
        $settings = $service->getSettings();
        $files = $service->listBackupFiles();
        $isAdmin = (new PermissionService())->isAdminRole($role);

        $this->view('backups/index', [
            'logs' => $logs['items'],
            'total' => $logs['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'files' => $files,
            'settings' => $settings,
            'isAdmin' => $isAdmin,
            'operationLabels' => BackupService::OPERATION_LABELS,
            'triggerLabels' => BackupService::TRIGGER_LABELS,
            'statusLabels' => BackupService::STATUS_LABELS,
            'cronHint' => $service->cronCommandHint(),
            'flash' => Flash::pull(),
            'formatBytes' => static fn(int $bytes): string => $service->formatBytes($bytes),
        ]);
    }

    public function create(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('login');
        }

        try
        {
            $service = new BackupService();
            $result = $service->createBackup(BackupService::TRIGGER_MANUAL, $user->role, $user->id);
            Flash::success(
                'Backup criado: ' . $result['filename']
                    . ' (' . $service->formatBytes($result['size']) . ').'
            );
        }
        catch (BackupException $e)
        {
            Flash::error($e->getMessage());
        }

        $this->redirect('backups');
    }

    public function download(): void
    {
        $filename = trim((string) ($_GET['file'] ?? ''));
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('login');
        }

        try
        {
            $service = new BackupService();
            $resolved = $service->resolveDownload($filename, $user->role);
            $path = $resolved['path'];

            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $resolved['filename'] . '"');
            header('Content-Length: ' . (string) filesize($path));
            header('Cache-Control: no-store');

            readfile($path);
            exit;
        }
        catch (BackupException $e)
        {
            Flash::error($e->getMessage());
            $this->redirect('backups');
        }
    }

    public function restore(): void
    {
        $filename = trim((string) ($_POST['filename'] ?? ''));
        $confirm = trim((string) ($_POST['confirm'] ?? ''));

        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('login');
        }

        if ($confirm !== 'RESTAURAR')
        {
            Flash::error('Confirmação inválida. Digite RESTAURAR para confirmar.');
            $this->redirect('backups');
        }

        try
        {
            $service = new BackupService();
            $service->restore($filename, $user->role, $user->id);
            Flash::success('Banco restaurado a partir de ' . $filename . '.');
        }
        catch (BackupException $e)
        {
            Flash::error($e->getMessage());
        }

        $this->redirect('backups');
    }

    public function updateSchedule(): void
    {
        $enabled = isset($_POST['enabled']);
        $runHour = (int) ($_POST['run_hour'] ?? 2);
        $runMinute = (int) ($_POST['run_minute'] ?? 0);

        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('login');
        }

        try
        {
            $service = new BackupService();
            $service->updateSchedule($enabled, $runHour, $runMinute, $user->role, $user->id);
            Flash::success('Agendamento de backup atualizado.');
        }
        catch (BackupException $e)
        {
            Flash::error($e->getMessage());
        }

        $this->redirect('backups');
    }

    public function cleanup(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('login');
        }

        try
        {
            $service = new BackupService();
            $removed = $service->cleanupOldBackups($user->role, $user->id);
            Flash::success($removed > 0
                ? $removed . ' backup(s) antigo(s) removido(s).'
                : 'Nenhum backup antigo para remover.');
        }
        catch (BackupException $e)
        {
            Flash::error($e->getMessage());
        }

        $this->redirect('backups');
    }
}
