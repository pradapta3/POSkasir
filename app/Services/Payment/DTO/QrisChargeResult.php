<?php

namespace App\Services\Payment\DTO;

use Carbon\CarbonImmutable;

final class QrisChargeResult
{
    public function __construct(
        public readonly string $referenceId,
        public readonly string $qrString,
        public readonly ?string $qrImageUrl,
        public readonly CarbonImmutable $expiresAt,
    ) {
    }
}
