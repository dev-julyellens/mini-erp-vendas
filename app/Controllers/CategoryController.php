<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;

final class CategoryController extends Controller
{
    public function index(): void
    {
        $repo = new CategoryRepository();
        $this->view('categories/index', [
            'categories' => $repo->allOrderedByName(),
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->view('categories/form', [
            'category' => null,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $service = new CategoryService();
        try
        {
            $service->create(
                (string) ($_POST['name'] ?? ''),
                isset($_POST['description']) ? (string) $_POST['description'] : null
            );
            Flash::success('Categoria criada com sucesso.');
            $this->redirect('/categories');
        }
        catch (ValidationException $e)
        {
            $this->view('categories/form', [
                'category' => null,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new CategoryRepository();
        $category = $repo->findById($id);
        if ($category === null)
        {
            Flash::error('Categoria não encontrada.');
            $this->redirect('/categories');
        }

        $this->view('categories/form', [
            'category' => $category,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new CategoryService();
        try
        {
            $service->update(
                $id,
                (string) ($_POST['name'] ?? ''),
                isset($_POST['description']) ? (string) $_POST['description'] : null
            );
            Flash::success('Categoria atualizada com sucesso.');
            $this->redirect('/categories');
        }
        catch (ValidationException $e)
        {
            $repo = new CategoryRepository();
            $category = $repo->findById($id);
            $this->view('categories/form', [
                'category' => $category,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new CategoryService();
        try
        {
            $service->delete($id);
            Flash::success('Categoria removida.');
        }
        catch (ValidationException $e)
        {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Não foi possível remover a categoria.');
        }

        $this->redirect('/categories');
    }
}
