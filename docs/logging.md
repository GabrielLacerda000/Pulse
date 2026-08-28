# Structured campaign logging

Living catalog of structured log events for the campaign send / WhatsApp
integration flow. Extend the table below when a new event is added — no ADR
update needed for catalog changes (see ADR 0005).

## Mechanism

- `App\Concerns\LogsStructuredEvents` trait exposes `logEvent(string $event, array $context = [], string $level = 'info'): void`.
- Consuming classes (`StartCampaignAction`, `SendCampaignRecipientJob`, `PulseWppClient`) `use` the trait and may override `logChannel()`; all current consumers use the default `campaigns` channel.
- No ambient context (e.g. `Auth::id()`) is injected by the trait — every call site passes its own relevant fields explicitly. This matters because `SendCampaignRecipientJob` runs in a queue worker with no authenticated session.

## Naming convention

Event names are dot-separated, stable strings following `domain.subject.outcome`:

- `campaign.started`, `campaign.completed` — campaign-level lifecycle.
- `campaign.recipient.sent`, `campaign.recipient.failed` — per-recipient outcomes.
- `whatsapp.api.request_failed` — a different domain (`whatsapp`), not `campaign`, since it's shared by non-campaign callers (`WhatsAppConnectionController`).

Treat event names as an append-only, stable API: once shipped, don't rename or repurpose one — add a new event instead.

## Event catalog

| Event | Level | Emitted from | Minimum context |
|---|---|---|---|
| `campaign.started` | info | `StartCampaignAction::execute()`, after marking `RUNNING` | `campaign_id`, `user_id` |
| `campaign.start_blocked` | notice | `StartCampaignAction::execute()`, both `CampaignStartBlockedException` branches | `campaign_id`, `user_id`, `reason` (`concurrent_run` \| `whatsapp_disconnected`) |
| `campaign.recipient.claim_missed` | info | `SendCampaignRecipientJob::handle()`, when the conditional `pending -> processing` claim affects 0 rows | `campaign_id`, `user_id`, `campaign_recipient_id` |
| `campaign.recipient.sent` | info | `SendCampaignRecipientJob::handle()`, success path | `campaign_id`, `user_id`, `campaign_recipient_id`, `campaign_message_id` |
| `campaign.recipient.failed` | warning | `SendCampaignRecipientJob::handle()`, non-auth failure path | `campaign_id`, `user_id`, `campaign_recipient_id`, `error_code` |
| `campaign.recipient.outcome_write_failed` | error | `SendCampaignRecipientJob::recordOutcome()`, after the SQLite-lock retry (ADR 0004) is exhausted | `campaign_id`, `user_id`, `campaign_recipient_id`, `exception` |
| `campaign.aborted_auth_required` | warning | `SendCampaignRecipientJob::abortForAuthRequired()` | `campaign_id`, `user_id` |
| `campaign.completed` | info | `SendCampaignRecipientJob::dispatchNextOrFinalize()`, no pending recipients left | `campaign_id`, `user_id` |
| `campaign.job_failed` | error | `SendCampaignRecipientJob::failed()` | `campaign_id`, `user_id`, `campaign_recipient_id`, `exception` |
| `whatsapp.api.request_failed` | warning | `PulseWppClient::authenticated()`, whenever a `PulseWppException` is caught around an HTTP call (`sendMessage`, `status`, `qr`, `connect`, `disconnect`) | `endpoint`, `error_code`, `error.message` |

`health()` on `PulseWppClient` intentionally bypasses logging — it's an infrastructure check, not a WhatsApp API call.

## Channel and retention

- Channel: `campaigns`, configured in `config/logging.php`.
- Driver: `daily` (Monolog), path `storage/logs/campaigns.log`.
- Level: `env('LOG_CAMPAIGNS_LEVEL', 'debug')`.
- Retention: `env('LOG_CAMPAIGNS_DAYS', 30)` days (`max_files`).
- Tail with `php artisan pail --filter=...`, or read the daily file directly — it's kept separate from `laravel.log` so it isn't lost in general framework/HTTP noise.

## PII policy

- Phone numbers, when logged, must be masked (e.g. `+55***1234`). The current catalog above logs no phone number in any event's context — none of the existing call sites pass one.
- The WhatsApp message body / rendered content is never logged, in any event.
- The `PulseWppException` message (`error.message` from the provider's error response) is logged as-is. It's `pulse-wpp`'s own error description, not the outbound message payload, so it's exempt from the "no message content" rule.

## Adding a new event

1. Pick a name following the `domain.subject.outcome` convention.
2. Add a row to the catalog table above with its level, emission point, and minimum context fields.
3. Call `$this->logEvent(...)` from the emitting class. If the class doesn't already `use LogsStructuredEvents`, add it (and override `logChannel()` only if the event doesn't belong on `campaigns`).
4. Add a test asserting the log call (event name, level, key context fields) via `Log::spy()` + `Log::shouldHaveReceived(...)`, matching the pattern already used in `tests/Feature/Jobs/SendCampaignRecipientJobTest.php`, `tests/Feature/Actions/StartCampaignActionTest.php`, and `tests/Feature/Services/PulseWppClientTest.php`.

No ADR update is required for catalog growth — see ADR 0005's expansion roadmap.
