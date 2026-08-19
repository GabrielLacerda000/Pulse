# 01 — Lead CRUD & table view

**What to build:** A user can create a lead manually, see all leads in a table, open a lead's detail page, edit its fields, and delete it. The lead's initial status is "Novo" (new).

**Blocked by:** None — can start immediately

**Status:** ready-for-agent

- [ ] `leads` migration exists with the fields from PRD §15 (`company_name`, `category`, `phone`, `email`, `website`, `instagram`, `has_website`, `has_instagram`, `city`, `state`, `status`, `type`, `score`, `rating`, `reviews_count`, `source`, `google_maps_url`, `source_created_at`, `notes`), avoiding SQLite-specific column types that would block a future Postgres migration
- [ ] `Lead` model with a `status` enum/cast covering: `new`, `contacted`, `conversation`, `meeting`, `proposal`, `won`, `lost`
- [ ] Thin `LeadController` (or resource controller) delegating to actions per PRD §17 architecture (actions for logic, controllers stay thin)
- [ ] Manual creation form (Nome da empresa, Categoria, Telefone, E-mail, Site, Instagram, Cidade, Estado, Notas) — new leads default to status "Novo"
- [ ] Leads index page (Inertia + Vue) rendered as a table with columns: Empresa, Categoria, Telefone, Cidade, Score, Tipo, Status, Ações
- [ ] Row actions: visualizar, editar, excluir (WhatsApp action deferred to ticket 3)
- [ ] Lead detail/show page displaying company info, status, presença digital block (site/instagram/score/rating), per PRD §14 layout
- [ ] Edit form updates all manual fields (notes editing UX is out of scope here — ticket 2 owns it)
- [ ] Delete removes the lead with a confirmation step
- [ ] Feature tests cover create, list, show, update, delete
