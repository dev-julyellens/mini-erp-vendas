<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\BackupSettings;
use PDO;

final class BackupSettingsRepository extends BaseRepository
{
    public function get(): BackupSettings
    {
        $stmt = $this->db->query(
            'SELECT enabled, run_hour, run_minute, frequency, last_run_at, updated_at, updated_by_user_id
             FROM backup_settings
             WHERE id = 1'
        );
        $row = $stmt->fetch();
        if ($row === false)
        {
            return BackupSettings::fromArray([
                'enabled' => false,
                'run_hour' => 2,
                'run_minute' => 0,
                'frequency' => 'daily',
                'last_run_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by_user_id' => null,
            ]);
        }

        return BackupSettings::fromArray($row);
    }

    public function update(bool $enabled, int $runHour, int $runMinute, ?int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE backup_settings
             SET enabled = :enabled,
                 run_hour = :run_hour,
                 run_minute = :run_minute,
                 updated_at = CURRENT_TIMESTAMP,
                 updated_by_user_id = :user_id
             WHERE id = 1'
        );
        $stmt->execute([
            'enabled' => $enabled,
            'run_hour' => $runHour,
            'run_minute' => $runMinute,
            'user_id' => $userId,
        ]);
    }

    public function markLastRun(): void
    {
        $this->db->exec(
            'UPDATE backup_settings SET last_run_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = 1'
        );
    }
}
