<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\ProductRepository;
use App\Services\StockService;

final class StockMovementController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $productId = isset($_GET['product_id']) && $_GET['product_id'] !== ''
            ? (int) $_GET['product_id']
            : null;
        if ($productId !== null && $productId <= 0)
        {
            $productId = null;
        }

        $dateFrom = isset($_GET['date_from']) ? (string) $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? (string) $_GET['date_to'] : null;
        $type = isset($_GET['type']) ? (string) $_GET['type'] : null;

        $service = new StockService();
        $result = $service->searchMovements($productId, $dateFrom, $dateTo, $type, $page, self::PER_PAGE);

        $filters = [
            'product_id' => $productId,
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
            'type' => $type ?? '',
        ];

        $productRepo = new ProductRepository();

        $this->view('stock/index', [
            'movements' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'products' => $productRepo->allOrderedByName(),
            'filters' => $filters,
            'paginationQuery' => StockService::filterQueryParams($filters),
            'types' => StockService::TYPES,
            'typeLabels' => StockService::TYPE_LABELS,
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $productRepo = new ProductRepository();

        $this->view('stock/create', [
            'products' => array_values(array_filter(
                $productRepo->allOrderedByName(),
                static fn($p) => !$p->isService()
            )),
            'types' => StockService::TYPES,
            'typeLabels' => StockService::TYPE_LABELS,
            'errors' => [],
            'old' => [],
            'flash' => Flash::pull(),
        ]);
    }

    public function store(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $type = trim((string) ($_POST['type'] ?? ''));
        $quantityRaw = trim((string) ($_POST['quantity'] ?? ''));
        $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

        $errors = [];
        if ($productId <= 0)
        {
            $errors['product_id'] = 'Select a product.';
        }

        if (!is_numeric($quantityRaw) || (int) $quantityRaw != $quantityRaw)
        {
            $errors['quantity'] = 'A quantidade deve ser um número inteiro.';
        }

        $quantity = (int) $quantityRaw;
        $old = [
            'product_id' => (string) $productId,
            'type' => $type,
            'quantity' => $quantityRaw,
            'notes' => $notes ?? '',
        ];

        if ($errors !== [])
        {
            $productRepo = new ProductRepository();
            $this->view('stock/create', [
                'products' => $productRepo->allOrderedByName(),
                'types' => StockService::TYPES,
                'typeLabels' => StockService::TYPE_LABELS,
                'errors' => $errors,
                'old' => $old,
                'flash' => Flash::pull(),
            ]);

            return;
        }

        try
        {
            $service = new StockService();
            $service->registerManual($productId, $type, $quantity, $notes);
            Flash::success('Movimentação registrada com sucesso.');
            $this->redirect('stock-movements');
        }
        catch (ValidationException $e)
        {
            $productRepo = new ProductRepository();
            $this->view('stock/create', [
                'products' => $productRepo->allOrderedByName(),
                'types' => StockService::TYPES,
                'typeLabels' => StockService::TYPE_LABELS,
                'errors' => $e->getErrors(),
                'old' => $old,
                'flash' => Flash::pull(),
            ]);
        }
    }
}
