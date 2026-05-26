<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

final class PixChargeRequest
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $amount,
        public readonly string $description,
        public readonly int $expiresInSeconds,
        public readonly ?string $payerName = null,
        public readonly ?string $payerDocument = null,
    )
    {
    }
}
