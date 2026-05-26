<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserPreferenceRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * @return array{
     *   theme: string,
     *   sidebar_collapsed: bool,
     *   sidebar_pinned: bool,
     *   dashboard_tab: string
     * }|null
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT theme, sidebar_collapsed, sidebar_pinned, dashboard_tab
             FROM user_preferences WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false)
        {
            return null;
        }

        return [
            'theme' => (string) $row['theme'],
            'sidebar_collapsed' => self::toBool($row['sidebar_collapsed']),
            'sidebar_pinned' => self::toBool($row['sidebar_pinned']),
            'dashboard_tab' => (string) $row['dashboard_tab'],
        ];
    }

    /**
     * @param array{
     *   theme?: string,
     *   sidebar_collapsed?: bool,
     *   sidebar_pinned?: bool,
     *   dashboard_tab?: string
     * } $prefs
     */
    public function upsert(int $userId, array $prefs): void
    {
        $existing = $this->findByUserId($userId);
        $theme = $this->normalizeTheme($prefs['theme'] ?? $existing['theme'] ?? 'light');
        $collapsed = array_key_exists('sidebar_collapsed', $prefs)
            ? self::toBool($prefs['sidebar_collapsed'])
            : ($existing['sidebar_collapsed'] ?? false);
        $pinned = array_key_exists('sidebar_pinned', $prefs)
            ? self::toBool($prefs['sidebar_pinned'])
            : ($existing['sidebar_pinned'] ?? false);
        $dashTab = $this->normalizeDashboardTab($prefs['dashboard_tab'] ?? $existing['dashboard_tab'] ?? 'overview');

        $stmt = $this->db->prepare(
            'INSERT INTO user_preferences (user_id, theme, sidebar_collapsed, sidebar_pinned, dashboard_tab, updated_at)
             VALUES (:user_id, :theme, :sidebar_collapsed, :sidebar_pinned, :dashboard_tab, CURRENT_TIMESTAMP)
             ON CONFLICT (user_id) DO UPDATE SET
                theme = EXCLUDED.theme,
                sidebar_collapsed = EXCLUDED.sidebar_collapsed,
                sidebar_pinned = EXCLUDED.sidebar_pinned,
                dashboard_tab = EXCLUDED.dashboard_tab,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':theme', $theme, PDO::PARAM_STR);
        Database::bindBool($stmt, ':sidebar_collapsed', $collapsed);
        Database::bindBool($stmt, ':sidebar_pinned', $pinned);
        $stmt->bindValue(':dashboard_tab', $dashTab, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function normalizeTheme(string $theme): string
    {
        return $theme === 'dark' ? 'dark' : 'light';
    }

    private function normalizeDashboardTab(string $tab): string
    {
        $allowed = ['overview', 'comercial', 'financeiro', 'operacional', 'executivo'];

        return in_array($tab, $allowed, true) ? $tab : 'overview';
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value))
        {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 't', 'yes', 'on'], true);
    }
}
