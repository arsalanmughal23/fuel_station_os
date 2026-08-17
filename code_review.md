# Fuel Station OS — Comprehensive Professional Code Review

**Reviewer:** Antigravity (AI Code Review)
**Date:** August 2026
**Codebase:** Laravel 12 + Nuxt 3 + SQLite + Docker
**Progress audit:** August 10, 2026 — statuses below reflect actual codebase state

---

## 0. Progress Tracker

> **For AI agents:** Read this section first, then `sub_plan.md` and `implementation_plan.md`. Continue from the first ⏳ or 🔄 item in **Recommended next steps**.

### Status legend

| Symbol | Meaning |
|--------|---------|
| ✅ | **Done** — implemented and verified in codebase |
| 🔄 | **Partial** — scaffolded or started but incomplete |
| 🚧 | **In progress** — actively being worked on |
| ⏳ | **Pending** — not started |
| ❌ | **Blocked** — cannot proceed until a dependency is done |

### Overall completion (August 10, 2026)

| Area | Was (review) | Now | Notes |
|------|--------------|-----|-------|
| Critical bug fixes (P0) | 0/5 | **4/5 ✅ + 1 🔄** | Auth integration still incomplete |
| Backend scaffolding | ~10% | **~55%** | Controllers, requests, services exist as stubs |
| Service business logic | 0% | **~5%** | All services are thin `create()` wrappers |
| API wiring | 0% | **🔄 Broken** | `routes/api.php` exists but is **not registered** in `bootstrap/app.php`; `AuthController` missing |
| Auth & RBAC | 0% | **🔄 ~20%** | Packages + migrations installed; no traits, seeder, or controller |
| Frontend | ~1% | **~1%** | Still a single test page |
| Tests | 0% | **0%** | Only Laravel example stubs |
| Docker / prod | ~60% | **~75%** | Prod multi-stage done; dev Dockerfile still basic |
| Tauri desktop | 0% | **0%** | Not started |

### Master task list

#### P0 — Critical fixes (Section 4)

| ID | Task | Status | Notes / files |
|----|------|--------|---------------|
| 4.1 | Fix `AppendOnlyLedger` broken global scope | ✅ | Uses model events in `app/Models/Concerns/AppendOnlyLedger.php` |
| 4.2 | Register morph maps in `AppServiceProvider` | ✅ | `Tank`, `Product`, `FuelType` in `app/Providers/AppServiceProvider.php` |
| 4.3 | Fix `Sale` model wrong FK / relationship | ✅ | Uses `account_id` + `account()` in `app/Models/Sale.php`; migration has FK |
| 4.4 | Add missing enum casts on `PaymentTransaction` | ✅ | `payment_method`, `status` cast in `app/Models/PaymentTransaction.php` |
| 4.5 | Install Sanctum + Spatie Permission | 🔄 | In `composer.json`; migrations published; **User model missing `HasApiTokens` + `HasRoles`** |
| 4.5a | Create `AuthController` (login/logout) | ⏳ | Referenced in `routes/api.php` but file **does not exist** |
| 4.5b | Register API routes in `bootstrap/app.php` | ⏳ | `routes/api.php` never loaded — **API endpoints unreachable** |
| 4.5c | Publish/configure Sanctum middleware | 🔄 | `config/sanctum.php` exists; User traits + controller missing |

#### P1 — High priority fixes

| ID | Task | Status | Notes / files |
|----|------|--------|---------------|
| 4.6 | Resolve `calculated_stock` ambiguity | 🔄 | Column kept; updated via `StockTransaction::booted()` on create (not SQLite trigger); accessor fallback in `Tank.php` |
| 4.6a | Resolve `accounts.current_balance` sync | ✅ | Updated via `PaymentTransaction::booted()` + accessor fallback in `Account.php` |
| 4.6b | Resolve `products.current_stock` sync | ✅ | Updated via `StockTransaction::booted()` + accessor fallback in `Product.php` |
| 4.7 | Fix `nozzle_readings` cascade inconsistency | ✅ | Uses `restrictOnDelete()` in migration |

#### Backend scaffolding

| ID | Task | Status | Notes / files |
|----|------|--------|---------------|
| 5.1 | Service layer (13 planned services) | 🔄 | 15 files in `app/Services/` — all stubs (`create()` only); extras: `TankService`, `NozzleService` |
| 5.2 | API controllers | 🔄 | 13 entity controllers exist; **`AuthController` missing**; return raw models (no Resources) |
| 5.3 | Form Requests — Store | 🔄 | 12 Store requests exist with basic rules |
| 5.4 | Form Requests — Update | 🔄 | 5 Update requests (`FuelType`, `Tank`, `Nozzle`, `Product`, `Account`); missing for `Sale`, `PurchaseOrder` |
| 5.5 | Form Requests — Auth | ⏳ | No `LoginRequest` |
| 5.6 | API Resources | ⏳ | `app/Http/Resources/` directory empty |
| 5.7 | Model Policies | ⏳ | No files in `app/Policies/` |
| 5.8 | RoleSeeder + DatabaseSeeder | ⏳ | Only test user in `database/seeders/DatabaseSeeder.php` |
| 5.9 | Events & Listeners | ⏳ | Not created |
| 5.10 | XOR DB constraints | ✅ | SQLite triggers in `sale_items` and `stock_transactions` migrations |
| 5.11 | Polymorphic indexes | ✅ | `st_stockable_idx`, `sa_stockable_idx`, `ph_priceable_idx` |
| 5.12 | Morph type short keys migration | ✅ | `2026_08_10_000000_convert_morph_types_to_short_keys.php` |
| 5.13 | `price_history` table | ✅ | `2026_08_09_000000_create_price_history_table.php` |
| 5.14 | `HasSlug` trait | ✅ | `app/Models/Concerns/HasSlug.php`; used by `FuelType`, `Product` |
| 5.15 | `UserFactory` username | ✅ | `database/factories/UserFactory.php` |

