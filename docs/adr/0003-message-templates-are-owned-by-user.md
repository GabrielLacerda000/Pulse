# MessageTemplate belongs to a User, unlike Lead

`Lead` currently has no `user_id` and no ownership concept — every logged-in user sees the same leads. `MessageTemplate`, introduced alongside it, does the opposite: each template has a `user_id` and is private to its author (a policy scopes queries to `auth()->id()`, no sharing between users).

This is a deliberate, temporary asymmetry, not an oversight: outreach wording is personal (each salesperson phrases things their own way), while lead data is still meant to be shared team-wide at this stage of the product. Lead ownership is expected to be added later as a separate piece of work; when it lands, revisit whether MessageTemplate should gain a sharing option (e.g. an `is_shared` flag) now that leads themselves are scoped per user too.
