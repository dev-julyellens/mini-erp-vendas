<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\ValidationException;
use App\Services\ApiPayloadService;
use App\Services\QuoteService;

final class ApiQuoteController extends Controller
{
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

        $validUntil = isset($data['valid_until']) && $data['valid_until'] !== null && $data['valid_until'] !== ''
            ? trim((string) $data['valid_until'])
            : null;
        $notes = isset($data['notes']) && $data['notes'] !== null && $data['notes'] !== ''
            ? trim((string) $data['notes'])
            : null;

        try
        {
            $quoteId = (new QuoteService())->create($customerId, $items, $validUntil, $notes);
            $this->json(['success' => true, 'quote_id' => $quoteId], 201);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
        catch (\Throwable $e)
        {
            Logger::exception($e, 'API: falha ao criar orçamento.', ['customer_id' => $customerId]);
            $this->json(['success' => false, 'message' => 'Erro interno do servidor.'], 500);
        }
    }
}
