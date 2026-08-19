# 04 — Pipeline Kanban with drag & drop

**What to build:** A Kanban view of the pipeline (PRD §5) where each column is a status (Novo, Contatado, Em conversa, Reunião, Proposta, Ganho, Perdido). Dragging a lead's card to another column updates its commercial status.

**Blocked by:** 01 — Lead CRUD & table view

**Status:** ready-for-agent

- [ ] Pipeline page renders one column per status with the leads currently in that status
- [ ] Each card shows at minimum: Nome da empresa, Categoria, Telefone, Score (PRD §5)
- [ ] Vue.Draggable Next (or equivalent) wired up for drag & drop between columns
- [ ] Dropping a card into a new column calls the backend to update that lead's `status`, and the UI reflects the new column immediately
- [ ] Card actions: WhatsApp (reuses ticket 03's action), Visualizar (links to detail page)
- [ ] Feature test covering a status update triggered by moving a lead between columns
