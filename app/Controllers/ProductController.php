<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\ProductRepository;
use App\Services\ProductService;

final class ProductController extends Controller
{
    private const PER_PAGE = 8;
    private const LOW_STOCK = 5;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $repo = new ProductRepository();
        $result = $repo->paginate($page, self::PER_PAGE);

        $this->view('products/index', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'lowStockThreshold' => self::LOW_STOCK,
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->view('products/form', [
            'product' => null,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $service = new ProductService();
        try {
            $service->create(
                (string) ($_POST['name'] ?? ''),
                isset($_POST['description']) ? (string) $_POST['description'] : null,
                (string) ($_POST['price'] ?? ''),
                (string) ($_POST['stock'] ?? '')
            );
            Flash::success('Product created successfully.');
            $this->redirect('/products');
        } catch (ValidationException $e) {
            $this->view('products/form', [
                'product' => null,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new ProductRepository();
        $product = $repo->findById($id);
        if ($product === null) {
            Flash::error('Product not found.');
            $this->redirect('/products');
        }

        $this->view('products/form', [
            'product' => $product,
            'errors' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new ProductService();
        try {
            $service->update(
                $id,
                (string) ($_POST['name'] ?? ''),
                isset($_POST['description']) ? (string) $_POST['description'] : null,
                (string) ($_POST['price'] ?? ''),
                (string) ($_POST['stock'] ?? '')
            );
            Flash::success('Product updated successfully.');
            $this->redirect('/products');
        } catch (ValidationException $e) {
            $repo = new ProductRepository();
            $product = $repo->findById($id);
            $this->view('products/form', [
                'product' => $product,
                'errors' => $e->getErrors(),
                'old' => $_POST,
                'flash' => Flash::pull(),
            ]);
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new ProductService();
        try {
            $service->delete($id);
            Flash::success('Product removed.');
        } catch (ValidationException $e) {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Unable to remove product.');
        } catch (\PDOException $e) {
            Flash::error('Database error while removing product.');
        }

        $this->redirect('/products');
    }
}
