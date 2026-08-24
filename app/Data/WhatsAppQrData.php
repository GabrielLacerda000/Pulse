<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class WhatsAppQrData extends Data
{
    public function __construct(
        public readonly ?string $qr = null,
    ) {}
}
