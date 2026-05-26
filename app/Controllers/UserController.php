<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Services\PlatformAdminService;
use App\Services\UserService;

final class UserController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $this->assertAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = isset($_GET['q']) ? (string) $_GET['q'] : null;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

        $result = (new UserService())->list($page, self::PER_PAGE, $search, $status !== '' ? $status : null);

        $this->view('users/index', [
            'users' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'globalRoles' => UserService::GLOBAL_ROLES,
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->assertAdmin();
        $this->view('users/form', [
            'user' => null,
            'errors' => [],
            'globalRoles' => UserService::GLOBAL_ROLES,
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $this->assertAdmin();
        $service = new UserService();
        try
        {
            $service->create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['role'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['password_confirm'] ?? '')
            );
            Flash::success('Usuário cadastrado.');
            $this->redirect('/admin/users');
        }
        catch (ValidationException $e)
        {
            $this->view('users/form', [
                'user' => null,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'globalRoles' => UserService::GLOBAL_ROLES,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function edit(): void
    {
        $this->assertAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $user = (new UserService())->find($id);
        if ($user === null)
        {
            Flash::error('Usuário não encontrado.');
            $this->redirect('/admin/users');
        }

        $this->view('users/form', [
            'user' => $user,
            'errors' => [],
            'globalRoles' => UserService::GLOBAL_ROLES,
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $this->assertAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $service = new UserService();
        try
        {
            $service->update(
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['role'] ?? '')
            );
            Flash::success('Usuário atualizado.');
            $this->redirect('/admin/users');
        }
        catch (ValidationException $e)
        {
            $this->view('users/form', [
                'user' => $service->find($id),
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'globalRoles' => UserService::GLOBAL_ROLES,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function toggleActive(): void
    {
        $this->assertAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $active = ($_POST['active'] ?? '') === '1';
        try
        {
            (new UserService())->setActive($id, $active);
            Flash::success($active ? 'Usuário ativado.' : 'Usuário desativado.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirect('/admin/users');
    }

    public function resetPassword(): void
    {
        $this->assertAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $user = (new UserService())->find($id);
        if ($user === null)
        {
            Flash::error('Usuário não encontrado.');
            $this->redirect('/admin/users');
        }

        $this->view('users/reset-password', [
            'user' => $user,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function storeResetPassword(): void
    {
        $this->assertAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        try
        {
            (new UserService())->adminResetPassword(
                $id,
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['password_confirm'] ?? '')
            );
            Flash::success('Senha redefinida.');
            $this->redirect('/admin/users');
        }
        catch (ValidationException $e)
        {
            $user = (new UserService())->find($id);
            $this->view('users/reset-password', [
                'user' => $user,
                'errors' => $e->getErrors(),
                'flash' => Flash::pull(),
            ]);
        }
    }

    private function assertAdmin(): void
    {
        try
        {
            (new PlatformAdminService())->assertPlatformAdmin();
        }
        catch (ValidationException $e)
        {
            Flash::error($e->getErrors()['access'] ?? 'Acesso negado.');
            $this->redirect('/');
        }
    }
}
