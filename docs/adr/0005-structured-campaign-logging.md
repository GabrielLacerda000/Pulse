# 0005. Structured logging for the campaign send / WhatsApp flow

## Status

Accepted

## Context

Before this change, the only trace of a campaign send's behavior was a
single ad-hoc `Log::error` call inside
`SendCampaignRecipientJob::recordOutcome()` (added alongside the SQLite
lock retry in ADR 0004). Every other step of the flow — starting a
campaign, being blocked from starting one, a recipient claim race, a
recipient send succeeding or failing, a run aborting for auth, a campaign
completing, a queued job failing outright, or a `pulse-wpp` API call
failing — was silent. Diagnosing "why did campaign X fail for half its
recipients" meant reconstructing the story from final DB state alone,
which doesn't distinguish a claim race from a send failure from a job
crash.

## Decision

- **A trait, not a DI service.** `App\Concerns\LogsStructuredEvents`
  exposes `logEvent(string $event, array $context = [], string $level =
  'info'): void`, resolving the channel via an overridable `logChannel()`
  and delegating to `Log::channel(...)->{$level}(...)`. This follows the
  existing `app/Concerns/*` convention (`CampaignValidationRules`,
  `SyncsCampaignMessagesAndRecipients`) rather than introducing a new
  injectable service, keeping call sites as simple one-line `use` +
  method-call additions to existing classes.
- **A dedicated `campaigns` channel**, not the default `laravel.log`. Daily
  driver, `storage/logs/campaigns.log`, level and retention independently
  configurable (`LOG_CAMPAIGNS_LEVEL`, `LOG_CAMPAIGNS_DAYS`, default 30
  days). Business-relevant campaign history can be retained longer than
  generic framework/HTTP log noise without a code change, and the channel
  can be tailed independently with `php artisan pail`.
- **Explicit context only — no ambient state.** The trait does not inject
  `Auth::id()` or any other ambient value. `SendCampaignRecipientJob` runs
  in a queue worker with no authenticated session, so every call site
  passes its own relevant context explicitly (e.g. `$campaign->user_id`).
  This keeps the trait usable identically from HTTP-request code
  (`StartCampaignAction`) and queue-worker code
  (`SendCampaignRecipientJob`, `PulseWppClient`) without a divergent
  context-resolution path for each.
- **A stable event-key naming convention** (`domain.subject.outcome`,
  dot-separated) instead of free-text log messages, so log lines are
  greppable/filterable by a stable string rather than parsed from prose.
- **The event catalog lives in `docs/logging.md`, not this ADR.** The
  catalog (event names, levels, emission points, minimum context fields)
  is expected to grow as more events are added; recording it in a table
  that requires editing an ADR each time would make catalog growth feel
  heavier than it is. This ADR records the *mechanism* and the decisions
  behind it; `docs/logging.md` records the *current state* of what's
  emitted.
- **Scope: only the classes that needed it.** `StartCampaignAction`,
  `SendCampaignRecipientJob`, and `PulseWppClient` were wired to the trait
  in this iteration. `PulseWppClient` also covers `status()`/`qr()`/
  `connect()`/`disconnect()`, since those share the same
  `PulseWppException`-driven failure path and are called from
  `WhatsAppConnectionController` outside any campaign run — those calls
  get `whatsapp.api.request_failed` context but no `campaign_id`
  correlation, since they aren't part of a campaign.
- **PII stays out of context.** Phone numbers, if ever logged, must be
  masked; the WhatsApp message body/rendered content is never logged. The
  `PulseWppException` message (the provider's own `error.message`) is
  exempt from the "no message content" rule — it originates from
  `pulse-wpp`'s error response, not from anything Pulse sends.

## Consequences

- A campaign run's full story (blocked start, claim races, per-recipient
  outcomes, auth aborts, completion, hard job failures, provider API
  failures) can now be reconstructed from `storage/logs/campaigns.log`
  alone, without querying application state.
- Adding a new structured event to one of the three wired classes is a
  one-line `logEvent(...)` call plus a `docs/logging.md` row — no new
  service, no DI wiring, no ADR edit.
- **Expansion roadmap:** other areas of the app (auth, future domains)
  wire into `LogsStructuredEvents` only when they actually need structured
  logging, not built speculatively ahead of need. When that happens, the
  same trait is reused; a new area with meaningfully different needs
  (e.g. a channel per domain) can override `logChannel()` rather than
  requiring a new mechanism.
- External log aggregation/error-tracking (Sentry, Flare, Papertrail) is
  out of scope — logs stay local (file + `pail`) for now. Swapping the
  `campaigns` channel's driver later doesn't require touching any call
  site, since call sites only know the event name/context/level, not the
  destination.
