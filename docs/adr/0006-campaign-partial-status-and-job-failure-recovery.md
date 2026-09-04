# 0006. Campaign partial-success status and recovery from a job's own failure

## Status

Accepted

## Context

A campaign run against 15 leads came back with 14 messages successfully sent,
but the campaign itself was left `failed`, and the 15th (last) recipient was
stuck `processing` — shown to the user as "pendente", since
`Campaign::progressCounts()` and `CampaignRecipient::scopeFilterStatus()`
both group `processing` together with `pending` for display purposes.

`SendCampaignRecipientJob` is a self-chaining, single-recipient job (no
`Bus::batch`): each job atomically claims one recipient
(`pending -> processing`), sends via `PulseWppClient::sendMessage()`, records
the outcome, then dispatches the next pending recipient itself. It has
`$tries = 1` and `$timeout = 120`, so any timeout or unhandled exception is
terminal — Laravel then invokes the job's `failed()` callback instead of
`handle()` finishing normally.

Two separate problems combined to produce this exact symptom:

1. **`retry_after` was less than the job's `timeout`.** `config/queue.php`
   defaulted `retry_after` to `90` seconds for the `database` connection (the
   one actually in use, `QUEUE_CONNECTION=database`), while the job's own
   `timeout` was `120` seconds. Laravel requires `retry_after` to exceed a
   job's `timeout` — otherwise the queue can treat a still-running job as
   lost and reschedule/kill it before it finishes normally, which is the
   likely trigger for the 15th recipient landing in `failed()` in the first
   place (a slow `pulse-wpp` response on that particular send).

2. **`failed()` never resolved the recipient it had claimed.** The old
   implementation only did this:

   ```php
   public function failed(?Throwable $exception): void
   {
       $this->logEvent('campaign.job_failed', [...], 'error');
       $this->recipient->campaign->update(['status' => CampaignStatus::FAILED]);
   }
   ```

   It marked the whole campaign `FAILED` but left `$this->recipient` exactly
   as `handle()` had claimed it — `processing` — and never continued the
   chain. So the run stopped dead with one recipient permanently stranded,
   even though every other recipient had already succeeded.

Separately, `dispatchNextOrFinalize()` always finalized a campaign to
`COMPLETED` once no recipient was left `pending`, regardless of how many
recipients had individually ended up `failed`. Combined with the bug above,
the only way a campaign could show anything other than "all good" was to be
forced into `FAILED` — there was no way to say "14 sent, 1 failed, otherwise
done".

## Decision

1. **Fix the `retry_after`/`timeout` mismatch.** Bumped the default
   `retry_after` from `90` to `150` seconds (30s of margin above the job's
   120s `timeout`) for the `database`, `beanstalkd`, and `redis` queue
   connections in `config/queue.php`, so a normal-length send can't be
   mistaken for a lost job.

2. **`failed()` now resolves its own recipient and keeps the chain going**,
   instead of leaving it stranded and stopping the run:

   ```php
   public function failed(?Throwable $exception): void
   {
       $this->logEvent('campaign.job_failed', [...], 'error');

       $updated = CampaignRecipient::query()
           ->whereKey($this->recipient->id)
           ->where('status', CampaignRecipientStatus::PROCESSING)
           ->update(['status' => CampaignRecipientStatus::FAILED, 'error_code' => PulseWppErrorCode::INTERNAL_ERROR]);

       if ($updated === 0) {
           return;
       }

       $this->dispatchNextOrFinalize($this->recipient->campaign);
   }
   ```

   The conditional `WHERE status = PROCESSING` guards against a race where
   the recipient was already resolved some other way by the time `failed()`
   runs (e.g. `handle()` itself finished and recorded an outcome before the
   timeout signal arrived) — in that case nothing is updated and the chain is
   not re-finalized. `abortForAuthRequired()` is unchanged: a
   `WHATSAPP_AUTH_REQUIRED` error is still a deliberate "stop everything,
   fail the rest" condition, unlike a single job's own timeout/crash.

3. **Added `CampaignStatus::PARTIAL`** and a single shared rule for deciding
   a campaign's terminal status from its recipient counts, in
   `Campaign::terminalStatusFor()`:

   ```php
   public static function terminalStatusFor(int $sent, int $failed): CampaignStatus
   {
       if ($failed === 0) {
           return CampaignStatus::COMPLETED;
       }

       if ($sent === 0) {
           return CampaignStatus::FAILED;
       }

       return CampaignStatus::PARTIAL;
   }
   ```

   `dispatchNextOrFinalize()` (both the normal-completion path and the
   `failed()` recovery path above) now calls this instead of hardcoding
   `COMPLETED`. `canRetryFailed()` was extended to also allow retrying from
   `PARTIAL`, not just `COMPLETED`. The frontend gained a matching label
   ("Sucesso parcial") and an amber badge, distinct from the green
   `completed` and red `failed` treatments.

## Consequences

- A single recipient's transient failure (timeout, crash) no longer takes
  down the rest of the campaign — the chain continues, and the campaign's
  final status now honestly reflects a mix of outcomes instead of being
  forced to `FAILED` or `COMPLETED`.
- `completed_at` is now set even when a campaign's normal (non-abort) finish
  lands on `FAILED` — e.g. every recipient ended up failing — so it
  consistently marks "when the run stopped happening", for `COMPLETED`,
  `PARTIAL`, and exhaustion-`FAILED` alike. `abortForAuthRequired()` still
  deliberately leaves `completed_at` null, since that path is an abort, not
  a normal finish.
- `PARTIAL` is a new value read from the `campaigns.status` column, which is
  a plain `string` (no DB-level enum constraint), so no migration was
  needed — only the PHP enum and the frontend's `CampaignStatus` union
  changed.
- The `retry_after` bump doesn't eliminate the underlying risk if a single
  recipient's send genuinely takes longer than the job's 120s `timeout` (a
  truly slow `pulse-wpp` response) — it only prevents the queue from
  mistaking a job that's still healthily running for a lost one. If jobs
  keep landing in `failed()` under normal conditions, that's a sign the
  `pulse-wpp` client itself needs a shorter HTTP timeout or the job's
  `$timeout` needs raising, not a further `retry_after` bump.
