<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Helpers\DateFilter;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use App\Services\ApiPayloadService;
use App\Services\OrderCancelService;
use App\Services\OrderService;

final class ApiOrderController extends Controller
{
    public function index(): void
    {
        try
        {
            $repo = new OrderRepository();
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));

            $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== ''
                ? (int) $_GET['customer_id']
                : null;

            $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
            $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

            $dateErrors = array_merge(
                DateFilter::validateOptionalIsoDate($dateFrom, 'date_from'),
                DateFilter::validateOptionalIsoDate($dateTo, 'date_to')
            );
            if ($dateErrors !== [])
            {
                $this->json(['success' => false, 'errors' => $dateErrors], 422);

                return;
            }

            if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo)
            {
                $this->json([
                    'success' => false,
                    'errors' => ['date_to' => 'A data final deve ser igual ou posterior à data inicial.'],
                ], 422);

                return;
            }

            $result = $repo->paginateFiltered(
                $page,
                $perPage,
                $customerId,
                $dateFrom !== '' ? $dateFrom : null,
                $dateTo !== '' ? $dateTo : null
            );

            $orderIds = array_map(static fn($order) => $order->id, $result['items']);
            $itemsByOrder = (new OrderItemRepository())->findByOrderIds($orderIds);

            $payload = [];
            foreach ($result['items'] as $order)
            {
                $lines = $itemsByOrder[$order->id] ?? [];
                $lineData = [];
                foreach ($lines as $line)
                {
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
                    'status' => $order->status,
                    'canceled_by' => $order->canceled_by,
                    'canceled_at' => $order->canceled_at,
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
        catch (\Throwable $e)
        {
            Logger::exception($e, 'API: falha ao listar pedidos.');
            $this->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }

    public function cancel(): void
    {
        $payloadService = new ApiPayloadService();

        try
        {
            $data = $payloadService->requireJsonObject();
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);

            return;
        }

        $orderId = (int) ($data['order_id'] ?? $data['id'] ?? 0);
        if ($orderId <= 0)
        {
            $this->json(['success' => false, 'message' => 'ID do pedido inválido.'], 422);

            return;
        }

        $service = new OrderCancelService();
        try
        {
            $service->cancel($orderId);
            $this->json(['success' => true, 'order_id' => $orderId, 'status' => 'canceled']);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'API: falha ao cancelar pedido.', ['order_id' => $orderId]);
            $this->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }

    public function store(): void
    {
        $payloadService = new ApiPayloadService();

        try
        {
            $data = $payloadService->requireJsonObject();
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);

            return;
        }

        $errors = $payloadService->validateRequired($data, ['customer_id', 'items']);
        if ($errors !== [])
        {
            $this->json(['success' => false, 'errors' => $errors], 422);

            return;
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $items = $data['items'] ?? [];
        if (!is_array($items))
        {
            $this->json(['success' => false, 'errors' => ['items' => 'Campo inválido.']], 422);

            return;
        }
        $installmentCount = \App\Services\InstallmentService::normalizeInstallmentCount(
            $data['installment_count'] ?? 1
        );

        $service = new OrderService();
        try
        {
            $orderId = $service->placeOrder($customerId, $items, $installmentCount);
            $this->json(['success' => true, 'order_id' => $orderId], 201);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'API: falha ao criar pedido.', ['customer_id' => $customerId]);
            $this->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }
}
