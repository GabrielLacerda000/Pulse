<?php

namespace App\Actions;

use App\Concerns\SyncsCampaignMessagesAndRecipients;
use App\Data\CampaignData;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

final class UpdateCampaignAction
{
    use SyncsCampaignMessagesAndRecipients;

    /**
     * Update a draft Campaign's fields and replace its message pool and
     * Lead snapshot with the newly submitted ones.
     */
    public function execute(Campaign $campaign, CampaignData $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data): Campaign {
            $campaign->update([
                'name' => $data->name,
                'delay_min_seconds' => $data->delay_min_seconds,
                'delay_max_seconds' => $data->delay_max_seconds,
            ]);

            $campaign->messages()->delete();
            $campaign->recipients()->delete();

            $this->syncMessagesAndRecipients($campaign, $data->messages, $data->lead_ids);

            return $campaign->refresh();
        });
    }
}