#### Service business logic (implement in this order)

| # | Service | Status | What's missing |
|---|---------|--------|----------------|
| 1 | `StockTransactionService` | 🔄 | Needs `append()`, `reverse()`, balance locking — currently just `create()` |
| 2 | `PaymentTransactionService` | 🔄 | Needs `append()`, `reverse()` — currently just `create()` |
| 3 | `FuelTypeService` | 🔄 | Needs price update + `PriceHistory` dual-write |
| 4 | `TankCalibrationService` | 🔄 | Needs linear interpolation for dip chart |
| 5 | `AccountService` | 🔄 | Stub only |
| 6 | `ProductService` | 🔄 | Stub only |
| 7 | `DeliveryService` | 🔄 | Should write stock ledger entry on delivery |
| 8 | `NozzleReadingService` | 🔄 | Should write stock + payment ledger entries |
| 9 | `SaleService` | 🔄 | Should orchestrate sale items, stock, payments |
| 10 | `StockAdjustmentService` | 🔄 | Should write stock ledger entry |
| 11 | `DeepReadingService` | 🔄 | Stub only |
| 12 | `PurchaseOrderService` | 🔄 | Stub only |
| 13 | `ReportService` | 🔄 | Returns empty array |
| — | `TankService`, `NozzleService` | 🔄 | Extra stubs (not in original plan) |

#### Model / migration fixes (Section 7)

| ID | Task | Status | Notes |
|----|------|--------|-------|
| 7.1 | `Sale.php` wrong customer FK | ✅ | Fixed — see 4.3 |
| 7.2 | `AppendOnlyLedger` broken scope | ✅ | Fixed — see 4.1 |
| 7.3 | `Tank` N+1 on `calculated_stock` | 🔄 | Persisted column + event sync; list queries may still N+1 without eager loading |
| 7.4 | `StockTransaction` `stockable_type/id` in `$fillable` | ⏳ | Still mass-assignable — security/mass-assignment risk |
| 7.5 | `FuelType` `slug` in `$fillable` | ✅ | Slug auto-generated via `HasSlug`; not in fillable |
| 7.6 | `Product` no-op `getCurrentStockAttribute` | ✅ | Now falls back to `sum()` when null |
| 7.7 | `Product`/`FuelType` use `boot()` not `booted()` | ✅ | Replaced with `HasSlug` trait |
| 7.8 | `Account` `UPDATED_AT = null` + timestamps column | ⏳ | Still present; low priority |
| 7.9 | `NozzleReading` missing `hasMany(StockTransaction)` | ⏳ | Has `hasOne` only; may need `hasMany` for reversals |
| 7.10 | `stock_adjustments` migration invalid `down()` SQL | ✅ | Fixed to `DROP INDEX IF EXISTS` |
| 7.11 | `payment_transactions` no `timestamps()` | ⏳ | By design for append-only; has `transacted_at` |
| 7.12 | Migration gap (skipped `100006`) | ⏳ | Cosmetic numbering only |
| 7.13 | `SaleItem` `unit` cast to `ScaleUnit` | ✅ | Fixed in `app/Models/SaleItem.php` |
| 7.14 | `ScaleUnit::Litr` typo | ✅ | Renamed to `Liter` |

#### Docker & infrastructure (Section 6)

| ID | Task | Status | Notes |
|----|------|--------|-------|
| 6.1 | Dev/prod PHP Dockerfiles differentiated | 🔄 | `Dockerfile.prod` is multi-stage + OPcache; dev `Dockerfile` still has git/curl |
| 6.2 | Composer layer caching | 🔄 | Prod copies `composer.json` + lock first; dev copies only `composer.json` (no lock) |
| 6.3 | `pnpm install --frozen-lockfile` | ✅ | In `docker/node/Dockerfile` |
| 6.4 | PHP-FPM port 9000 NOT exposed to host | ✅ | Removed from `docker-compose.yml` |
| 6.5 | Prod compose standalone (not dev overlay) | ✅ | `make prod` uses `docker-compose.prod.yml` only |
| 6.6 | Volume conflict (bind vs named storage) | 🔄 | Dev uses `./storage` bind mount; simplified but vendor bind still redundant |
| 6.7 | Queue worker waits for migrations | 🔄 | Queue command polls `migrate`; entrypoint does not run migrations |
| 6.8 | `.dockerignore` exclusions | ✅ | Includes md, mermaid, tests |
| 6.9 | Nginx SSL/TLS + rate limiting | ⏳ | Not configured |
| 6.10 | `NUXT_PUBLIC_API_BASE_URL` env duplication | ⏳ | Still set in compose `environment` (overrides `env_file`) |
| 6.11 | Nginx `server_name localhost` | ⏳ | Won't work with custom domains |
| 6.12 | `composer.json` boilerplate metadata | ✅ | Updated to `fuel-station-os/fuel-station-os` |

#### Security (Section 10)

| Area | Status | Notes |
|------|--------|-------|
| Authentication | 🔄 | Packages installed; controller, traits, route registration missing |
| Authorization (RBAC) | ⏳ | Spatie installed; no policies, seeder, or role checks |
| Input validation | 🔄 | Form requests exist but incomplete; auth routes unprotected |
| Mass assignment | 🔄 | `stockable_type/id` still fillable on `StockTransaction` |
| SQL injection | ✅ | Eloquent ORM |
| CSRF / API tokens | 🔄 | Designed for token auth; not wired end-to-end |
| Docker attack surface | 🔄 | Prod image improved; dev still has git/curl |
| Env / secrets | ⏳ | `APP_KEY` guidance in `.env.example` only |

#### Frontend (Section 8)

