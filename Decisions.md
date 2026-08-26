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
| 9 | **SQLite embedded** | Desktop app; no DB server needed; single file = easy backup |
| 10 | **Docker for all environments** | Reproducible dev + prod; no local PHP/Node setup required |
| 11 | **Short morph keys** (`'Tank'`, `'Product'`, `'FuelType'`) | Prevents FQCN leakage into the database |
| 12 | **`calculated_stock` kept as a persisted column** | Performance for list queries; synced via `StockTransaction::booted()` event with accessor fallback |
| 13 | **`current_balance` kept as a persisted column** | Performance; synced via `PaymentTransaction::booted()` event with accessor fallback |
| 14 | **`AppendOnlyLedger` trait uses model events, not global scope** | Global scope was broken (corrupted read queries); events (`updating`, `deleting`) are the correct enforcement pattern |
| 15 | **`payment_transactions` has no `updated_at`** | Intentional for append-only; uses `transacted_at` instead |
| 16 | **Services handle all business logic** | Controllers are thin HTTP adapters only; no domain logic in controllers or models |
| 17 | **`ScaleUnit` enum uses `Liter` (not `Litr`)** | Fixed typo; use `Liter` everywhere |
| 18 | **`nozzle_readings` FK uses `restrictOnDelete()`** | Prevents accidental data loss; consistent with rest of schema |
