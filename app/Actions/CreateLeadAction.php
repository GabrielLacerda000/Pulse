<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;

final class CreateLeadAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Lead
    {
        return Lead::create([
            ...$data,
            'status' => LeadStatus::NEW,
        ]);
    }
}
