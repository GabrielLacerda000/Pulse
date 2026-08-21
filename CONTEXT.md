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
