<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Models\Lead;

final class UpdateLeadStatusAction
{
    public function execute(Lead $lead, LeadStatus $status): Lead
    {
        $lead->update([
            'status' => $status,
        ]);

        return $lead->refresh();
    }
}
