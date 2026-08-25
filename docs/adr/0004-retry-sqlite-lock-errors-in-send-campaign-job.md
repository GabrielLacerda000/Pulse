# 0004. Retry SQLite lock errors when recording a campaign recipient's outcome

## Status

Accepted

## Context

A campaign run against 3 leads came back with 1 success and the rest stuck
`pending`, and the queue reported the job itself as failed. The application
log showed the real error:

```
SQLSTATE[HY000]: General error: 5 database is locked
(update "campaign_recipients" set "campaign_message_id" = ?, ...)
```

`SendCampaignJob::handle()` sends to `pulse-wpp` and then writes the
recipient's outcome (`SENT`/`FAILED`) with `$recipient->update(...)`. That
write is a plain SQLite `UPDATE`, not wrapped in any retry. The `catch` block
around the send only handles `PulseWppException` (rejects/failures from the
`pulse-wpp` HTTP API) — a `QueryException` from the subsequent DB write was
never caught. It propagated out of `handle()`, and with `$tries = 1` the job
was marked failed outright, aborting mid-loop and leaving the untried
recipients `pending`.

`database.sqlite` is shared by the web app **and** the queue
(`QUEUE_CONNECTION=database` in `.env`), so writes to `campaign_recipients`
compete with the queue worker's own polling/reservation writes on the *same*
file. `busy_timeout`/WAL/`synchronous=normal` were already set in
`config/database.php` (see commit `731e091`) precisely to absorb this kind
of contention, but under load a single 5s busy-timeout window can still be
exhausted — SQLite then raises "database is locked" instead of silently
succeeding.

Whether the message was actually delivered by `pulse-wpp` before this error
is a separate, currently unverifiable question — `sendMessage()` only
confirms the HTTP call succeeded, not delivery. That's out of scope here;
this ADR only addresses Pulse's own handling of the follow-up DB write.

## Decision

In `SendCampaignJob`, the recipient status update is now wrapped in
`retry()`:

- Up to 3 attempts, 200ms apart.
- Only retried when the exception is a `QueryException` whose message
  contains `"database is locked"` — a targeted, transient condition, not a
  blanket retry-everything policy.
- If it still fails after retrying, the exception is logged
  (`Log::error`, with campaign/recipient IDs) and the job **continues to the
  next recipient** instead of aborting the whole campaign. The
  `WHATSAPP_AUTH_REQUIRED` short-circuit (abort immediately, leave the rest
  `pending`) is untouched — that's a real "we can't send anything else"
  condition, unlike a transient local DB write failure.

## Consequences

- A campaign run no longer dies outright because one recipient's status
  write collided with unrelated queue-table activity on the same SQLite
  file — it retries briefly, then degrades to "skip this recipient, keep
  going" rather than "fail the whole campaign".
- If a recipient's write still fails after retrying, that recipient's true
  outcome (was the message actually sent by `pulse-wpp`?) may not match
  what's recorded (or not recorded at all, staying `pending`). This is a
  known gap, not fully closed by this change — recording delivery is not
  transactional with sending it, and can't be made so without a durable
  outbox/idempotency key on the `pulse-wpp` side.
- **Consider Redis for the queue connection** as a follow-up. The root
  contention exists because the queue driver (`database`) and the app share
  one SQLite file; moving `QUEUE_CONNECTION` to Redis removes that specific
  source of lock contention entirely rather than retrying around it. This
  ADR's retry is a mitigation, not a replacement for that larger change —
  revisit if lock errors keep showing up in logs after this lands.