| ID | Task | Status |
|----|------|--------|
| 8.1 | Pinia + `@vueuse/nuxt` dependencies | ⏳ |
| 8.2 | `useApi` composable + auth store | ⏳ |
| 8.3 | Entity stores (tanks, products, sales, accounts) | ⏳ |
| 8.4 | Auth flow (login page, token storage) | ⏳ |
| 8.5 | CRUD pages / components | ⏳ |
| 8.6 | API connectivity test page | ✅ | `frontend/pages/index.vue` |

#### Testing & CI

| ID | Task | Status |
|----|------|--------|
| T.1 | Feature tests for API endpoints | ⏳ |
| T.2 | Unit tests for services | ⏳ |
| T.3 | Constraint tests (append-only, XOR, balances) | ⏳ |
| T.4 | CI pipeline (GitHub Actions) | ⏳ |

#### Tauri desktop

| ID | Task | Status |
|----|------|--------|
| D.1 | `frontend/src-tauri/` scaffold | ⏳ |
| D.2 | `build-desktop.sh` | ⏳ |
| D.3 | IPC strategy + auto-updater | ⏳ |

#### DRY / refactor (Section 9 — lower priority)

| ID | Task | Status |
|----|------|--------|
| 9.1 | Extract SQLite trigger blocks to helper | ⏳ |
| 9.2 | Extract DB driver check helper | ⏳ |
| 9.3 | Centralize stock sign convention in service | ⏳ |
| 9.4 | Service interfaces + DI bindings | ⏳ |

### Recommended next steps (priority order)

1. ⏳ **Register API routes** — add `api: __DIR__.'/../routes/api.php'` to `bootstrap/app.php`
2. ⏳ **Create `AuthController`** + add `HasApiTokens` / `HasRoles` to `User` model
3. ⏳ **Create `RoleSeeder`** + update `DatabaseSeeder` with roles and sample domain data
4. 🔄 **Implement core ledger services** — `StockTransactionService::append/reverse`, `PaymentTransactionService::append/reverse`
5. 🔄 **Implement domain services** — `DeliveryService`, `NozzleReadingService`, `SaleService` (orchestration)
6. ⏳ **Generate API Resources** for all entities
7. ⏳ **Create Policies** + wire authorization in Form Requests
8. ⏳ **Write feature tests** for auth + ledger correctness
9. ⏳ **Frontend** — Pinia auth store, `useApi`, login page
10. ⏳ **Tauri** — desktop wrapper (after API is stable)

---

## 1. Executive Summary

Fuel Station OS is a well-conceived, purpose-built desktop fuel management system with a commendably thoughtful architectural foundation. The data model and append-only ledger design demonstrate genuine domain expertise — the ERD is logical, the polymorphic relationships are well-placed, and the decision to use SQLite for a single-station desktop app is pragmatic and appropriate.

**Current state (August 10, 2026):** Significant scaffolding progress since the original review (~25% → **~45% overall**). Critical model bugs (AppendOnlyLedger, Sale FK, morph maps, enum casts, cascade rules, XOR triggers) are **fixed**. Backend structure exists: 15 service stubs, 13 API controllers, 17 form requests, and protected route definitions in `routes/api.php`. However, **API routes are not registered in `bootstrap/app.php`**, **`AuthController` is missing**, services contain **no real business logic**, and there are **zero application tests**. The frontend remains a single test page. See **Section 0** for the full task-by-task status.

---

## 2. Strengths

- **ERD quality is genuinely excellent** — 21 tables are fully thought-through, with proper polymorphic relationships (`stockable`, `priceable`), reversal-pattern FKs on ledger tables, and snapshot fields (unit price, liters_sold) on line items.
- **Append-only ledger architecture is sound** — The dual enforcement strategy (PHP trait + SQLite triggers) is the right approach. Using reversal rows instead of deletes is a mature, audit-trail-preserving pattern.
- **Polymorphic design is clean** — Using `stockable` morph for both `Tank` and `Product` on stock ledger/adjustments is elegant and avoids table-per-entity proliferation.
- **Enum usage is correct** — PHP 8.1+ backed enums with `label()` methods (e.g., `ScaleUnit`) are the modern way. All enum casts in models are properly configured.
- **Docker setup is functionally solid** — Health checks, named volumes, resource limits on workers, delegated bind mounts, and the Makefile DX are all well above average for a side-project setup.
- **Migration order is correct** — Dependencies are resolved properly; no forward-reference FK issues.
- **Decimal precision is intentional** — Using `decimal:3` for liters, `decimal:2` for money, `decimal:4` for unit price is domain-appropriate.
- **Non-root container user** — Both Dockerfiles correctly switch to `www-data` before `EXPOSE`.
- **Makefile DX is outstanding** — Color-coded output, dependency checks, prod/dev separation, `make help` auto-documentation — this is production-grade developer ergonomics.
- **Implementation plan is thorough** — The v3.1 plan with phased execution, enum tables, migration order, and design-decision rationale is a high-quality technical document.

---

## 3. Weaknesses

### Critical
- ✅ **~~No authentication implemented~~** → **🔄 Partial** — Sanctum routes defined in `routes/api.php`, but `AuthController` missing, `User` lacks `HasApiTokens`, and **`bootstrap/app.php` does not load `routes/api.php`**
- ✅ **~~No Service layer~~** → **🔄 Partial** — 15 service files exist in `app/Services/` but all are stubs (`create()` only)
- ✅ **~~No API controllers~~** → **🔄 Partial** — 13 entity controllers exist; `AuthController` missing
- ✅ **~~No Form Request validation~~** → **🔄 Partial** — 17 request files exist; missing auth + some Update requests
- ⏳ **No API Resources** — `app/Http/Resources/` directory does not exist
- ⏳ **Zero application tests** — Only Laravel scaffolding stubs remain
- ✅ **~~Morph map NOT registered~~** — **Done** in `AppServiceProvider::boot()`

