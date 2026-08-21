# WhatsApp message templates stay client-side (wa.me links)

Adding multiple, variable-driven MessageTemplates for outreach raised the option of sending WhatsApp messages server-side via a provider API (Twilio, Meta Cloud API, Evolution API, etc.), which would enable delivery tracking and automation. We decided against it: the app keeps using `wa.me/{phone}?text=...` deep links built client-side, exactly like the existing single-message button, just with the text now coming from a chosen MessageTemplate instead of a hardcoded string.

Rationale: a provider integration requires credentials, a queue, and webhook handling that don't exist anywhere in the app yet, and the user explicitly does not want to depend on the number being saved as a WhatsApp contact — the `wa.me` link already satisfies that without any infrastructure. If real programmatic sending is ever needed, this can be revisited, but it's a separate, much larger piece of work than "let the user pick which text to prefill."
