# 02 — Notes management

**What to build:** From a lead's detail page, a user can add and edit commercial notes independently of the main edit form. Notes are freeform text tied to a lead and are never touched by anything except explicit user action.

**Blocked by:** 01 — Lead CRUD & table view

**Status:** ready-for-agent

- [ ] Notes block on the lead detail page (per PRD §14 layout) with its own save action, separate from the general lead-edit form
- [ ] Saving notes does not require submitting/validating the rest of the lead's fields
- [ ] Empty notes render an empty/placeholder state, not an error
- [ ] Feature tests cover adding notes to a lead with no notes and editing existing notes
