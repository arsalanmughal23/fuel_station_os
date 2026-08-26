# Tasks — Progress Tracker
**Last Audit:** August 10, 2026 | **Overall Progress: ~45%**

## Status Legend
| Symbol | Meaning |
|---|---|
| `[x]` | **Done** — implemented and verified |
| `[/]` | **Partial** — started but incomplete |
| `[ ]` | **Pending** — not started |
| `[-]` | **Blocked** — depends on another pending task |

---

## Key Design Decisions
| Decision | Rationale |
|---|---|
| Single station (no `companies`/`stations`) | ERD v4 is single-station scoped |
| `int id PK` (no UUIDs) | ERD v4 uses int PKs; SQLite has no distributed need |
| No `shifts` table | Removed in ERD v4; readings are standalone timed records |
| No `pumps` table | ERD v4: nozzles attach directly to tanks |
| No `delivery_items` table | Delivery is one record per tank fill in ERD v4 |
| Polymorphic `stock_transactions` | Single ledger for both fuel tanks and shop products |
| `payment_transactions.sale_id` FK | Revenue links to Sale (not NozzleReading) |
| SQLite embedded | Desktop app; no DB server needed; single file = easy backup |
| Docker | Reproducible dev + prod; no local PHP/Node setup required |
| Append-only ledgers | Immutable audit trail; reversals via new rows only |

---

## P0 — Critical Fixes (Do These First)
- `[x]` Fix `AppendOnlyLedger` broken global scope → now uses model events
- `[x]` Register morph maps in `AppServiceProvider` (`Tank`, `Product`, `FuelType`)
- `[x]` Fix `Sale` model wrong FK (`customer_id` → `account_id`) and relationship
- `[x]` Add missing enum casts on `PaymentTransaction` (`payment_method`, `status`)
- `[/]` Install & configure Sanctum + Spatie Permission — migrations published; **User model missing `HasApiTokens` + `HasRoles`**
- `[ ]` Create `AuthController` (login/logout) — file does not exist
- `[ ]` Register API routes in `bootstrap/app.php` — `routes/api.php` never loaded; **all endpoints unreachable**

---

## Phase 1 — Docker & Infrastructure
- `[x]` `docker-compose.yml` with `app`, `nginx`, `frontend`, `queue` services
- `[x]` PHP 8.4 Dockerfile with Composer
- `[x]` Node 20 Dockerfile for Nuxt 3 (`pnpm install --frozen-lockfile`)
- `[x]` Nginx config routing API → Laravel, `/` → Nuxt
- `[x]` Prod multi-stage Dockerfile with OPcache and non-root user
- `[x]` PHP-FPM port 9000 NOT exposed to host
- `[x]` Prod compose standalone (not dev overlay)
- `[x]` `.dockerignore` exclusions
- `[/]` Composer layer caching — fixed in prod; **dev Dockerfile still copies all files before `composer install`**
- `[/]` Volume conflict in dev compose — simplified; vendor bind still redundant
- `[/]` Queue worker migration wait — polls; entrypoint does not run migrations first
- `[ ]` Nginx SSL/TLS + rate limiting
- `[ ]` Fix `NUXT_PUBLIC_API_BASE_URL` env duplication in compose
- `[ ]` Nginx `server_name localhost` → support custom domains

---

## Phase 2 — Database & Models

### 2.1 Package Setup
- `[x]` `laravel/sanctum` installed and configured
- `[x]` `spatie/laravel-permission` installed and configured
- `[x]` Vendor migrations published

### 2.2 Migrations
- `[x]` All 21 ERD v4 tables created in correct dependency order
- `[x]` `price_history` table
- `[x]` XOR SQLite triggers on `sale_items` and `stock_transactions`
- `[x]` Polymorphic indexes (`st_stockable_idx`, `sa_stockable_idx`, `ph_priceable_idx`)
- `[x]` Morph short-key conversion migration
- `[x]` `nozzle_readings` uses `restrictOnDelete()` on FKs
- `[x]` `stock_adjustments` `down()` method fixed (invalid SQL)
- `[x]` `payment_transactions` — has `timestamps()` + append-only enforced via SQLite triggers (`BEFORE UPDATE`/`BEFORE DELETE`)

