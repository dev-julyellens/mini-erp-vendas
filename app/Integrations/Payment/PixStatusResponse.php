<?php

declare(strict_types=1);

namespace App\Integrations\Payment;

final class PixStatusResponse
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $status,
        public readonly ?string $paidAt = null,
        public readonly ?string $receiptReference = null,
    )
    {
    }
}
