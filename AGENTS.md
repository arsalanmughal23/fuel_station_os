# Agent Instructions & Context

## Welcome
You are contributing to **FuelStationOS**. To maintain a lean context window and avoid hallucinations, project knowledge is strictly organized into the following concise documents:

1. **`PRD.md`**: What we are building and core requirements.
2. **`Architecture.md`**: Tech stack, ledger principles, and database schema.
3. **`Decisions.md`**: Key architectural rules and design decisions (DO NOT violate these).
4. **`Tasks.md`**: Progress tracker (Always check this to see what is Done, In Progress, and Pending).
5. **`fuelstationos_erd_v4.mermaid`**: Visual ERD — the authoritative source for all table columns, FK relationships, and constraints. Check this when implementing models or migrations.

## Your Workflow
1. **Understand the Goal:** Briefly review `PRD.md` and `Architecture.md`.
2. **Check Status:** Review `Tasks.md` to see where to pick up work. Do not redo `[x]` done tasks.
3. **Follow Rules:** Adhere to constraints in `Decisions.md` (e.g., append-only ledgers, no UUIDs).
4. **Update Progress:** After completing a significant feature, mark the relevant item in `Tasks.md` as `[x]`. Add new tasks if you discover missing steps.
5. **Be Concise:** When writing code or responses, avoid over-explaining. Write clean, professional, self-documenting code.