### 2.3 Models
- `[x]` `AppendOnlyLedger` trait fixed (model events)
- `[x]` `HasSlug` trait for `FuelType`, `Product`
- `[x]` `Sale` — correct `account_id` FK + `account()` relation
- `[x]` `PaymentTransaction` — enum casts for `payment_method`, `status`
- `[x]` `Product.current_stock` accessor fallback to `sum()`
- `[x]` `SaleItem.unit` cast to `ScaleUnit::class`
- `[x]` `ScaleUnit::Liter` (typo `Litr` fixed)
- `[x]` `UserFactory` generates `username`
- `[/]` `Tank` — `calculated_stock` synced via event; **list queries may still N+1 without eager loading**
- `[x]` Fix mass assignment — `stockable_type`/`stockable_id` removed from `StockTransaction.$fillable`
- `[ ]` `Account.UPDATED_AT = null` — still conflicts with `timestamps()` column; fix or document intentionally
- `[ ]` `NozzleReading` — add `hasMany(StockTransaction)` for reversal support
- `[x]` Morph short-key conversion — Fixed via `Relation::morphMap` in `AppServiceProvider` (no migration needed)

### 2.4 Enums
- `[x]` `AdjustmentType`, `PaymentMethod`, `PaymentCategory`, `PaymentStatus`
- `[x]` `PurchaseOrderStatus`, `ScaleUnit` (formerly `ProductUnit`)

### 2.5 Seeders
- `[ ]` `RoleSeeder` — create roles: `admin`, `manager`, `staff`
- `[ ]` `DatabaseSeeder` — call `RoleSeeder` + seed Owner account + sample FuelType, Tank, Nozzle, TankCalibration

---

## Phase 3 — Service Layer (Implement in This Order)
- `[ ]` **1. `StockTransactionService`** — `append()`, `reverse()`, balance locking
- `[ ]` **2. `PaymentTransactionService`** — `append()`, `reverse()`
- `[ ]` **3. `FuelTypeService`** — CRUD + price update → `PriceHistory` dual-write
- `[ ]` **4. `TankCalibrationService`** — linear interpolation for dip chart
- `[ ]` **5. `AccountService`** — CRUD; balance derived from ledger
- `[ ]` **6. `ProductService`** — CRUD + price update → `PriceHistory` dual-write
- `[ ]` **7. `DeliveryService`** — write stock ledger entry on delivery
- `[ ]` **8. `NozzleReadingService`** — compute liters sold; write stock + payment ledger entries
- `[ ]` **9. `SaleService`** — orchestrate sale items, stock, payments
- `[ ]` **10. `StockAdjustmentService`** — write stock ledger entry
- `[ ]` **11. `DeepReadingService`** — record dip; compute variance
- `[ ]` **12. `PurchaseOrderService`** — CRUD; status transitions
- `[ ]` **13. `ReportService`** — Dashboard KPIs + aggregations

---

## Phase 4 — API Layer
- `[x]` `routes/api.php` route definitions with Sanctum middleware
- `[/]` API controllers — 13 entity controllers exist; **`AuthController` missing**
- `[/]` Form Requests (Store) — 12 Store requests with basic rules
- `[/]` Form Requests (Update) — 5 exist (`FuelType`, `Tank`, `Nozzle`, `Product`, `Account`); **missing `Sale`, `PurchaseOrder`**
- `[ ]` Form Requests (Auth) — no `LoginRequest`
- `[ ]` API Resources — `app/Http/Resources/` is empty
- `[ ]` Model Policies — `app/Policies/` is empty
- `[ ]` Wire authorization checks in controllers/policies

---

## Phase 5 — Frontend (Nuxt 3)
- `[x]` API connectivity test page (`frontend/pages/index.vue`)
- `[ ]` Install Pinia + `@vueuse/nuxt` dependencies
- `[ ]` `useApi` composable + auth store
- `[ ]` Entity stores: `tanks`, `products`, `sales`, `accounts`, `dashboard`
- `[ ]` Auth flow — Login page, token storage
- `[ ]` All CRUD pages and Dashboard KPIs

---

## Phase 6 — Testing
- `[ ]` Feature tests for all API endpoints
- `[ ]` Unit tests for all Service classes
- `[ ]` Constraint tests: `SaleItem` XOR FK, `StockTransaction` single-FK, ledger immutability, derived balance correctness
- `[ ]` CI pipeline (GitHub Actions)

---

## Phase 7 — Desktop Wrapper (Tauri)
- `[ ]` `frontend/src-tauri/` scaffold
- `[ ]` `build-desktop.sh`
- `[ ]` IPC strategy + auto-updater

