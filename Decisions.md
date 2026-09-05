# Key Architectural Decisions
**Do NOT violate these. They are final.**

---

| # | Decision | Rationale |
|---|---|---|
| 1 | **Single station scope** — no `companies`/`stations` tables | ERD v4 is single-station scoped |
| 2 | **Integer PKs, no UUIDs** | ERD v4 uses int PKs; SQLite has no distributed need |
| 3 | **No `shifts` table** | Removed in ERD v4; readings are standalone timed records |
| 4 | **No `pumps` table** | Nozzles attach directly to tanks |
| 5 | **No `delivery_items` table** | Delivery is one record per tank fill |
| 6 | **Polymorphic `stock_transactions`** | Single ledger for both fuel tanks and shop products |
| 7 | **`payment_transactions.sale_id` FK** | Revenue links to Sale, not NozzleReading |
| 8 | **Append-only ledgers** | Immutable audit trail; reversals via new rows only (never UPDATE/DELETE) |
| 9 | **SQLite embedded (WAL mode)** | Desktop app; no DB server needed; single file = easy backup; WAL for concurrency |
| 10 | **Docker for dev only** | Reproducible dev; **production uses single Tauri executable (no Docker)** |
| 11 | **Short morph keys** (`'Tank'`, `'Product'`, `'FuelType'`) | Prevents FQCN leakage into the database |
| 12 | **`calculated_stock` kept as a persisted column** | Performance for list queries; synced via `StockTransaction::booted()` event with accessor fallback |
| 13 | **`current_balance` kept as a persisted column** | Performance; synced via `PaymentTransaction::booted()` event with accessor fallback |
| 14 | **`AppendOnlyLedger` trait uses model events, not global scope** | Global scope was broken (corrupted read queries); events (`updating`, `deleting`) are the correct enforcement pattern |
| 15 | **`payment_transactions` has no `updated_at`** | Intentional for append-only; uses `transacted_at` instead |
| 16 | **Services handle all business logic** | Controllers are thin HTTP adapters only; no domain logic in controllers or models |
| 17 | **`ScaleUnit` enum uses `Liter` (not `Litr`)** | Fixed typo; use `Liter` everywhere |
| 18 | **`nozzle_readings` FK uses `restrictOnDelete()`** | Prevents accidental data loss; consistent with rest of schema |
| 19 | **Laravel lives in `backend/` directory** | Separation of concerns; Tauri bundles backend as sidecar resource |
| 20 | **FrankenPHP (via Laravel Octane) as PHP runtime** | Long-running worker; better performance than PHP-FPM; Tauri manages lifecycle via sidecar |
| 21 | **Tauri (Rust) manages frontend + sidecar** | Single executable installer; native OS integration; sidecar pattern for backend |
| 22 | **Database backup/restore via Tauri commands** | User-controlled disaster recovery via USB; no server dependency |
| 23 | **FrankenPHP worker communicates via stdin/stdout** | Sidecar IPC; Tauri starts/stops worker; health checks via HTTP endpoint |
| 24 | **Caddyfile configures FrankenPHP** | Native FrankenPHP config; CORS, health check, PHP server directive |

(End of file - total 24 decisions)