### High
- ✅ **~~`AppendOnlyLedger` trait is fundamentally broken~~** — **Done** — uses model events, not global scope
- 🔄 **`tanks.calculated_stock` is persisted but never updated** → **Partial** — updated via `StockTransaction::booted()` on create; accessor fallback exists; no SQLite trigger
- ✅ **~~`Sale.customer()` uses wrong FK and wrong model~~** — **Done** — uses `account_id` + `account()`
- ✅ **~~`accounts.current_balance` is stored but never updated~~** — **Done** — updated via `PaymentTransaction::booted()` + accessor
- ✅ **~~Inconsistent cascade rules~~** — **Done** — `nozzle_readings` uses `restrictOnDelete()`
- ✅ **~~`PaymentTransaction` missing enum casts~~** — **Done**
- 🔄 **~~No Sanctum, no RBAC packages installed~~** → **Partial** — packages in `composer.json`; User traits + seeder not wired
- ⏳ **Frontend is a placeholder** — One page, zero components/stores/auth

### Medium
- 🔄 **Dev and Prod PHP Dockerfiles are identical** → **Partial** — `Dockerfile.prod` is multi-stage with OPcache; dev `Dockerfile` unchanged
- 🔄 **Composer layer caching broken** → **Partial** — fixed in prod; dev still copies only `composer.json` without lock
- ⏳ **`FuelType.current_price` denormalization** — `FuelTypeService` is stub; no `PriceHistory` dual-write
- ✅ **~~`Product.boot()` not `booted()`~~** — **Done** — replaced with `HasSlug` trait
- ⏳ **`StockTransaction::UPDATED_AT = null` + `timestamps()`** — still present
- ⏳ **No Tauri files exist** — not started
- ✅ **~~SQL syntax error in migration `down()`~~** — **Done** — fixed in `stock_adjustments` migration

### Low
- ✅ **~~`composer.json` has default Laravel boilerplate~~** — **Done**
- ✅ **~~`ScaleUnit::Litr` typo~~** — **Done** — renamed to `Liter`
- ✅ **~~`SaleItem` casts `unit` as `'string'`~~** — **Done** — casts to `ScaleUnit::class`
- ⏳ **`NUXT_PUBLIC_API_BASE_URL` set in both `env_file` and `environment`** — still in compose
- ⏳ **Nginx `server_name localhost`** — unchanged

---

## 4. Priority Issues (Fix Immediately)

### P0 — Blockers

#### 4.1 Fix the `AppendOnlyLedger` trait's broken global scope — ✅ **DONE**

> Fixed. Trait now uses model events (`updating`, `deleting`) instead of a broken global scope. See `app/Models/Concerns/AppendOnlyLedger.php`.

**Current (broken):**
```php
protected static function bootAppendOnlyLedger(): void
{
    static::addGlobalScope('appendOnly', function (Builder $builder) {
        $where = [];
        foreach (static::getAppendOnlyColumns() as $column) {
            $where[$column] = 0;  // ← WHERE updated_at = 0 — always false
        }
        $builder->where($where);
    });
}
```

**Fix — use model events, not a global scope:**
```php
trait AppendOnlyLedger
{
    protected static function bootAppendOnlyLedger(): void
    {
        static::updating(function ($model) {
            throw new \RuntimeException(
                static::class . ' is append-only and cannot be updated.'
            );
        });

        static::deleting(function ($model) {
            throw new \RuntimeException(
                static::class . ' is append-only and cannot be deleted.'
            );
        });
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException(static::class . ' is append-only and cannot be updated.');
    }

    public function delete(): bool|null
    {
        throw new \RuntimeException(static::class . ' is append-only and cannot be deleted.');
    }
}
```

#### 4.2 Register Morph Maps in `AppServiceProvider` — ✅ **DONE**

> Fixed. Morph map registered for `Tank`, `Product`, `FuelType`. Migration `2026_08_10_000000_convert_morph_types_to_short_keys.php` converts existing FQCN values.

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\{Tank, Product, FuelType};

public function boot(): void
{
    Relation::morphMap([
        'Tank'     => Tank::class,
        'Product'  => Product::class,
        'FuelType' => FuelType::class,
    ]);
}
```

#### 4.3 Fix `Sale` model — wrong FK, wrong related model — ✅ **DONE**

> Fixed. Model uses `account_id`, `account()` relationship, correct `$fillable`. Migration includes nullable `account_id` FK.

```php
// WRONG — customer_id FK does not exist in the migration
protected $fillable = ['user_id', 'customer_id', ...];
public function customer(): BelongsTo
{
    return $this->belongsTo(User::class, 'customer_id');
}

// CORRECT — matches the ERD (account_id → accounts)
protected $fillable = ['user_id', 'account_id', 'total_amount',
                       'paid_amount', 'change_amount', 'payment_status', 'sale_date'];
public function account(): BelongsTo
{
    return $this->belongsTo(Account::class);
}
```

Also add `account_id` FK to the `sales` migration (nullable → `accounts`).

#### 4.4 Fix missing enum casts in `PaymentTransaction` — ✅ **DONE**

> Fixed. `payment_method` and `status` cast to enum types.

```php
// Add the missing casts:
protected function casts(): array
{
    return [
        'type'           => PaymentType::class,
        'category'       => PaymentCategory::class,
        'payment_method' => PaymentMethod::class,   // ← missing
        'status'         => PaymentStatus::class,   // ← missing
        'amount'         => 'decimal:2',
        'transacted_at'  => 'datetime',
    ];
}
```

#### 4.5 Install required packages — 🔄 **PARTIAL**

> Packages installed and migrations published. **Still pending:** `HasApiTokens` + `HasRoles` on `User`, `AuthController`, `RoleSeeder`, register `routes/api.php` in `bootstrap/app.php`.

```bash
composer require laravel/sanctum spatie/laravel-permission
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### P1 — High Priority

