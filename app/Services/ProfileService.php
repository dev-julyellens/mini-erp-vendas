<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Helpers\Audit;
use App\Models\User;
use App\Repositories\UserPreferenceRepository;
use App\Repositories\UserRepository;

final class ProfileService
{
    /** @var list<string> */
    public const DASHBOARD_TABS = ['overview', 'comercial', 'financeiro', 'operacional', 'executivo'];

    private UserRepository $users;

    private UserPreferenceRepository $preferences;

    private AvatarStorageService $avatars;

    public function __construct(
        ?UserRepository $users = null,
        ?UserPreferenceRepository $preferences = null,
        ?AvatarStorageService $avatars = null
    )
    {
        $this->users = $users ?? new UserRepository();
        $this->preferences = $preferences ?? new UserPreferenceRepository();
        $this->avatars = $avatars ?? new AvatarStorageService();
    }

    /**
     * @return array{
     *   theme: string,
     *   sidebar_collapsed: bool,
     *   sidebar_pinned: bool,
     *   dashboard_tab: string
     * }
     */
    public function preferencesForUser(int $userId): array
    {
        $stored = $this->preferences->findByUserId($userId);
        if ($stored !== null)
        {
            return $stored;
        }

        return [
            'theme' => 'light',
            'sidebar_collapsed' => false,
            'sidebar_pinned' => false,
            'dashboard_tab' => 'overview',
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *   theme: string,
     *   sidebar_collapsed: bool,
     *   sidebar_pinned: bool,
     *   dashboard_tab: string
     * }
     */
    public function savePreferences(int $userId, array $input): array
    {
        $current = $this->preferencesForUser($userId);
        $theme = isset($input['theme']) ? (string) $input['theme'] : $current['theme'];
        $collapsed = array_key_exists('sidebar_collapsed', $input)
            ? self::toBool($input['sidebar_collapsed'])
            : $current['sidebar_collapsed'];
        $pinned = array_key_exists('sidebar_pinned', $input)
            ? self::toBool($input['sidebar_pinned'])
            : $current['sidebar_pinned'];
        $dashTab = isset($input['dashboard_tab']) ? (string) $input['dashboard_tab'] : $current['dashboard_tab'];

        if ($pinned)
        {
            $collapsed = false;
        }

        $prefs = [
            'theme' => $theme === 'dark' ? 'dark' : 'light',
            'sidebar_collapsed' => $collapsed,
            'sidebar_pinned' => $pinned,
            'dashboard_tab' => in_array($dashTab, self::DASHBOARD_TABS, true) ? $dashTab : 'overview',
        ];

        $this->preferences->upsert($userId, $prefs);
        Audit::record('editar', 'usuarios', $userId, null, ['preferences' => $prefs], $userId);

        return $prefs;
    }

    public function uploadAvatar(int $userId, string $fieldName = 'avatar'): User
    {
        $user = $this->users->findById($userId);
        if ($user === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        $relative = $this->avatars->store($userId, $fieldName);
        $this->avatars->deleteIfExists($user->avatar_path);
        $this->users->updateAvatarPath($userId, $relative);
        Audit::record('editar', 'usuarios', $userId, null, ['avatar_path' => $relative], $userId);

        $fresh = $this->users->findById($userId);
        if ($fresh === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        return $fresh;
    }

    public function removeAvatar(int $userId): void
    {
        $user = $this->users->findById($userId);
        if ($user === null)
        {
            throw new ValidationException(['id' => 'Usuário não encontrado.']);
        }

        $this->avatars->deleteIfExists($user->avatar_path);
        $this->users->updateAvatarPath($userId, null);
        Audit::record('editar', 'usuarios', $userId, null, ['avatar_path' => null], $userId);
    }

    public function avatarAbsolutePath(User $user): ?string
    {
        return $this->avatars->absolutePath($user->avatar_path);
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value))
        {
            return $value;
        }

        if (is_int($value) || is_float($value))
        {
            return (bool) $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 't', 'yes', 'on'], true);
    }
}
