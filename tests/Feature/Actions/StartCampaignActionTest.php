<?php

use App\Actions\StartCampaignAction;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Exceptions\CampaignStartBlockedException;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use App\Services\PulseWppClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->client = new PulseWppClient(
        baseUrl: 'http://pulse-wpp.test',
        apiKey: 'testing-api-key',
    );

    $this->channel = Mockery::spy();
    Log::shouldReceive('channel')->with('campaigns')->andReturn($this->channel);
});

test('starting a campaign logs campaign.started at info with campaign_id and user_id', function () {
    Queue::fake();
    Http::fake(['pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'connected'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);
    CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::PENDING]);

    (new StartCampaignAction($this->client))->execute($campaign);

    $this->channel->shouldHaveReceived('info')->once()->withArgs(
        fn (string $event, array $context) => $event === 'campaign.started'
            && $context['campaign_id'] === $campaign->id
            && $context['user_id'] === $campaign->user_id
    );
});

test('a blocked start due to a concurrent run logs campaign.start_blocked at notice with a distinguishing reason', function () {
    Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

    expect(fn () => (new StartCampaignAction($this->client))->execute($campaign))
        ->toThrow(CampaignStartBlockedException::class);

    $this->channel->shouldHaveReceived('notice')->once()->withArgs(
        fn (string $event, array $context) => $event === 'campaign.start_blocked'
            && $context['campaign_id'] === $campaign->id
            && $context['user_id'] === $campaign->user_id
            && $context['reason'] === 'concurrent_run'
    );
});

test('a blocked start due to WhatsApp being disconnected logs campaign.start_blocked at notice with its own reason', function () {
    Http::fake(['pulse-wpp.test/whatsapp/status' => Http::response(['status' => 'disconnected'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);

    expect(fn () => (new StartCampaignAction($this->client))->execute($campaign))
        ->toThrow(CampaignStartBlockedException::class);

    $this->channel->shouldHaveReceived('notice')->once()->withArgs(
        fn (string $event, array $context) => $event === 'campaign.start_blocked'
            && $context['campaign_id'] === $campaign->id
            && $context['user_id'] === $campaign->user_id
            && $context['reason'] === 'whatsapp_disconnected'
    );
});