#### 4.6 Resolve `calculated_stock` ambiguity (choose one approach) — 🔄 **PARTIAL**

> Option B partially implemented: `StockTransaction::booted()` updates `tanks.calculated_stock` and `products.current_stock` on ledger insert. No SQLite trigger. Accessor fallback when column is null.

**Option A — Pure computed (always accurate):** Remove the `calculated_stock` column from the migration. The accessor already does `sum()`.

**Option B — Trigger-maintained (better for list performance):** Keep the column, add a SQLite trigger in the `stock_transactions` migration:

```sql
CREATE TRIGGER update_tank_stock_after_insert
AFTER INSERT ON stock_transactions
WHEN NEW.stockable_type = 'Tank'
BEGIN
    UPDATE tanks SET calculated_stock = (
        SELECT COALESCE(SUM(quantity), 0)
        FROM stock_transactions
        WHERE stockable_type = 'Tank' AND stockable_id = NEW.stockable_id
    ) WHERE id = NEW.stockable_id;
END;
```

#### 4.7 Fix inconsistent cascade in `nozzle_readings` — ✅ **DONE**

> Fixed. Both FKs use `restrictOnDelete()`.

```php
// Current — dangerous: deletes all readings when nozzle deleted
$table->foreignId('nozzle_id')->constrained()->onDelete('cascade');
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// Fixed — consistent with rest of schema
$table->foreignId('nozzle_id')->constrained()->restrictOnDelete();
$table->foreignId('user_id')->constrained()->restrictOnDelete();
```

---

## 5. Architecture Review

### 5.1 Overall Structure

The monorepo structure is practical for a small team:

```
/                  # Laravel root (API backend)
/frontend/         # Nuxt 3 app
/docker/           # Per-service Dockerfiles
/makefile          # Unified DX commands
```

**Missing:** No `docker/tauri/` directory and no `build-desktop.sh`. The Tauri desktop wrapper is entirely unimplemented. ⏳ **PENDING**

### 5.2 Domain Model Coherence

The domain model maps cleanly to the real-world problem:

```
FuelType → Tank → Nozzle → NozzleReading
                ↓              ↓ (ledger)
              Delivery    StockTransaction (polymorphic)
                ↓
           PurchaseOrder → Account
Product → SaleItem → Sale → Account
```

This is well-reasoned. The single `stock_transactions` table for both fuel tanks and shop products via `stockable` morph is the right design.

**Missing DB enforcement — XOR constraints:** ✅ **DONE** — SQLite triggers added in `sale_items` and `stock_transactions` migrations.

### 5.3 Service Layer — 🔄 **PARTIAL (stubs only)**

The plan lists 13 services; **15 stub files now exist** in `app/Services/` but none contain real business logic (all are thin `create()` wrappers). This remains the highest-priority implementation work.

**Recommended directory structure:**
```
app/Services/
├── Ledger/
│   ├── StockTransactionService.php
│   └── PaymentTransactionService.php
├── Operations/
│   ├── DeliveryService.php
│   ├── NozzleReadingService.php
│   └── DeepReadingService.php
├── Sales/
│   └── SaleService.php
├── Inventory/
│   ├── StockAdjustmentService.php
│   └── ProductService.php
└── Setup/
    ├── FuelTypeService.php
    ├── TankCalibrationService.php
    └── AccountService.php
```

---

## 6. Docker Setup Review

### 6.1 What Works

- Health check on `backend` service ✅
- Named volumes for storage isolation ✅
- Non-root user (`www-data`) ✅
- Resource limits on queue/scheduler workers ✅
- Multi-stage build in `node/Dockerfile.prod` ✅
- Separate prod compose file ✅

### 6.2 Critical Issues

**Issue 1: Dev and Prod PHP Dockerfiles are identical**

`docker/php/Dockerfile` and `docker/php/Dockerfile.prod` are byte-for-byte identical. A proper prod image needs:

```dockerfile
# Recommended Dockerfile.prod (multi-stage)
FROM php:8.4-fpm AS builder
RUN apt-get update && apt-get install -y \
    unzip libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_sqlite mbstring bcmath pcntl gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
COPY . .

FROM php:8.4-fpm AS final
# Only runtime dependencies — no git, no curl
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_sqlite mbstring bcmath pcntl gd opcache
COPY --from=builder /var/www/html /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
USER www-data
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

**Issue 2: Composer layer cache is broken**

Both Dockerfiles copy all app files *before* installing dependencies:
```dockerfile
COPY . .                  # ← invalidates cache on every source change
RUN composer install      # ← re-runs from scratch every time
```

**Fix (copy only manifests first):**
```dockerfile
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist
COPY . .
```

**Issue 3: `pnpm install` without `--frozen-lockfile`**

```dockerfile
# Can silently install different versions than pnpm-lock.yaml
RUN pnpm install

# Fix
RUN pnpm install --frozen-lockfile
```

**Issue 4: PHP-FPM port 9000 exposed to host**

```yaml
backend:
  ports:
    - "9000:9000"  # ← REMOVE THIS. FPM should only be reachable by nginx internally.
```

**Issue 5: Volume conflict in dev compose**

```yaml
backend:
  volumes:
    - ./:/var/www/html:delegated         # mount everything
    - ./vendor:/var/www/html/vendor      # redundant: already inside ./
    - backend_storage:/var/www/html/storage  # named volume overwrites bind-mounted storage/database
```

Named volume `backend_storage` and bind-mounted `./storage/database` conflict. Pick one strategy.

**Issue 6: `prod` Makefile target merges dev and prod compose**

```makefile
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

