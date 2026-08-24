# Pulse

A CRM for tracking prospects and moving them through a sales pipeline, with WhatsApp as the primary outreach channel.

## Language

**Lead**:
A prospect tracked by the CRM — a company or contact with a status in the sales pipeline (`new`, `contacted`, `conversation`, `meeting`, `proposal`, `won`, `lost`). Currently has no owner (`user_id`); ownership will be added later, outside this context's current scope.
_Avoid_: Contact, prospect, deal

**MessageTemplate**:
A reusable, user-authored piece of outreach text containing variables, used to prefill a message before sending it through a channel (currently only WhatsApp). Belongs to exactly one User; not shared between users.
_Avoid_: WhatsAppTemplate, message, script

**Channel**:
The medium a MessageTemplate is meant for (e.g. `whatsapp`). Stored per template even though only one channel exists today, so the concept doesn't need to be retrofitted later.

**Variable**:
A `{name}`-style placeholder inside a MessageTemplate's text that gets substituted with a value from a specific Lead's fields (e.g. `{nome}` → `company_name`, `{cidade}` → `city`) when the template is used against that lead. A Variable with no corresponding value on the Lead resolves to an empty string, never a literal placeholder.
_Avoid_: placeholder (fine informally, but code/glossary should say Variable), token, merge field

**Campaign**:
A bulk WhatsApp outreach run: a named, user-owned set of Leads (fixed at creation time — see "Lead snapshot") paired with a pool of candidate messages, sent out one at a time in the background with a randomized delay between sends. Lifecycle: `draft` (being configured, nothing sent yet) → `running` (a queued Job is working through the snapshot) → `completed` (every lead in the snapshot has been attempted) or `failed` (aborted mid-run, e.g. WhatsApp session logged out). Belongs to exactly one User.
_Avoid_: blast, broadcast, batch

**Lead snapshot**:
The fixed list of Leads a Campaign will message, captured at the moment the Campaign is created (not a live filter re-evaluated at send time). A Lead's status changing after the Campaign is created does not add or remove it from the snapshot. Only Leads with a phone number [[NormalizePhoneAction]] can validate are selectable.

**CampaignMessage**:
One candidate message text in a Campaign's message pool, containing Variables the same way a MessageTemplate does. Authored either by typing directly into the campaign, or by copying a MessageTemplate's text in as a starting point — once copied, it is a plain, independent text with no ongoing link back to the MessageTemplate it came from (editing one does not affect the other).
_Avoid_: template (a CampaignMessage is not a MessageTemplate and isn't reusable outside the campaign), variant

**CampaignRecipient**:
The record of one Lead's outcome within a Campaign: which CampaignMessage was drawn for them (with Variables already substituted, i.e. the exact text that was — or would be — sent), and a status: `pending` (not yet attempted), `sent`, or `failed` (with the pulse-wpp error code, if the send was attempted and rejected). One CampaignRecipient per Lead in the Lead snapshot. Drives both the live progress count and the final success/failure summary.
_Avoid_: CampaignLead (drop the underlying Lead concept once it's in the campaign — call it a Recipient), send, delivery
