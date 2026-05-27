<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\OrderCancelService;
use App\Services\OrderService;

final class OrderController extends Controller
{
    private const PER_PAGE = 10;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
            ? (int) $_GET['customer_id']
            : null;
        $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

        $repo = new OrderRepository();
        $result = $repo->paginateFiltered(
            $page,
            self::PER_PAGE,
            $customerId,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $customers = new CustomerRepository();

        $this->view('orders/index', [
            'orders' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'customers' => $customers->allOrderedByName(),
            'filters' => [
                'customer_id' => $customerId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'flash' => Flash::pull(),
        ]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $orders = new OrderRepository();
        $order = $orders->findById($id);
        if ($order === null)
        {
            Flash::error('Order not found.');
            $this->redirect('/orders');
        }

        $items = new OrderItemRepository();
        $installmentService = new \App\Services\InstallmentService();
        $this->view('orders/show', [
            'order' => $order,
            'items' => $items->findByOrderId($id),
            'installments' => $installmentService->findByOrderId($id),
            'flash' => Flash::pull(),
        ]);
    }

    public function create(): void
    {
        $customers = new CustomerRepository();
        $products = new ProductRepository();

        $this->view('orders/create', [
            'customers' => $customers->allOrderedByName(),
            'products' => $products->allOrderedByName(),
            'flash' => Flash::pull(),
            'pageScripts' => [
                'assets/js/autosave.js',
                'assets/js/order_create.js',
            ],
        ]);
    }

    public function cancel(): void
    {
        $orderId = (int) ($_POST['id'] ?? 0);
        if ($orderId <= 0)
        {
            Flash::error('Venda inválida.');
            $this->redirect('/orders');
        }

        $service = new OrderCancelService();

        try
        {
            $service->cancel($orderId);
            Flash::success('Venda #' . $orderId . ' cancelada. Estoque restaurado.');
            $this->redirect('/orders/show?id=' . $orderId);
        }
        catch (ValidationException $e)
        {
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/orders/show?id=' . $orderId);
        }
        catch (\Throwable $e)
        {
            Flash::error('Erro inesperado ao cancelar a venda.');
            $this->redirect('/orders/show?id=' . $orderId);
        }
    }

    public function store(): void
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $isJson = strpos($contentType, 'application/json') !== false;

        $payload = $isJson
            ? $this->readJsonBody()
            : $_POST;

        $customerId = (int) ($payload['customer_id'] ?? 0);
        $items = $payload['items'] ?? [];
        if (!is_array($items))
        {
            $items = [];
        }
        $installmentCount = \App\Services\InstallmentService::normalizeInstallmentCount(
            $payload['installment_count'] ?? 1
        );

        $service = new OrderService();

        try
        {
            $orderId = $service->placeOrder($customerId, $items, $installmentCount);
            if ($isJson)
            {
                $this->json(['success' => true, 'order_id' => $orderId, 'message' => 'Sale registered successfully.']);
            }
            Flash::success('Sale #' . $orderId . ' registered successfully.');
            $this->redirect('/orders/show?id=' . $orderId);
        }
        catch (ValidationException $e)
        {
            if ($isJson)
            {
                $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
            }
            Flash::error(implode(' ', $e->getErrors()));
            $this->redirect('/orders/create');
        }
        catch (\Throwable $e)
        {
            if ($isJson)
            {
                $this->json(['success' => false, 'message' => 'Unexpected error while saving the sale.'], 500);
            }
            Flash::error('Unexpected error while saving the sale.');
            $this->redirect('/orders/create');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '')
        {
            return [];
        }

        try
        {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        }
        catch (\JsonException $e)
        {
            return [];
        }
    }
}
