<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class SendMessageResultData extends Data
{
    public function __construct(
        public readonly bool $success,
        public readonly string $messageId,
    ) {}
}
