<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

final class PixChargeResponse
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $status,
        public readonly string $qrPayload,
        public readonly ?string $qrImageUrl,
        public readonly string $expiresAt,
        public readonly ?string $receiptReference = null,
    )
    {
    }
}
