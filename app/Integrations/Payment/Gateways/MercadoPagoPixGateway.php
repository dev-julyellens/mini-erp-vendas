<?php

declare(strict_types=1);

namespace App\Integrations\Payment\Gateways;

use App\Core\Logger;
use App\Helpers\PixConfig;
use App\Integrations\Payment\MercadoPagoApiClient;
use App\Integrations\Payment\PaymentGatewayInterface;
use App\Integrations\Payment\PixChargeRequest;
use App\Integrations\Payment\PixChargeResponse;
use App\Integrations\Payment\PixStatusResponse;
use App\Integrations\Payment\PixWebhookResult;

/**
 * Gateway PIX via API Mercado Pago (Checkout Transparente / payments).
 *
 * @see https://www.mercadopago.com.br/developers/pt/docs/checkout-api-payments/integration-configuration/integrate-with-pix
 */
final class MercadoPagoPixGateway implements PaymentGatewayInterface
{
    private MercadoPagoApiClient $client;

    public function __construct(?MercadoPagoApiClient $client = null)
    {
        $this->client = $client ?? new MercadoPagoApiClient(PixConfig::mercadoPagoAccessToken());
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function createCharge(PixChargeRequest $request): PixChargeResponse
    {
        $expiresAt = (new \DateTimeImmutable('now'))
            ->modify('+' . $request->expiresInSeconds . ' seconds');

        $body = [
            'transaction_amount' => round((float) $request->amount, 2),
            'description' => mb_substr($request->description, 0, 255),
            'payment_method_id' => 'pix',
            'external_reference' => $request->externalId,
            'date_of_expiration' => $expiresAt->format('Y-m-d\TH:i:s.000P'),
            'payer' => [
                'email' => PixConfig::mercadoPagoPayerEmail(),
            ],
        ];

        if ($request->payerName !== null && $request->payerName !== '')
        {
            $parts = preg_split('/\s+/', trim($request->payerName), 2);
            $body['payer']['first_name'] = $parts[0] ?? 'Cliente';
            if (!empty($parts[1]))
            {
                $body['payer']['last_name'] = $parts[1];
            }
        }

        $payment = $this->client->createPayment($body, $request->externalId);
        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '')
        {
            throw new \RuntimeException('Mercado Pago não retornou ID do pagamento.');
        }

        $transactionData = $payment['point_of_interaction']['transaction_data'] ?? [];
        if (!is_array($transactionData))
        {
            $transactionData = [];
        }

        $qrPayload = (string) ($transactionData['qr_code'] ?? '');
        $qrBase64 = (string) ($transactionData['qr_code_base64'] ?? '');
        $qrImageUrl = $qrBase64 !== '' ? 'data:image/png;base64,' . $qrBase64 : null;

        $status = self::mapPaymentStatus((string) ($payment['status'] ?? 'pending'));

        return new PixChargeResponse(
            $paymentId,
            $status,
            $qrPayload,
            $qrImageUrl,
            $expiresAt->format('Y-m-d H:i:s'),
        );
    }

    public function fetchStatus(string $externalId): PixStatusResponse
    {
        $payment = $this->client->getPayment($externalId);
        $status = self::mapPaymentStatus((string) ($payment['status'] ?? 'pending'));
        $paidAt = null;
        if ($status === 'paid')
        {
            $paidAt = (string) ($payment['date_approved'] ?? $payment['date_last_updated'] ?? '');
            if ($paidAt === '')
            {
                $paidAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            }
        }

        $receipt = null;
        if ($status === 'paid')
        {
            $txData = $payment['point_of_interaction']['transaction_data'] ?? [];
            if (is_array($txData))
            {
                $receipt = (string) ($txData['transaction_id'] ?? $payment['id'] ?? '');
            }
        }

        return new PixStatusResponse($externalId, $status, $paidAt, $receipt !== '' ? $receipt : null);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function parseWebhook(array $payload): PixWebhookResult
    {
        $paymentId = '';
        if (isset($payload['data']) && is_array($payload['data']))
        {
            $paymentId = (string) ($payload['data']['id'] ?? '');
        }
        if ($paymentId === '')
        {
            $paymentId = (string) ($payload['id'] ?? '');
        }

        if ($paymentId === '')
        {
            return new PixWebhookResult('', 'pending', null, null);
        }

        $remote = $this->fetchStatus($paymentId);

        return new PixWebhookResult(
            $remote->externalId,
            $remote->status,
            $remote->paidAt,
            $remote->receiptReference,
        );
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = PixConfig::mercadoPagoWebhookSecret();
        if ($secret === '')
        {
            Logger::warning('Webhook Mercado Pago sem PIX_MERCADOPAGO_WEBHOOK_SECRET; confiança apenas no corpo JSON.');

            return $rawBody !== '' && json_decode($rawBody, true) !== null;
        }

        if (!is_string($signatureHeader) || $signatureHeader === '')
        {
            return false;
        }

        $dataId = '';
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded))
        {
            if (isset($decoded['data']['id']))
            {
                $dataId = (string) $decoded['data']['id'];
            }
            elseif (isset($decoded['id']))
            {
                $dataId = (string) $decoded['id'];
            }
        }

        $requestId = (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? '');
        $ts = '';
        $providedHash = '';

        foreach (explode(',', $signatureHeader) as $part)
        {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2)
            {
                continue;
            }
            if ($kv[0] === 'ts')
            {
                $ts = $kv[1];
            }
            elseif ($kv[0] === 'v1')
            {
                $providedHash = $kv[1];
            }
        }

        if ($ts === '' || $providedHash === '')
        {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $requestId, $ts);
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $providedHash);
    }

    public static function mapPaymentStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status)
        {
            'approved' => 'paid',
            'cancelled', 'canceled' => 'canceled',
            'rejected', 'expired' => 'expired',
            default => 'pending',
        };
    }
}
