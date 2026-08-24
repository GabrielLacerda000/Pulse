<?php

namespace App\Data;

use App\Enums\WhatsAppConnectionStatus;
use Spatie\LaravelData\Data;

final class WhatsAppStatusData extends Data
{
    public function __construct(
        public readonly WhatsAppConnectionStatus $status,
        public readonly ?string $phone = null,
    ) {}
}
