<?php

use App\Actions\RenderMessageTemplateAction;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\PulseWppErrorCode;
use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\User;
use App\Services\PulseWppClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use PDOException;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('it sends to every pending recipient sequentially, waiting between sends but not before the first', function () {
    Sleep::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING, 'delay_min_seconds' => 30, 'delay_max_seconds' => 30]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'content' => 'Olá {nome}!']);
    $leadOne = Lead::factory()->create(['company_name' => 'Empresa Um']);
    $leadTwo = Lead::factory()->create(['company_name' => 'Empresa Dois']);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $leadOne->id, 'company_name' => 'Empresa Um', 'phone' => '5581999999999']);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $leadTwo->id, 'company_name' => 'Empresa Dois', 'phone' => '5581988888888']);

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertSentCount(2);
    Sleep::assertSequence([Sleep::for(30)->seconds()]);

    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($recipientOne->fresh()->rendered_content)->toBe('Olá Empresa Um!');
    expect($recipientTwo->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($recipientTwo->fresh()->rendered_content)->toBe('Olá Empresa Dois!');
    expect($campaign->fresh()->status)->toBe(CampaignStatus::COMPLETED);
});

test('it falls back to the recipient snapshot when the Lead has been deleted', function () {
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'content' => 'Olá {nome}!']);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => null, 'company_name' => 'Empresa Snapshot', 'phone' => '5581999999999']);

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    expect($recipient->fresh()->rendered_content)->toBe('Olá Empresa Snapshot!');
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
});

test('a non-auth error marks the recipient failed with the stable error code and continues to the next', function (string $code, int $status) {
    Http::fake(['pulse-wpp.test/messages' => Http::response([
        'success' => false,
        'error' => ['code' => $code, 'message' => "error: {$code}"],
    ], $status)]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::FAILED);
    expect($recipientOne->fresh()->error_code)->toBe(PulseWppErrorCode::from($code));
    expect($recipientTwo->fresh()->status)->toBe(CampaignRecipientStatus::FAILED);
    expect($recipientTwo->fresh()->error_code)->toBe(PulseWppErrorCode::from($code));
    expect($campaign->fresh()->status)->toBe(CampaignStatus::COMPLETED);
})->with([
    ['UNAUTHORIZED', 401],
    ['INVALID_REQUEST', 400],
    ['WHATSAPP_NOT_CONNECTED', 409],
    ['MESSAGE_SEND_FAILED', 502],
    ['PROVIDER_UNAVAILABLE', 503],
    ['INTERNAL_ERROR', 500],
]);

test('a WHATSAPP_AUTH_REQUIRED error aborts the run immediately, leaving untried recipients pending', function () {
    Http::fake(['pulse-wpp.test/messages' => Http::response([
        'success' => false,
        'error' => ['code' => 'WHATSAPP_AUTH_REQUIRED', 'message' => 'error: WHATSAPP_AUTH_REQUIRED'],
    ], 401)]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertSentCount(1);
    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::PENDING);
    expect($recipientTwo->fresh()->status)->toBe(CampaignRecipientStatus::PENDING);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::FAILED);
});

test('it retries a transient SQLite lock error when recording the outcome, then succeeds', function () {
    Sleep::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    $attempts = 0;
    CampaignRecipient::updating(function () use (&$attempts) {
        $attempts++;

        if ($attempts === 1) {
            throw new QueryException('sqlite', 'update "campaign_recipients" ...', [], new PDOException('database is locked'));
        }
    });

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    CampaignRecipient::flushEventListeners();

    expect($attempts)->toBe(2);
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::COMPLETED);
});

test('it logs and moves on to the next recipient when the database stays locked', function () {
    Sleep::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);
    Log::spy();

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    CampaignRecipient::updating(function (CampaignRecipient $model) use ($recipientOne) {
        if ($model->is($recipientOne)) {
            throw new QueryException('sqlite', 'update "campaign_recipients" ...', [], new PDOException('database is locked'));
        }
    });

    (new SendCampaignJob($campaign))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    CampaignRecipient::flushEventListeners();

    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::PENDING);
    expect($recipientTwo->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::COMPLETED);
    Log::shouldHaveReceived('error')->once();
});

test('a job that fails outright marks the campaign failed', function () {
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);

    (new SendCampaignJob($campaign))->failed(new RuntimeException('boom'));

    expect($campaign->fresh()->status)->toBe(CampaignStatus::FAILED);
});
