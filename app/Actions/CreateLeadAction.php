<?php

namespace App\Actions;

use App\Data\LeadData;
use App\Enums\LeadStatus;
use App\Models\Lead;

final class CreateLeadAction
{
    public function execute(LeadData $data): Lead
    {
        return Lead::create([
            ...$data->toArray(),
            'status' => LeadStatus::NEW,
        ]);
    }
}