This applies dev bind mounts (e.g. `./:/var/www/html:delegated`) to production containers, overriding the baked image. The prod file should be **standalone**.

**Issue 7: Queue worker starts before migrations in prod**

In `docker-compose.prod.yml`, the queue worker has no initialization wait — it starts immediately after the backend health check passes, but before `php artisan migrate` runs (which is done in the entrypoint). Make the entrypoint run migrations before signaling readiness.

### 6.3 `.dockerignore` Issues

**Missing exclusions (add these):**
```
combined_prompt.txt
fuelstationos_erd_v4.mermaid
implementation_plan*.md
recommended-structure.txt
*.mermaid
tests/
```

---

## 7. Backend Code Review (Laravel)

### 7.1 Models — Issues Summary

| File | Issue | Severity | Status |
|------|-------|----------|--------|
| `Sale.php` | `customer_id` in `$fillable` and wrong relationship | Critical | ✅ Fixed |
| `AppendOnlyLedger.php` | Global scope corrupts reads | Critical | ✅ Fixed |
| `Tank.php` | `calculated_stock` accessor causes N+1 when listing | High | 🔄 Partial (event sync + accessor) |
| `StockTransaction.php` | `stockable_type`/`stockable_id` should not be in `$fillable` | Medium | ⏳ Pending |
| `FuelType.php` | `slug` is auto-generated but in `$fillable` | Medium | ✅ Fixed (HasSlug) |
| `Product.php` | `getCurrentStockAttribute()` is a no-op getter | Low | ✅ Fixed |
| `Product.php` | Uses `boot()` instead of `booted()` | Low | ✅ Fixed (HasSlug) |
| `Account.php` | `UPDATED_AT = null` but `timestamps()` creates the column | Low | ⏳ Pending |
| `NozzleReading.php` | Missing `hasMany(StockTransaction)` relationship | Medium | ⏳ Pending |

### 7.2 Migrations — Issues Summary

| Issue | Status |
|-------|--------|
| Missing polymorphic index on `stock_transactions` | ✅ Done (`st_stockable_idx`) |
| Missing polymorphic index on `stock_adjustments` | ✅ Done (`sa_stockable_idx`) |
| `payment_transactions` — no `timestamps()` call | ⏳ Pending (by design) |
| `nozzle_readings` — uses `onDelete('cascade')` inconsistently | ✅ Fixed |
| `stock_adjustments` `down()` method has invalid SQL | ✅ Fixed |
| Migration gap: `100006` is skipped | ⏳ Pending (cosmetic) |
| XOR triggers on `sale_items` / `stock_transactions` | ✅ Done |
| `price_history` table | ✅ Done |
| Morph short-key conversion migration | ✅ Done |

### 7.3 API Routes — 🔄 **PARTIAL**

> Route definitions exist in `routes/api.php` with Sanctum middleware, but **`bootstrap/app.php` does not register the API routes file** — endpoints are currently unreachable. `AuthController` is referenced but missing.

```php
// Current state
Route::get('/health', fn() => response()->json(['status' => 'ok']));
Route::prefix('v1')->group(function () { /* empty */ });
```

**Recommended full structure:**
```php
Route::get('/health', HealthCheckController::class);

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::apiResources([
            'fuel-types'      => FuelTypeController::class,
            'tanks'           => TankController::class,
            'nozzles'         => NozzleController::class,
            'products'        => ProductController::class,
            'accounts'        => AccountController::class,
            'purchase-orders' => PurchaseOrderController::class,
            'sales'           => SaleController::class,
        ]);

        Route::post('deliveries',        [DeliveryController::class, 'store']);
        Route::post('nozzle-readings',   [NozzleReadingController::class, 'store']);
        Route::post('deep-readings',     [DeepReadingController::class, 'store']);
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store']);
    });
});
```

### 7.4 Missing Critical Components

| Component | Status | Impact |
|-----------|--------|--------|
| `laravel/sanctum` | ✅ Installed | Auth possible once wired |
| `spatie/laravel-permission` | ✅ Installed | RBAC possible once wired |
| Service layer (13 services) | 🔄 Stubs only | No real business logic yet |
| API Controllers (18+) | 🔄 Partial | 13 exist; AuthController missing |
| Form Requests | 🔄 Partial | 17 files; missing auth + some Update |
| API Resources | ⏳ Not created | No response shaping |
| Events & Listeners | ⏳ Not created | No event-driven patterns |
| Model Policies | ⏳ Not created | No authorization |
| Seeders (RoleSeeder, etc.) | ⏳ Not created | Cannot bootstrap roles/data |
| API route registration | ⏳ Not done | `bootstrap/app.php` missing `api:` key |

---

## 8. Frontend Code Review (Nuxt 3) — ⏳ **PENDING (~1% complete)**

### 8.1 Current State

- 1 page — an API connectivity test button
- 1 layout — a `<header>` with one link
- 0 components, 0 composables, 0 Pinia stores
- No TypeScript interfaces, no CSS design system, no auth flow

### 8.2 What Is Correct

- `nuxt.config.ts` uses `runtimeConfig.public` correctly for the API URL
- `$fetch` is the correct Nuxt way to call APIs
- `nitro.preset: 'node-server'` is right for Docker deployment
- `typeCheck: false` with `strict: true` — acceptable during dev, enable for CI

### 8.3 What Must Be Built

**Required dependencies:**
```json
{
  "dependencies": {
    "@pinia/nuxt": "^0.5.0",
    "pinia": "^2.1.0",
    "@vueuse/nuxt": "^10.0.0",
    "@vueuse/core": "^10.0.0"
  }
}
```

