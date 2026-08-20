<?php

namespace App\Data;

use Spatie\LaravelData\Data;

final class LeadData extends Data
{
    public function __construct(
        public readonly string $company_name,
        public readonly ?string $category = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $website = null,
        public readonly ?string $instagram = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $notes = null,
    ) {}
}
