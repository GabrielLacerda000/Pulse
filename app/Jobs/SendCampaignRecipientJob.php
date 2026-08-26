<?php

namespace App\Jobs;

use App\Actions\RenderMessageTemplateAction;
use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\PulseWppErrorCode;
use App\Exceptions\PulseWppException;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Lead;
use App\Services\PulseWppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends to exactly one campaign recipient, since there's only one WhatsApp
 * connection to work with. Before sending anything, it atomically claims
 * its recipient with a conditional `pending -> processing` update: if the
 * claim fails (the recipient was already claimed by another instance of
 * this job), the job stops immediately with no send and no further
 * dispatch. That's what makes it safe for two overlapping instances of the
 * same job to exist without racing to send to (and record) the same
 * recipient.
 *
 * On success, the recipient moves processing -> sent. On a non-auth
 * failure, processing -> failed. A WHATSAPP_AUTH_REQUIRED error aborts the
 * whole run instead: it marks every still-pending (or currently claimed)
 * recipient failed with that error code and leaves the campaign FAILED,
 * without setting completed_at.
 *
 * When there's more work to do, the job dispatches the next pending
 * recipient's job with a random delay instead of sleeping in-process, so no
 * single job instance ever runs long enough to be reclaimed by the queue
 * as "stuck".
 *
 * Recording a recipient's outcome is retried on transient SQLite "database
 * is locked" errors (see docs/adr/0004-retry-sqlite-lock-errors-in-send-campaign-job.md):
 * the shared queue/app database can be busy at the exact moment we write.
 * If it still fails after retrying, the recipient is left as-is and the run
 * continues rather than aborting the whole campaign over one write.
 */
final class SendCampaignRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    private const RECIPIENT_UPDATE_ATTEMPTS = 3;

    private const RECIPIENT_UPDATE_RETRY_DELAY_MS = 200;

    public function __construct(public readonly CampaignRecipient $recipient) {}

    public function handle(PulseWppClient $client, RenderMessageTemplateAction $renderer): void
    {
        $claimed = CampaignRecipient::query()
            ->whereKey($this->recipient->id)
            ->where('status', CampaignRecipientStatus::PENDING)
            ->update(['status' => CampaignRecipientStatus::PROCESSING]);

        if ($claimed === 0) {
            return;
        }

        $campaign = $this->recipient->campaign;
        $message = $campaign->messages->random();
        $lead = $this->recipient->lead ?? new Lead(['company_name' => $this->recipient->company_name]);
        $rendered = $renderer->renderContent($message->content, $lead);

        $errorCode = null;

        try {
            $client->sendMessage($this->recipient->phone, $rendered);
        } catch (PulseWppException $exception) {
            if ($exception->errorCode === PulseWppErrorCode::WHATSAPP_AUTH_REQUIRED) {
                $this->abortForAuthRequired($campaign, $exception->errorCode);

                return;
            }

            $errorCode = $exception->errorCode;
        }

        $this->recordOutcome([
            'campaign_message_id' => $message->id,
            'rendered_content' => $rendered,
            'status' => $errorCode === null ? CampaignRecipientStatus::SENT : CampaignRecipientStatus::FAILED,
            'error_code' => $errorCode,
        ]);

        $this->dispatchNextOrFinalize($campaign);
    }

    /**
     * All recipients still pending (or claimed by this job) are marked
     * failed with the auth error, and the campaign is left FAILED with no
     * completed_at, since the run was aborted rather than finished.
     */
    private function abortForAuthRequired(Campaign $campaign, PulseWppErrorCode $errorCode): void
    {
        $this->recordOutcome([
            'status' => CampaignRecipientStatus::FAILED,
            'error_code' => $errorCode,
        ]);

        $campaign->recipients()
            ->where('status', CampaignRecipientStatus::PENDING)
            ->update(['status' => CampaignRecipientStatus::FAILED, 'error_code' => $errorCode]);

        $campaign->update(['status' => CampaignStatus::FAILED]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordOutcome(array $attributes): void
    {
        try {
            retry(
                self::RECIPIENT_UPDATE_ATTEMPTS,
                fn () => $this->recipient->update($attributes),
                self::RECIPIENT_UPDATE_RETRY_DELAY_MS,
                fn (Throwable $exception) => $this->isDatabaseLockException($exception),
            );
        } catch (Throwable $exception) {
            Log::error('Failed to record campaign recipient outcome after retrying.', [
                'campaign_id' => $this->recipient->campaign_id,
                'campaign_recipient_id' => $this->recipient->id,
                'exception' => $exception,
            ]);
        }
    }

    private function dispatchNextOrFinalize(Campaign $campaign): void
    {
        $next = $campaign->recipients()
            ->where('status', CampaignRecipientStatus::PENDING)
            ->orderBy('id')
            ->first();

        if ($next === null) {
            $campaign->update(['status' => CampaignStatus::COMPLETED, 'completed_at' => now()]);

            return;
        }

        self::dispatch($next)->delay(random_int($campaign->delay_min_seconds, $campaign->delay_max_seconds));
    }

    private function isDatabaseLockException(Throwable $exception): bool
    {
        return $exception instanceof QueryException
            && str_contains($exception->getMessage(), 'database is locked');
    }

    public function failed(?Throwable $exception): void
    {
        $this->recipient->campaign->update(['status' => CampaignStatus::FAILED]);
    }
}
