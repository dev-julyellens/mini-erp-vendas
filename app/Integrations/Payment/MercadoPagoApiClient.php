<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

use RuntimeException;

/**
 * Cliente HTTP mínimo para a API de pagamentos do Mercado Pago (PIX).
 */
final class MercadoPagoApiClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct(
        private readonly string $accessToken,
    )
    {
        if ($this->accessToken === '')
        {
            throw new RuntimeException('Access token do Mercado Pago não configurado.');
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    public function createPayment(array $body, string $idempotencyKey): array
    {
        return $this->request('POST', '/v1/payments', $body, $idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->request('GET', '/v1/payments/' . rawurlencode($paymentId));
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
    {
        $url = self::BASE_URL . $path;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];

        if ($idempotencyKey !== null && $idempotencyKey !== '')
        {
            $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;
        }

        $ch = curl_init($url);
        if ($ch === false)
        {
            throw new RuntimeException('Não foi possível iniciar requisição ao Mercado Pago.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null && $method !== 'GET')
        {
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE);
            if ($encoded === false)
            {
                throw new RuntimeException('Falha ao serializar corpo da requisição.');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($raw))
        {
            throw new RuntimeException('Resposta vazia do Mercado Pago: ' . $error);
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($raw, true);
        if (!is_array($decoded))
        {
            throw new RuntimeException('Resposta inválida do Mercado Pago (HTTP ' . $status . ').');
        }

        if ($status < 200 || $status >= 300)
        {
            $message = (string) ($decoded['message'] ?? 'Erro na API do Mercado Pago.');
            throw new RuntimeException($message . ' (HTTP ' . $status . ')');
        }

        return $decoded;
    }
}
