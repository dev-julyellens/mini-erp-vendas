<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Auth;
use App\Helpers\Flash;
use App\Repositories\UserCompanyRepository;
use App\Services\PasswordPolicyService;
use App\Services\PermissionService;
use App\Services\ProfileService;
use App\Services\TenantContextService;
use App\Services\UserService;

final class ProfileController extends Controller
{
    public function show(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            $this->redirect('/login');
        }

        $userId = (int) $user->id;
        $snapshot = Auth::sessionSnapshot();
        $effectiveRole = (new TenantContextService())->resolveEffectiveAclRole();
        $permissionKeys = (new PermissionService())->permissionKeysForRole($effectiveRole);
        $profileService = new ProfileService();

        $this->view('profile/show', [
            'user' => $user,
            'userPrefs' => $profileService->preferencesForUser($userId),
            'hasAvatar' => $profileService->avatarAbsolutePath($user) !== null,
            'companyName' => $snapshot['company_name'] ?? null,
            'companyRole' => $snapshot['company_role'] ?? null,
            'companyBindings' => (new UserCompanyRepository())->listBindingsForUser($userId),
            'effectiveRole' => $effectiveRole,
            'permissionKeys' => $permissionKeys,
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $userId = Auth::id();
        if ($userId === null)
        {
            $this->redirect('/login');
        }

        try
        {
            (new UserService())->updateOwnProfile(
                $userId,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? '')
            );
            Flash::success('Perfil atualizado.');
            $this->redirect('/profile');
        }
        catch (ValidationException $e)
        {
            $this->renderShowWithErrors($userId, $e->getErrors(), $_POST);
        }
    }

    public function updatePreferences(): void
    {
        $userId = Auth::id();
        if ($userId === null)
        {
            $this->json(['success' => false, 'message' => 'Não autenticado.'], 401);

            return;
        }

        $input = $_POST;
        if (str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json'))
        {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded))
            {
                $input = $decoded;
            }
        }

        try
        {
            $prefs = (new ProfileService())->savePreferences($userId, $input);
            $this->json(['success' => true, 'data' => $prefs]);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
    }

    public function uploadAvatar(): void
    {
        $userId = Auth::id();
        if ($userId === null)
        {
            $this->redirect('/login');
        }

        try
        {
            (new ProfileService())->uploadAvatar($userId);
            Flash::success('Foto de perfil atualizada.');
        }
        catch (ValidationException $e)
        {
            foreach ($e->getErrors() as $message)
            {
                Flash::error((string) $message);
                break;
            }
        }

        $this->redirect('/profile');
    }

    public function removeAvatar(): void
    {
        $userId = Auth::id();
        if ($userId === null)
        {
            $this->redirect('/login');
        }

        (new ProfileService())->removeAvatar($userId);
        Flash::success('Foto de perfil removida.');
        $this->redirect('/profile');
    }

    public function avatar(): void
    {
        $user = Auth::user();
        if ($user === null)
        {
            http_response_code(404);
            exit;
        }

        $path = (new ProfileService())->avatarAbsolutePath($user);
        if ($path === null)
        {
            http_response_code(404);
            exit;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION)))
        {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    public function password(): void
    {
        $this->view('profile/password', [
            'errors' => [],
            'hint' => (new PasswordPolicyService())->requirementsHint(),
            'flash' => Flash::pull(),
        ]);
    }

    public function updatePassword(): void
    {
        $userId = Auth::id();
        if ($userId === null)
        {
            $this->redirect('/login');
        }

        try
        {
            (new UserService())->changeOwnPassword(
                $userId,
                (string) ($_POST['current_password'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['password_confirm'] ?? '')
            );
            Flash::success('Senha alterada.');
            $this->redirect('/profile');
        }
        catch (ValidationException $e)
        {
            $this->view('profile/password', [
                'errors' => $e->getErrors(),
                'hint' => (new PasswordPolicyService())->requirementsHint(),
                'flash' => Flash::pull(),
            ]);
        }
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    private function renderShowWithErrors(int $userId, array $errors, array $old): void
    {
        $user = Auth::user();
        $snapshot = Auth::sessionSnapshot();
        $effectiveRole = (new TenantContextService())->resolveEffectiveAclRole();
        $profileService = new ProfileService();

        $this->view('profile/show', [
            'user' => $user,
            'userPrefs' => $profileService->preferencesForUser($userId),
            'hasAvatar' => $user !== null && $profileService->avatarAbsolutePath($user) !== null,
            'companyName' => $snapshot['company_name'] ?? null,
            'companyRole' => $snapshot['company_role'] ?? null,
            'companyBindings' => (new UserCompanyRepository())->listBindingsForUser($userId),
            'effectiveRole' => $effectiveRole,
            'permissionKeys' => (new PermissionService())->permissionKeysForRole($effectiveRole),
            'errors' => $errors,
            'old' => $old,
            'flash' => Flash::pull(),
        ]);
    }
}
