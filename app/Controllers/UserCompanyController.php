<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\CompanyRepository;
use App\Services\CompanyRoleService;
use App\Services\PlatformAdminService;
use App\Services\UserCompanyService;

final class UserCompanyController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        if (!$this->canAccess())
        {
            Flash::error('Sem permissão para gerenciar vínculos.');
            $this->redirect('/');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = isset($_GET['q']) ? (string) $_GET['q'] : null;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
        $role = isset($_GET['role']) ? (string) $_GET['role'] : null;
        $companyId = (int) ($_GET['company_id'] ?? 0);
        if (!(new PlatformAdminService())->isPlatformAdmin())
        {
            $companyId = 0;
        }

        $service = new UserCompanyService();
        $result = $service->list(
            $page,
            self::PER_PAGE,
            $search,
            $role !== '' ? $role : null,
            $status !== '' ? $status : null,
            $companyId > 0 ? $companyId : null
        );

        $this->view('user-companies/index', [
            'links' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'roleFilter' => $role ?? '',
            'companyRoles' => CompanyRoleService::ROLES,
            'companies' => (new PlatformAdminService())->isPlatformAdmin()
                ? (new CompanyRepository())->listForSelect()
                : [],
            'companyId' => $companyId,
            'isPlatformAdmin' => (new PlatformAdminService())->isPlatformAdmin(),
            'flash' => Flash::pull(),
        ]);
    }

    public function attach(): void
    {
        if (!$this->canAccess())
        {
            Flash::error('Sem permissão.');
            $this->redirect('/user-companies');
        }

        try
        {
            (new UserCompanyService())->attach(
                (int) ($_POST['user_id'] ?? 0),
                (int) ($_POST['company_id'] ?? 0),
                (string) ($_POST['role'] ?? '')
            );
            Flash::success('Vínculo criado.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirectBack();
    }

    public function updateRole(): void
    {
        if (!$this->canAccess())
        {
            Flash::error('Sem permissão.');
            $this->redirect('/user-companies');
        }

        try
        {
            (new UserCompanyService())->updateRole(
                (int) ($_POST['user_id'] ?? 0),
                (int) ($_POST['company_id'] ?? 0),
                (string) ($_POST['role'] ?? '')
            );
            Flash::success('Papel atualizado.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirectBack();
    }

    public function toggleActive(): void
    {
        if (!$this->canAccess())
        {
            Flash::error('Sem permissão.');
            $this->redirect('/user-companies');
        }

        try
        {
            (new UserCompanyService())->setLinkActive(
                (int) ($_POST['user_id'] ?? 0),
                (int) ($_POST['company_id'] ?? 0),
                ($_POST['active'] ?? '') === '1'
            );
            Flash::success('Status do vínculo atualizado.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirectBack();
    }

    public function detach(): void
    {
        if (!$this->canAccess())
        {
            Flash::error('Sem permissão.');
            $this->redirect('/user-companies');
        }

        try
        {
            (new UserCompanyService())->detach(
                (int) ($_POST['user_id'] ?? 0),
                (int) ($_POST['company_id'] ?? 0)
            );
            Flash::success('Vínculo removido.');
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
        }

        $this->redirectBack();
    }

    private function canAccess(): bool
    {
        if ((new PlatformAdminService())->isPlatformAdmin())
        {
            return true;
        }

        return \App\Helpers\Permission::can('usuarios', 'editar');
    }

    private function redirectBack(): void
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $target = '/user-companies' . ($query !== '' ? '?' . $query : '');
        $this->redirect($target);
    }
}
