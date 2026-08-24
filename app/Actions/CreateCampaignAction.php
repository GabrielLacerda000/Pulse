<?php

namespace App\Actions;

use App\Data\CampaignData;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

final class CreateCampaignAction
{
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

            foreach ($data->messages as $message) {
                $campaign->messages()->create(['content' => $message]);
            }

            $leads = Lead::query()->whereIn('id', $data->lead_ids)->get();

            foreach ($leads as $lead) {
                $campaign->recipients()->create([
                    'lead_id' => $lead->id,
                    'company_name' => $lead->company_name,
                    'phone' => $lead->whatsapp_phone,
                    'status' => CampaignRecipientStatus::PENDING,
                ]);
            }

            return $campaign;
        });
    }
}
