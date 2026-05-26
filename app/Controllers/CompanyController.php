<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Services\CompanyService;
use App\Services\PlatformAdminService;

final class CompanyController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $this->assertAdmin();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = isset($_GET['q']) ? (string) $_GET['q'] : null;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : null;

        $service = new CompanyService();
        $result = $service->list($page, self::PER_PAGE, $search, $status !== '' ? $status : null);

        $this->view('companies/index', [
            'companies' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->assertAdmin();
        $this->view('companies/form', [
            'company' => null,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $this->assertAdmin();
        $service = new CompanyService();
        try
        {
            $service->create(
                (string) ($_POST['name'] ?? ''),
                isset($_POST['tax_id']) ? (string) $_POST['tax_id'] : null,
                (string) ($_POST['slug'] ?? '')
            );
            Flash::success('Empresa cadastrada com sucesso.');
            $this->redirect('/admin/companies');
        }
        catch (ValidationException $e)
        {
            $this->view('companies/form', [
                'company' => null,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function edit(): void
    {
        $this->assertAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $company = (new CompanyService())->find($id);
        if ($company === null)
        {
            Flash::error('Empresa não encontrada.');
            $this->redirect('/admin/companies');
        }

        $this->view('companies/form', [
            'company' => $company,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $this->assertAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $service = new CompanyService();
        try
        {
            $service->update(
                $id,
                (string) ($_POST['name'] ?? ''),
                isset($_POST['tax_id']) ? (string) $_POST['tax_id'] : null,
                (string) ($_POST['slug'] ?? '')
            );
            Flash::success('Empresa atualizada.');
            $this->redirect('/admin/companies');
        }
        catch (ValidationException $e)
        {
            $this->view('companies/form', [
                'company' => $service->find($id),
                'errors' => $e->getErrors(),
                'old' => $_POST,
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
            (new CompanyService())->setActive($id, $active);
            Flash::success($active ? 'Empresa ativada.' : 'Empresa desativada.');
        }
        catch (ValidationException $e)
        {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Não foi possível alterar o status.');
        }

        $this->redirect('/admin/companies');
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
