<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Flash;
use App\Core\Controller;
use App\Helpers\ProductPricing;
use App\Services\ProductService;
use App\Core\ValidationException;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;

final class ServiceController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'q' => (string) ($_GET['q'] ?? ''),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'type' => ProductPricing::TYPE_SERVICE,
            'low_stock' => false,
        ];

        $repo = new ProductRepository();
        $result = $repo->paginate($page, self::PER_PAGE, $filters);

        $this->view('services/index', [
            'services' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'categories' => (new CategoryRepository())->allOrderedByName(),
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
            Flash::success('Serviço criado com sucesso.');
            $this->redirect('/services');
        }
        catch (ValidationException $e)
        {
            $this->renderForm(null, $e->getErrors(), $_POST);
        }
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = $this->findServiceOrRedirect($id);
        if ($product === null)
        {
            return;
        }

        $this->renderForm($product, [], null);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $this->findServiceOrRedirect($id);
        if ($existing === null)
        {
            return;
        }

        $service = new ProductService();
        try
        {
            $service->update($id, $this->inputFromPost());
            Flash::success('Serviço atualizado com sucesso.');
            $this->redirect('/services');
        }
        catch (ValidationException $e)
        {
            $this->renderForm($existing, $e->getErrors(), $_POST);
        }
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = (new ProductRepository())->findById($id);
        if ($existing === null || !$existing->isService())
        {
            Flash::error('Serviço não encontrado.');
            $this->redirect('/services');
        }

        $service = new ProductService();
        try
        {
            $service->delete($id);
            Flash::success('Serviço removido.');
        }
        catch (ValidationException $e)
        {
            $msg = implode(' ', $e->getErrors());
            Flash::error($msg !== '' ? $msg : 'Não foi possível remover o serviço.');
        }
        catch (\PDOException $e)
        {
            Flash::error('Erro ao remover serviço.');
        }

        $this->redirect('/services');
    }

    private function findServiceOrRedirect(int $id): ?\App\Models\Product
    {
        $repo = new ProductRepository();
        $product = $repo->findById($id);
        if ($product === null || !$product->isService())
        {
            Flash::error('Serviço não encontrado.');
            $this->redirect('/services');

            return null;
        }

        return $product;
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $old
     */
    private function renderForm(?\App\Models\Product $service, array $errors, ?array $old): void
    {
        $this->view('services/form', [
            'service' => $service,
            'errors' => $errors,
            'old' => $old,
            'categories' => (new CategoryRepository())->allOrderedByName(),
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
            'unit_of_measure' => (string) ($_POST['unit_of_measure'] ?? 'HR'),
            'type' => ProductPricing::TYPE_SERVICE,
            'cost_price' => (string) ($_POST['cost_price'] ?? '0'),
            'price' => (string) ($_POST['price'] ?? ''),
            'min_stock' => '0',
            'stock' => '0',
            'estimated_time_minutes' => (string) ($_POST['estimated_time_minutes'] ?? ''),
        ];
    }
}
