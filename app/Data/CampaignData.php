<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class CampaignData extends Data
{
    /**
     * @param  array<int, int>  $lead_ids
     * @param  array<int, string>  $messages
     */
    public function __construct(
        public readonly string $name,
        public readonly int $delay_min_seconds,
        public readonly int $delay_max_seconds,
        public readonly array $lead_ids,
        public readonly array $messages,
    ) {}
}
