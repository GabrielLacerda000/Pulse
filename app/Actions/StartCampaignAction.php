<?php

namespace App\Actions;

use App\Concerns\LogsStructuredEvents;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\WhatsAppConnectionStatus;
use App\Exceptions\CampaignStartBlockedException;
use App\Exceptions\PulseWppException;
use App\Jobs\SendCampaignRecipientJob;
use App\Models\Campaign;
use App\Services\PulseWppClient;
use Illuminate\Support\Facades\Cache;

final class StartCampaignAction
{
    use LogsStructuredEvents;

    public function __construct(private readonly PulseWppClient $client) {}

    /**
     * Start a draft Campaign's send: guard against a concurrent run and a
     * disconnected WhatsApp session, then dispatch the sequential sender.
     *
     * The concurrency guard and the running-flag update are wrapped in a
     * lock keyed by owner, so two start requests fired at the same time
     * can't both pass the guard before either marks its campaign running.
     *
     * Only the first recipient's job is dispatched here: each job claims
     * and sends to exactly one recipient, then dispatches the next one
     * itself (see SendCampaignRecipientJob).
     *
     * @throws CampaignStartBlockedException
     */
    public function execute(Campaign $campaign): void
    {
        Cache::lock('campaign-start:'.$campaign->user_id, 10)->block(5, function () use ($campaign): void {
            if (Campaign::query()->where('status', CampaignStatus::RUNNING)->exists()) {
                $this->logEvent('campaign.start_blocked', [
                    ...$this->campaignLogContext($campaign),
                    'reason' => 'concurrent_run',
                ], 'notice');

                throw new CampaignStartBlockedException(
                    __('Você já tem uma campanha em andamento. Aguarde ela terminar antes de iniciar outra.')
                );
            }

            if (! $this->isWhatsAppConnected()) {
                $this->logEvent('campaign.start_blocked', [
                    ...$this->campaignLogContext($campaign),
                    'reason' => 'whatsapp_disconnected',
                ], 'notice');

                throw new CampaignStartBlockedException(
                    __('Conecte o WhatsApp antes de iniciar a campanha.')
                );
            }

            $campaign->update(['status' => CampaignStatus::RUNNING, 'started_at' => now()]);

            $this->logEvent('campaign.started', $this->campaignLogContext($campaign));
        });

        $firstRecipient = $campaign->recipients()
            ->where('status', CampaignRecipientStatus::PENDING)
            ->orderBy('id')
            ->first();

        if ($firstRecipient === null) {
            $campaign->update(['status' => CampaignStatus::COMPLETED, 'completed_at' => now()]);

            return;
        }

        SendCampaignRecipientJob::dispatch($firstRecipient);
    }

    private function isWhatsAppConnected(): bool
    {
        try {
            return $this->client->status()->status === WhatsAppConnectionStatus::CONNECTED;
        } catch (PulseWppException) {
            return false;
        }
    }

    /**
     * @return array{campaign_id: int, user_id: int}
     */
    private function campaignLogContext(Campaign $campaign): array
    {
        return [
            'campaign_id' => $campaign->id,
            'user_id' => $campaign->user_id,
        ];
    }
}
