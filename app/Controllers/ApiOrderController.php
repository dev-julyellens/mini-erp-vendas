<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Services\OrderService;

final class ApiOrderController extends Controller
{
    public function index(): void
    {
        $repo = new OrderRepository();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));

        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
            ? (int) $_GET['customer_id']
            : null;
        $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

        $result = $repo->paginateFiltered(
            $page,
            $perPage,
            $customerId,
            $dateFrom !== '' ? $dateFrom : null,
            $dateTo !== '' ? $dateTo : null
        );

        $itemsRepo = new OrderItemRepository();
        $payload = [];
        foreach ($result['items'] as $order) {
            $lines = $itemsRepo->findByOrderId($order->id);
            $lineData = [];
            foreach ($lines as $line) {
                $lineData[] = [
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal' => $line->subtotal,
                ];
            }

            $payload[] = [
                'id' => $order->id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer_name,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
                'items' => $lineData,
            ];
        }

        $this->json([
            'data' => $payload,
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(): void
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            $this->json(['success' => false, 'message' => 'Empty body.'], 400);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->json(['success' => false, 'message' => 'Invalid JSON.'], 400);
        }

        if (!is_array($data)) {
            $this->json(['success' => false, 'message' => 'Invalid payload.'], 400);
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        $service = new OrderService();
        try {
            $orderId = $service->placeOrder($customerId, $items);
            $this->json(['success' => true, 'order_id' => $orderId], 201);
        } catch (ValidationException $e) {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Server error.'], 500);
        }
    }
}
