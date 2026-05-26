<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

interface PaymentGatewayInterface
{
    public function name(): string;

    /**
     * Cria cobrança PIX no provedor.
     */
    public function createCharge(PixChargeRequest $request): PixChargeResponse;

    /**
     * Consulta status atual no provedor.
     */
    public function fetchStatus(string $externalId): PixStatusResponse;

    /**
     * Interpreta payload do webhook e retorna status normalizado.
     *
     * @param array<string, mixed> $payload
     */
    public function parseWebhook(array $payload): PixWebhookResult;

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool;
}
