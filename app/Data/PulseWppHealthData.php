<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class PulseWppHealthData extends Data
{
    public function __construct(
        public readonly string $status,
    ) {}
}
