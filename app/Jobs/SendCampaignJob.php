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
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Sends a Campaign's pending recipients one at a time, since there's only
 * one WhatsApp connection to work with. Never retried automatically: any
 * per-recipient failure is recorded and the job moves on, except a
 * WHATSAPP_AUTH_REQUIRED error, which aborts the whole run immediately.
 *
 * Recording a recipient's outcome is retried on transient SQLite "database
 * is locked" errors (see docs/adr/0004-retry-sqlite-lock-errors-in-send-campaign-job.md):
 * the shared queue/app database can be busy at the exact moment we write.
 * If it still fails after retrying, the recipient is left as-is and the run
 * continues rather than aborting the whole campaign over one write.
 */
final class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    private const RECIPIENT_UPDATE_ATTEMPTS = 3;

    private const RECIPIENT_UPDATE_RETRY_DELAY_MS = 200;

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(PulseWppClient $client, RenderMessageTemplateAction $renderer): void
    {
        $messages = $this->campaign->messages;
        $isFirstSend = true;

        foreach ($this->pendingRecipients() as $recipient) {
            if (! $isFirstSend) {
                Sleep::for(random_int($this->campaign->delay_min_seconds, $this->campaign->delay_max_seconds))->seconds();
            }
            $isFirstSend = false;

            $message = $messages->random();
            $lead = $recipient->lead ?? new Lead(['company_name' => $recipient->company_name]);
            $rendered = $renderer->renderContent($message->content, $lead);

            $errorCode = null;

            try {
                $client->sendMessage($recipient->phone, $rendered);
            } catch (PulseWppException $exception) {
                if ($exception->errorCode === PulseWppErrorCode::WHATSAPP_AUTH_REQUIRED) {
                    $this->campaign->update(['status' => CampaignStatus::FAILED]);

                    return;
                }

                $errorCode = $exception->errorCode;
            }

            try {
                retry(
                    self::RECIPIENT_UPDATE_ATTEMPTS,
                    fn () => $recipient->update([
                        'campaign_message_id' => $message->id,
                        'rendered_content' => $rendered,
                        'status' => $errorCode === null ? CampaignRecipientStatus::SENT : CampaignRecipientStatus::FAILED,
                        'error_code' => $errorCode,
                    ]),
                    self::RECIPIENT_UPDATE_RETRY_DELAY_MS,
                    fn (Throwable $exception) => $this->isDatabaseLockException($exception),
                );
            } catch (Throwable $exception) {
                Log::error('Failed to record campaign recipient outcome after retrying.', [
                    'campaign_id' => $this->campaign->id,
                    'campaign_recipient_id' => $recipient->id,
                    'exception' => $exception,
                ]);
            }
        }

        $this->campaign->update(['status' => CampaignStatus::COMPLETED]);
    }

    private function isDatabaseLockException(Throwable $exception): bool
    {
        return $exception instanceof QueryException
            && str_contains($exception->getMessage(), 'database is locked');
    }

    public function failed(?Throwable $exception): void
    {
        $this->campaign->update(['status' => CampaignStatus::FAILED]);
    }

    /**
     * @return LazyCollection<int, CampaignRecipient>
     */
    private function pendingRecipients()
    {
        return $this->campaign->recipients()
            ->where('status', CampaignRecipientStatus::PENDING)
            ->cursor();
    }
}
