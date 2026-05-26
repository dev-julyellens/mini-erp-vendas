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

        $this->view('profile/show', [
            'user' => $user,
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
            $user = Auth::user();
            $snapshot = Auth::sessionSnapshot();
            $effectiveRole = (new TenantContextService())->resolveEffectiveAclRole();
            $this->view('profile/show', [
                'user' => $user,
                'companyName' => $snapshot['company_name'] ?? null,
                'companyRole' => $snapshot['company_role'] ?? null,
                'companyBindings' => (new UserCompanyRepository())->listBindingsForUser($userId),
                'effectiveRole' => $effectiveRole,
                'permissionKeys' => (new PermissionService())->permissionKeysForRole($effectiveRole),
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
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
}
