# 03 — Phone normalization & WhatsApp action

**What to build:** Any lead with a valid phone number gets a "WhatsApp" action (on the list, and on the detail page) that opens `wa.me` with a pre-filled default message. Leads without a valid phone have the action disabled.

**Blocked by:** 01 — Lead CRUD & table view

**Status:** ready-for-agent

- [ ] Phone normalization service/action: strips spaces, parentheses, hyphens, and non-numeric characters; applies default country code `55` (Brazil) when the number lacks one
- [ ] Normalized phone is what's used to build the `wa.me` link (PRD §6): `https://wa.me/<normalized>?text=<encoded message>`
- [ ] Default message text (PRD §7) is URL-encoded via `encodeURIComponent`-equivalent before being placed in the link
- [ ] "Abrir WhatsApp" action appears on the leads table and the lead detail page, opening in a new tab/window — no automatic sending, user reviews and sends manually
- [ ] Action is disabled/hidden when the lead has no phone or the phone fails normalization
- [ ] Unit tests for the normalization service covering formatted input, already-normalized input, and numbers missing a country code
- [ ] Feature test asserting the WhatsApp link is present for a lead with a phone and absent/disabled for a lead without one
