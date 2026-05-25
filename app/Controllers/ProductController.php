<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Helpers\ProductPricing;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Services\ProductService;

final class ProductController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'type' => (string) ($_GET['type'] ?? ''),
            'low_stock' => isset($_GET['low_stock']) && $_GET['low_stock'] === '1',
        ];

        $repo = new ProductRepository();
        $result = $repo->paginate($page, self::PER_PAGE, $filters);

        $this->view('products/index', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'categories' => (new CategoryRepository())->allOrderedByName(),
            'typeOptions' => [
                ProductPricing::TYPE_PRODUCT => 'Produto',
                ProductPricing::TYPE_SERVICE => 'Serviço',
            ],
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm(null, [], null);
    }

    public function store(): void
    {
        $service = new ProductService();
        try
        {
            $service->create($this->inputFromPost());
            Flash::success('Produto criado com sucesso.');
            $this->redirect('/products');
        }
        catch (ValidationException $e)
        {
            $this->renderForm(null, $e->getErrors(), $_POST);
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new ProductRepository();
        $product = $repo->findById($id);
        if ($product === null)
        {
            Flash::error('Produto não encontrado.');
            $this->redirect('/products');
        }

        $this->renderForm($product, [], null);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new ProductService();
        try
        {
            $service->update($id, $this->inputFromPost());
            Flash::success('Produto atualizado com sucesso.');
            $this->redirect('/products');
        }
        catch (ValidationException $e)
        {
            $repo = new ProductRepository();
            $product = $repo->findById($id);
            $this->renderForm($product, $e->getErrors(), $_POST);
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $service = new ProductService();
        try
        {
            $service->delete($id);
            Flash::success('Produto removido.');
        }
        catch (ValidationException $e)
        {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Não foi possível remover o produto.');
        }
        catch (\PDOException)
        {
            Flash::error('Erro ao remover produto.');
        }

        $this->redirect('/products');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $old
     */
    private function renderForm(?\App\Models\Product $product, array $errors, ?array $old): void
    {
        $this->view('products/form', [
            'product' => $product,
            'errors' => $errors,
            'old' => $old,
            'categories' => (new CategoryRepository())->allOrderedByName(),
            'units' => ProductPricing::UNITS,
            'flash' => Flash::pull(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function inputFromPost(): array
    {
        return [
            'name' => (string) ($_POST['name'] ?? ''),
            'description' => (string) ($_POST['description'] ?? ''),
            'sku' => (string) ($_POST['sku'] ?? ''),
            'barcode' => (string) ($_POST['barcode'] ?? ''),
            'category_id' => (string) ($_POST['category_id'] ?? ''),
            'unit_of_measure' => (string) ($_POST['unit_of_measure'] ?? 'UN'),
            'type' => (string) ($_POST['type'] ?? ProductPricing::TYPE_PRODUCT),
            'cost_price' => (string) ($_POST['cost_price'] ?? '0'),
            'price' => (string) ($_POST['price'] ?? ''),
            'min_stock' => (string) ($_POST['min_stock'] ?? '0'),
            'stock' => (string) ($_POST['stock'] ?? '0'),
        ];
    }
}
