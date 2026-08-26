<?php

use App\Actions\RenderMessageTemplateAction;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\PulseWppErrorCode;
use App\Jobs\SendCampaignRecipientJob;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Models\User;
use App\Services\PulseWppClient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PDOException;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('it claims a pending recipient, sends to it, and dispatches the next one with a delay in range', function () {
    Queue::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING, 'delay_min_seconds' => 10, 'delay_max_seconds' => 20]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'content' => 'Olá {nome}!']);
    $lead = Lead::factory()->create(['company_name' => 'Empresa Um']);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $lead->id, 'company_name' => 'Empresa Um', 'phone' => '5581999999999']);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignRecipientJob($recipientOne))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertSentCount(1);
    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($recipientOne->fresh()->rendered_content)->toBe('Olá Empresa Um!');
    expect($campaign->fresh()->status)->toBe(CampaignStatus::RUNNING);

    Queue::assertPushed(SendCampaignRecipientJob::class, function ($job) use ($recipientTwo) {
        return $job->recipient->is($recipientTwo)
            && $job->delay >= 10 && $job->delay <= 20;
    });
});

test('it falls back to the recipient snapshot when the Lead has been deleted', function () {
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'content' => 'Olá {nome}!']);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => null, 'company_name' => 'Empresa Snapshot', 'phone' => '5581999999999']);

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    expect($recipient->fresh()->rendered_content)->toBe('Olá Empresa Snapshot!');
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
});

test('when no pending recipients remain, the campaign finalizes to completed with completed_at set', function () {
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    $fresh = $campaign->fresh();
    expect($fresh->status)->toBe(CampaignStatus::COMPLETED);
    expect($fresh->completed_at)->not->toBeNull();
});

test('a claim on a recipient that is no longer pending sends nothing and dispatches nothing', function () {
    Queue::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id, 'status' => CampaignRecipientStatus::PROCESSING]);

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertNothingSent();
    Queue::assertNothingPushed();
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::PROCESSING);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::RUNNING);
});

test('a concurrent claim on the same recipient loses the race and sends nothing', function () {
    Queue::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    CampaignRecipient::query()->whereKey($recipient->id)->update(['status' => CampaignRecipientStatus::PROCESSING]);

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertNothingSent();
    Queue::assertNothingPushed();
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::PROCESSING);
});

test('a non-auth error marks the recipient failed with the stable error code and dispatches the next recipient', function (string $code, int $status) {
    Queue::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response([
        'success' => false,
        'error' => ['code' => $code, 'message' => "error: {$code}"],
    ], $status)]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignRecipientJob($recipientOne))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::FAILED);
    expect($recipientOne->fresh()->error_code)->toBe(PulseWppErrorCode::from($code));
    expect($campaign->fresh()->status)->toBe(CampaignStatus::RUNNING);
    Queue::assertPushed(SendCampaignRecipientJob::class, fn ($job) => $job->recipient->is($recipientTwo));
})->with([
    ['UNAUTHORIZED', 401],
    ['INVALID_REQUEST', 400],
    ['WHATSAPP_NOT_CONNECTED', 409],
    ['MESSAGE_SEND_FAILED', 502],
    ['PROVIDER_UNAVAILABLE', 503],
    ['INTERNAL_ERROR', 500],
]);

test('a WHATSAPP_AUTH_REQUIRED error aborts the run, marking the claimed and still-pending recipients failed', function () {
    Queue::fake();
    Http::fake(['pulse-wpp.test/messages' => Http::response([
        'success' => false,
        'error' => ['code' => 'WHATSAPP_AUTH_REQUIRED', 'message' => 'error: WHATSAPP_AUTH_REQUIRED'],
    ], 401)]);

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipientOne = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);
    $recipientTwo = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignRecipientJob($recipientOne))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    Http::assertSentCount(1);
    expect($recipientOne->fresh()->status)->toBe(CampaignRecipientStatus::FAILED);
    expect($recipientOne->fresh()->error_code)->toBe(PulseWppErrorCode::WHATSAPP_AUTH_REQUIRED);
    expect($recipientTwo->fresh()->status)->toBe(CampaignRecipientStatus::FAILED);
    expect($recipientTwo->fresh()->error_code)->toBe(PulseWppErrorCode::WHATSAPP_AUTH_REQUIRED);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::FAILED);
    expect($campaign->fresh()->completed_at)->toBeNull();
    Queue::assertNothingPushed();
});

test('it retries a transient SQLite lock error when recording the outcome, then succeeds', function () {
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

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    CampaignRecipient::flushEventListeners();

    expect($attempts)->toBe(2);
    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::SENT);
    expect($campaign->fresh()->status)->toBe(CampaignStatus::COMPLETED);
});

test('it logs when the database stays locked while recording the outcome', function () {
    Http::fake(['pulse-wpp.test/messages' => Http::response(['success' => true, 'messageId' => 'abc'])]);
    Log::spy();

    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    CampaignMessage::factory()->create(['campaign_id' => $campaign->id]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    CampaignRecipient::updating(function (CampaignRecipient $model) {
        throw new QueryException('sqlite', 'update "campaign_recipients" ...', [], new PDOException('database is locked'));
    });

    (new SendCampaignRecipientJob($recipient))->handle(app(PulseWppClient::class), app(RenderMessageTemplateAction::class));

    CampaignRecipient::flushEventListeners();

    expect($recipient->fresh()->status)->toBe(CampaignRecipientStatus::PROCESSING);
    Log::shouldHaveReceived('error')->once();
});

test('a job that fails outright marks the campaign failed', function () {
    $campaign = Campaign::factory()->create(['status' => CampaignStatus::RUNNING]);
    $recipient = CampaignRecipient::factory()->create(['campaign_id' => $campaign->id]);

    (new SendCampaignRecipientJob($recipient))->failed(new RuntimeException('boom'));

    expect($campaign->fresh()->status)->toBe(CampaignStatus::FAILED);
});