**Composable pattern (start here):**
```typescript
// composables/useApi.ts
export function useApi() {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()

  const request = async <T>(
    endpoint: string,
    options: Parameters<typeof $fetch>[1] = {}
  ): Promise<T> => {
    return $fetch<T>(`${config.public.apiBaseUrl}/${endpoint}`, {
      ...options,
      headers: {
        Authorization: `Bearer ${authStore.token}`,
        Accept: 'application/json',
        ...options.headers,
      },
      onResponseError({ response }) {
        if (response.status === 401) authStore.logout()
      },
    })
  }

  return { request }
}
```

**Store structure needed:**
```
frontend/stores/
├── auth.ts       # session, token, user
├── dashboard.ts  # KPI snapshots
├── tanks.ts
├── products.ts
├── sales.ts
└── accounts.ts
```

---

## 9. SOLID & DRY Analysis

### 9.1 Single Responsibility

**✅ Models are data containers only.** No fat business logic in models.

**⚠️ Risk:** Without a Service layer enforced from the start, controllers will absorb business logic. Plan the Service layer now.

### 9.2 Open/Closed

**✅ Polymorphic design** (`stockable`, `priceable`) allows adding new entity types (e.g. `BulkContainer`) without modifying ledger tables — perfect OCP compliance.

**⚠️** The `AppendOnlyLedger` trait is not open for extension. If a model needs to allow specific field updates (e.g. `status`), there's no hook.

### 9.3 Interface Segregation / Dependency Inversion

**N/A — No interfaces yet.** When Services are created, bind them:
```php
// Define contract
interface LedgerWriterInterface {
    public function append(array $data): Model;
    public function reverse(Model $original, string $reason): Model;
}

// Bind in AppServiceProvider
$this->app->bind(LedgerWriterInterface::class, StockTransactionService::class);
```

### 9.4 DRY — Duplications Found

1. **Slug generation** — ✅ **DONE** — extracted to `HasSlug` trait in `app/Models/Concerns/HasSlug.php`
2. **SQLite trigger blocks** — ⏳ **PENDING** — still copy-pasted across migrations; extract to `MigrationHelper`
3. **DB driver check** — ⏳ **PENDING** — `if (Schema::getConnection()->getDriverName() !== 'sqlite')` still repeated
4. **Sign convention comments** — ⏳ **PENDING** — not centralized in `StockTransactionService` yet

---

## 10. Security Review

| Area | Status | Notes |
|------|--------|-------|
| Authentication | 🔄 PARTIAL | Packages installed; controller, traits, route registration missing |
| Authorization (RBAC) | ⏳ PENDING | Spatie installed; no policies, seeder, or role checks |
| Input Validation | 🔄 PARTIAL | Form requests exist but incomplete; API routes not loaded |
| Mass Assignment | 🔄 PARTIAL | `stockable_type/id` still in `$fillable` on `StockTransaction` |
| SQL Injection | ✅ SAFE | Eloquent ORM parameterizes all queries |
| XSS | ✅ N/A | JSON API, no HTML rendering |
| CSRF | 🔄 PARTIAL | Designed for token auth; not wired end-to-end |
| Port Exposure | ✅ FIXED | PHP-FPM port 9000 no longer exposed in dev compose |
| Env Security | ⏳ PENDING | `APP_KEY` not enforced in `.env.example` |
| Docker Security | 🔄 PARTIAL | Prod image improved; dev still has git/curl |
| SQLite Access | ✅ SAFE | Nginx root is `public/` only; DB file not web-accessible |

**The three critical security items (auth, RBAC, validation) must be implemented before any production or even internal staging deployment.**

---

## 11. Improvement Recommendations

### Immediate (Fix Before Feature Work)
1. ✅ Fix `AppendOnlyLedger` trait (see §4.1)
2. ✅ Register morph maps in `AppServiceProvider` (see §4.2)
3. ✅ Fix `Sale` model customer relationship (see §4.3)
4. ✅ Add missing enum casts to `PaymentTransaction` (see §4.4)
5. 🔄 Install Sanctum + Spatie (see §4.5) — packages done; wiring pending
6. ✅ Remove PHP-FPM port 9000 host exposure from `docker-compose.yml`
7. ⏳ **Register API routes in `bootstrap/app.php`** — NEW blocker
8. ⏳ **Create `AuthController` + User traits** — NEW blocker

### Short Term (Next Sprint)
7. 🔄 Differentiate dev/prod PHP Dockerfiles with multi-stage build — prod done, dev pending
8. 🔄 Fix Composer layer caching in both Dockerfiles — prod done, dev partial
9. ✅ Add `--frozen-lockfile` to Node Dockerfile
10. ✅ Add `HasSlug` trait (eliminate boot() duplication)
11. ✅ Add XOR constraint triggers for `sale_items` and `stock_transactions`
12. ✅ Add composite index on `(stockable_type, stockable_id)` for morph tables
13. ⏳ Create `RoleSeeder` + `DatabaseSeeder` with Owner account, sample FuelType/Tank/Nozzle
14. ⏳ Generate API Resources for all entities
15. ⏳ Create Model Policies + wire RBAC
16. ⏳ Implement service business logic (see Section 0 service table)

### Service Layer Build Order
```
1. StockTransactionService  (core ledger)
2. PaymentTransactionService
3. FuelTypeService          (price update + PriceHistory)
4. TankCalibrationService   (linear interpolation)
5. AccountService
6. ProductService
7. DeliveryService
8. NozzleReadingService
9. SaleService
10. StockAdjustmentService
11. DeepReadingService
12. PurchaseOrderService
13. ReportService
```

### Example — `StockTransactionService`

