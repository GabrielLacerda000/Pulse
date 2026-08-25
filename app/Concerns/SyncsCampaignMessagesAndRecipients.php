<?php

namespace App\Concerns;

use App\Enums\CampaignRecipientStatus;
use App\Models\Campaign;
use App\Models\Lead;

trait SyncsCampaignMessagesAndRecipients
{
    /**
     * Replace the Campaign's message pool with the given contents, and its
     * recipient snapshot with a pending CampaignRecipient per selected Lead.
     *
     * @param  array<int, string>  $messages
     * @param  array<int, int>  $leadIds
     */
    private function syncMessagesAndRecipients(Campaign $campaign, array $messages, array $leadIds): void
    {
        foreach ($messages as $message) {
            $campaign->messages()->create(['content' => $message]);
        }

        $leads = Lead::query()->whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            $campaign->recipients()->create([
                'lead_id' => $lead->id,
                'company_name' => $lead->company_name,
                'phone' => $lead->whatsapp_phone,
                'status' => CampaignRecipientStatus::PENDING,
            ]);
        }
    }
}
