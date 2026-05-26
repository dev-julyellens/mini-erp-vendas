<?php

declare(strict_types=1);

namespace App\Integrations\Payment\Gateways;

use App\Helpers\AppConfig;
use App\Helpers\PixConfig;
use App\Integrations\Payment\PaymentGatewayInterface;
use App\Integrations\Payment\PixChargeRequest;
use App\Integrations\Payment\PixChargeResponse;
use App\Integrations\Payment\PixStatusResponse;
use App\Integrations\Payment\PixWebhookResult;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Gateway simulado para desenvolvimento e testes de conciliação.
 */
final class MockPixGateway implements PaymentGatewayInterface
{
    /** @var array<string, array{status: string, paid_at: ?string, receipt: ?string}> */
    private static array $store = [];

    public function name(): string
    {
        return 'mock';
    }

    public function createCharge(PixChargeRequest $request): PixChargeResponse
    {
        $expiresAt = (new \DateTimeImmutable())
            ->modify('+' . $request->expiresInSeconds . ' seconds')
            ->format('Y-m-d H:i:s');

        $payload = $this->buildCopyPastePayload($request);
        $qrImageUrl = $this->buildPngDataUri($payload, 240);

        self::$store[$request->externalId] = [
            'status' => 'pending',
            'paid_at' => null,
            'receipt' => null,
        ];

        return new PixChargeResponse(
            $request->externalId,
            'pending',
            $payload,
            $qrImageUrl,
            $expiresAt,
        );
    }

    public function fetchStatus(string $externalId): PixStatusResponse
    {
        $entry = self::$store[$externalId] ?? ['status' => 'pending', 'paid_at' => null, 'receipt' => null];

        return new PixStatusResponse(
            $externalId,
            $entry['status'],
            $entry['paid_at'],
            $entry['receipt'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function parseWebhook(array $payload): PixWebhookResult
    {
        $externalId = trim((string) ($payload['external_id'] ?? $payload['txid'] ?? ''));
        $status = $this->normalizeStatus((string) ($payload['status'] ?? ''));
        $paidAt = isset($payload['paid_at']) ? (string) $payload['paid_at'] : null;
        $receipt = isset($payload['receipt_reference']) ? (string) $payload['receipt_reference'] : null;

        if ($externalId !== '' && isset(self::$store[$externalId]))
        {
            self::$store[$externalId] = [
                'status' => $status,
                'paid_at' => $paidAt ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'receipt' => $receipt ?? 'MOCK-' . strtoupper(substr($externalId, 0, 8)),
            ];
        }

        return new PixWebhookResult($externalId, $status, $paidAt, $receipt);
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $secret = PixConfig::webhookSecret();
        if ($secret === '')
        {
            return AppConfig::isDebug();
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return is_string($signatureHeader) && hash_equals($expected, $signatureHeader);
    }

    private function buildCopyPastePayload(PixChargeRequest $request): string
    {
        $merchant = PixConfig::merchantName();
        $city = PixConfig::merchantCity();

        return sprintf(
            '00020126580014BR.GOV.BCB.PIX0136%s520400005303986540%.2f5802BR5913%s6009%s62070503***6304MOCK',
            $request->externalId,
            (float) $request->amount,
            substr($merchant, 0, 13),
            substr($city, 0, 9),
        );
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status)
        {
            'paid', 'pago', 'concluida', 'concluída' => 'paid',
            'expired', 'expirado', 'expirada' => 'expired',
            'canceled', 'cancelado', 'cancelada' => 'canceled',
            default => 'pending',
        };
    }

    private function buildPngDataUri(string $payload, int $size): ?string
    {
        try
        {
            $qrCode = new QrCode(
                data: $payload,
                size: max(120, $size),
                margin: 0
            );

            $writer = new PngWriter();
            $png = $writer->write($qrCode)->getString();

            return 'data:image/png;base64,' . base64_encode($png);
        }
        catch (\Throwable $e)
        {
            return null;
        }
    }
}
