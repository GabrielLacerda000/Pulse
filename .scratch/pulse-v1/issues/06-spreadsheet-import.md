# 06 — Spreadsheet import (XLSX/CSV)

**What to build:** A user can upload an `.xlsx` or `.csv` file matching the external capture system's format (PRD §9), preview how many leads are new/duplicate/invalid, and confirm the import to create/update leads — without ever silently overwriting existing commercial status or notes.

**Blocked by:** 01 — Lead CRUD & table view, 02 — Notes management, 03 — Phone normalization & WhatsApp action

**Status:** ready-for-agent

- [x] Import UI: file picker for `.xlsx`/`.csv`, accessible from the Leads page ("botão de importar", PRD §13.1)
- [x] Parses the expected column set (PRD §9: Nome, Categoria, Telefone, E-mail, Site, Tem site, Instagram, Tem Instagram, Cidade, Estado, Status, Tipo, Score, Avaliação, Nº avaliações, Fonte, Google Maps, Criado em) and validates structure, surfacing a clear error if the structure doesn't match
- [x] Every phone is normalized (reusing ticket 03's service) before duplicate detection
- [x] Duplicate detection by normalized phone (PRD §11) — matching an existing lead marks the row as a duplicate rather than creating a new lead
- [x] Preview screen shows counts of novos / duplicados / inválidos before confirming (PRD §11 example), with a confirm action ("Importar N leads")
- [x] On confirm: new rows create leads with status "Novo"; rows matching an existing lead update only source-derived fields (Tipo, Score, Avaliação, Nº avaliações, Fonte, Google Maps, Tem site, Tem Instagram, Criado em) and never overwrite the existing `status` or `notes` (PRD §18)
- [x] Feature tests cover: first-time import creating leads, re-import of a row whose lead has an advanced status (asserting status is preserved), re-import preserving existing notes, and a row with invalid/missing required data being reported as invalid rather than crashing the import
