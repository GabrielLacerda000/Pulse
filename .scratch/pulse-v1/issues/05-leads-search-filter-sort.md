# 05 — Leads search, filter & sort

**What to build:** From the Leads table, a user can search leads by free text, filter by attributes (status, category, city, etc.), and sort by column.

**Blocked by:** 01 — Lead CRUD & table view

**Status:** ready-for-agent

- [ ] Search box filters the leads table (e.g. by company name) without a full page reload (Inertia partial reload or equivalent)
- [ ] Filters available for at least status, category, and city
- [ ] Sortable columns on the leads table (at minimum: Empresa, Score, Status)
- [ ] Search/filter/sort state is reflected in the URL so results are shareable/bookmarkable
- [ ] Feature tests cover searching, filtering by status, and sorting