```php
// app/Services/Ledger/StockTransactionService.php
namespace App\Services\Ledger;

use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockTransactionService
{
    public function append(
        Model $stockable,
        float $quantity,
        string $unit,
        int $userId,
        array $sourceFK = [],
        ?string $remarks = null
    ): StockTransaction {
        return DB::transaction(function () use ($stockable, $quantity, $unit, $userId, $sourceFK, $remarks) {
            // Atomic balance calculation — lock last row
            $lastBalance = StockTransaction::where('stockable_type', get_class($stockable))
                ->where('stockable_id', $stockable->id)
                ->latest()
                ->value('balance_after') ?? 0;

            return StockTransaction::create(array_merge([
                'stockable_type' => get_class($stockable),
                'stockable_id'   => $stockable->id,
                'quantity'       => $quantity,
                'unit'           => $unit,
                'balance_after'  => $lastBalance + $quantity,
                'user_id'        => $userId,
                'remarks'        => $remarks,
            ], $sourceFK));
        });
    }

    public function reverse(
        StockTransaction $original,
        int $userId,
        string $reason
    ): StockTransaction {
        return $this->append(
            stockable: $original->stockable,
            quantity: -$original->quantity,
            unit: $original->unit,
            userId: $userId,
            sourceFK: ['reversed_transaction_id' => $original->id],
            remarks: "Reversal: {$reason}"
        );
    }
}
```

---

## 12. Production Readiness Checklist

### Infrastructure
- [x] PHP `Dockerfile.prod` — multi-stage, no git/curl, OPcache enabled
- [x] PHP-FPM port 9000 NOT exposed to host
- [x] Production compose is standalone (not an overlay of dev)
- [ ] Nginx with SSL/TLS configured
- [ ] Nginx rate limiting (`limit_req_zone`)
- [ ] Log rotation for nginx and Laravel logs
- [ ] SQLite database on a named volume with scheduled backup
- [ ] Docker secrets or env injection from secrets manager

### Application
- [x] `laravel/sanctum` installed
- [x] `spatie/laravel-permission` installed
- [ ] Spatie roles seeded
- [ ] All endpoints protected with `auth:sanctum` (routes defined but not registered)
- [ ] All endpoints have Form Request validation (partial)
- [ ] All endpoints return API Resources
- [ ] `APP_DEBUG=false` in production (set in prod compose)
- [ ] `APP_KEY` set and >= 32 chars
- [ ] `config:cache`, `route:cache`, `view:cache` run on deploy
- [x] Morph maps registered
- [x] `AppendOnlyLedger` trait fixed
- [x] DB-level XOR constraints on `sale_items` and `stock_transactions`
- [ ] `AuthController` implemented
- [ ] API routes registered in `bootstrap/app.php`
- [ ] Service business logic implemented

### Testing
- [ ] Feature tests for all API endpoints
- [ ] Unit tests for all Service classes
- [ ] Constraint tests (append-only, XOR FK, balance correctness)
- [ ] CI pipeline (GitHub Actions) running tests on every push

### Monitoring & Operations
- [ ] Error tracking (Sentry or Bugsnag)
- [ ] Database backup schedule (`spatie/laravel-backup`)
- [ ] Queue failure alerting
- [ ] `make health` passes

### Tauri (Desktop Wrapper)
- [ ] `frontend/src-tauri/` with `tauri.conf.json`, `main.rs`, `Cargo.toml`
- [ ] `build-desktop.sh` working
- [ ] IPC communication strategy defined
- [ ] Auto-updater configured
- [ ] App icon set

---

## 13. Long-Term Recommendations

**1. Event-Driven Ledger Writes** — Decouple services with Laravel events:
```php
event(new DeliveryReceived($delivery));
// Listener: WriteStockTransactionOnDelivery
```
Simplifies adding audit logging, notifications, and report triggers.

**2. Pessimistic Locking for Balance Calculation** — SQLite doesn't support `SELECT FOR UPDATE`, but a PostgreSQL/MySQL migration path requires it in `StockTransactionService::append()` to prevent race conditions.

**3. Read Model for Dashboard KPIs** — Expensive aggregations (daily revenue, tank levels) should be cached or snapshotted every N minutes rather than computed per request.

**4. Multi-Station Scalability Path** — Current single-station scope is correct. If multi-station is needed later, add a `stations` table and scope all entity queries through `station_id`. The polymorphic design makes this non-breaking.

**5. SQLite → PostgreSQL Migration** — Keep all SQLite-specific SQL isolated in `if (getDriverName() === 'sqlite')` blocks. Add equivalent `BEFORE UPDATE`/`BEFORE DELETE` triggers for non-SQLite DBs (currently only CHECK constraints are added for non-SQLite).

**6. Frozen API Versioning** — Keep v1 routes frozen once live. Introduce v2 for breaking changes. Never silently break clients.

---

## Summary Scorecard

| Area | Score (was) | Score (now) | Status |
|------|-------------|-------------|--------|
| Architecture Design | 8/10 | 8/10 | Strong foundation, sound decisions |
| Data Model / ERD | 9/10 | 9/10 | Excellent domain modeling |
| Append-Only Ledger | 5/10 | **8/10** | ✅ Concept + implementation fixed; service logic pending |
| Docker Setup | 6/10 | **7/10** | 🔄 Prod improved; dev Dockerfile still basic |
| Backend Implementation | 2/10 | **5/10** | 🔄 Scaffolding done; business logic + API wiring pending |
| Frontend Implementation | 1/10 | 1/10 | ⏳ Proof-of-concept placeholder |
| Testing | 0/10 | 0/10 | ⏳ Zero tests |
| Security | 2/10 | **4/10** | 🔄 Packages installed; auth wiring incomplete |
| Tauri Integration | 0/10 | 0/10 | ⏳ Not started |
| Documentation | 8/10 | **9/10** | ✅ Progress tracker added |
| **Overall** | **4.1/10** | **5.2/10** | **🔄 Scaffolding ~55% — business logic next** |

---

*End of review — 60+ files reviewed. Progress audit: August 10, 2026. See Section 0 for live task statuses.*