---

## DRY / Refactor (Lower Priority)
- `[x]` `HasSlug` trait extracted for `FuelType` + `Product`
- `[ ]` Extract SQLite trigger blocks to `MigrationHelper`
- `[ ]` Extract DB driver check helper (repeated `getDriverName() !== 'sqlite'`)
- `[ ]` Centralize stock sign convention in `StockTransactionService`
- `[ ]` Define service interfaces (`LedgerWriterInterface`) + DI bindings in `AppServiceProvider`

---

## Security Checklist
- `[x]` SQL injection protection (Eloquent ORM)
- `[x]` PHP-FPM not exposed to host
- `[/]` Authentication — packages installed; controller + User traits missing
- `[/]` Input validation — Form Requests exist but incomplete
- `[/]` Mass assignment — `stockable_type/id` still in `StockTransaction.$fillable`
- `[ ]` Authorization (RBAC) — Spatie installed; no policies, seeder, or role checks
- `[ ]` CSRF / API tokens — not wired end-to-end
- `[ ]` Remove `git`/`curl` from dev Docker image
- `[ ]` `APP_KEY` guidance in `.env.example`

---

## Production Readiness Checklist

### Infrastructure
- `[x]` PHP `Dockerfile.prod` — multi-stage, no git/curl, OPcache enabled
- `[x]` PHP-FPM port 9000 NOT exposed to host
- `[x]` Production compose is standalone (not an overlay of dev)
- `[ ]` Nginx SSL/TLS configured
- `[ ]` Nginx rate limiting (`limit_req_zone`)
- `[ ]` Log rotation for nginx and Laravel logs
- `[ ]` SQLite database on a named volume with scheduled backup
- `[ ]` Docker secrets or env injection from secrets manager

### Application
- `[x]` `laravel/sanctum` installed
- `[x]` `spatie/laravel-permission` installed
- `[x]` Morph maps registered
- `[x]` `AppendOnlyLedger` trait fixed
- `[x]` DB-level XOR constraints on `sale_items` and `stock_transactions`
- `[ ]` Spatie roles seeded
- `[ ]` All endpoints protected with `auth:sanctum` (routes defined but not registered)
- `[ ]` All endpoints have Form Request validation (partial)
- `[ ]` All endpoints return API Resources
- `[ ]` `APP_DEBUG=false` in production
- `[ ]` `APP_KEY` set and >= 32 chars
- `[ ]` `config:cache`, `route:cache`, `view:cache` run on deploy
- `[ ]` `AuthController` implemented
- `[ ]` API routes registered in `bootstrap/app.php`
- `[ ]` Service business logic implemented

### Testing
- `[ ]` Feature tests for all API endpoints
- `[ ]` Unit tests for all Service classes
- `[ ]` Constraint tests (append-only, XOR FK, balance correctness)
- `[ ]` CI pipeline (GitHub Actions) running tests on every push

### Monitoring & Operations
- `[ ]` Error tracking (Sentry or Bugsnag)
- `[ ]` Database backup schedule (`spatie/laravel-backup`)
- `[ ]` Queue failure alerting

### Tauri (Desktop Wrapper)
- `[ ]` `frontend/src-tauri/` with `tauri.conf.json`, `main.rs`, `Cargo.toml`
- `[ ]` `build-desktop.sh` working
- `[ ]` IPC communication strategy defined
- `[ ]` Auto-updater configured
- `[ ]` App icon set

---

## Recommended Next Steps (Priority Order)
1. `[ ]` Register `routes/api.php` in `bootstrap/app.php`
2. `[ ]` Create `AuthController` + add `HasApiTokens` / `HasRoles` to `User` model
3. `[ ]` Create `RoleSeeder` + update `DatabaseSeeder`
4. `[ ]` Fix `StockTransaction.$fillable` — remove `stockable_type`/`stockable_id`
5. `[ ]` Implement `StockTransactionService::append/reverse`
6. `[ ]` Implement `PaymentTransactionService::append/reverse`
7. `[ ]` Implement domain services (`DeliveryService`, `NozzleReadingService`, `SaleService`)
8. `[ ]` Generate all API Resources
9. `[ ]` Create Model Policies + wire authorization
10. `[ ]` Write feature tests for auth + ledger correctness
11. `[ ]` Frontend — Pinia auth store, `useApi`, login page
12. `[ ]` Tauri desktop wrapper (after API is stable)
