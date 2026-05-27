<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\AppConfig;
use App\Services\PixChargeService;

final class PixWebhookController extends Controller
{
    public function mock(): void
    {
        if (!AppConfig::allowsMockPixWebhook())
        {
            $this->json(['success' => false, 'message' => 'Webhook não disponível neste ambiente.'], 404);

            return;
        }

        $this->handle('mock');
    }

    public function mercadopago(): void
    {
        if (!\App\Helpers\PixConfig::isMercadoPagoConfigured())
        {
            $this->json(['success' => false, 'message' => 'Gateway Mercado Pago não configurado.'], 404);

            return;
        }

        $this->handle('mercadopago');
    }

    private function handle(string $gateway): void
    {
        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody))
        {
            $rawBody = '';
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload))
        {
            $payload = $_POST;
        }

        $signature = $_SERVER['HTTP_X_PIX_SIGNATURE']
            ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE']
            ?? $_SERVER['HTTP_X_SIGNATURE']
            ?? null;

        try
        {
            $service = new PixChargeService();
            $service->handleWebhook($gateway, $payload, $rawBody, is_string($signature) ? $signature : null);
            $this->json(['success' => true]);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
        catch (\Throwable $e)
        {
            $this->json(['success' => false, 'message' => 'Erro ao processar webhook.'], 500);
        }
    }
}
