<?php

namespace App\Actions;

use App\Concerns\SyncsCampaignMessagesAndRecipients;
use App\Data\CampaignData;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

final class CreateCampaignAction
{
    use SyncsCampaignMessagesAndRecipients;

    /**
     * Create a Campaign in draft status, its message pool, and a
     * CampaignRecipient snapshot (status pending) for each selected Lead.
     */
    public function execute(CampaignData $data): Campaign
    {
        return DB::transaction(function () use ($data): Campaign {
            $campaign = Campaign::create([
                'name' => $data->name,
                'status' => CampaignStatus::DRAFT,
                'delay_min_seconds' => $data->delay_min_seconds,
                'delay_max_seconds' => $data->delay_max_seconds,
            ]);

            $this->syncMessagesAndRecipients($campaign, $data->messages, $data->lead_ids);

            return $campaign;
        });
    }
}
