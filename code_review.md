# Fuel Station OS — Comprehensive Professional Code Review

**Reviewer:** Antigravity (AI Code Review)
**Date:** August 2026
**Codebase:** Laravel 12 + Nuxt 3 + SQLite + Docker

---

## 1. Executive Summary

Fuel Station OS is a well-conceived, purpose-built desktop fuel management system with a commendably thoughtful architectural foundation. The data model and append-only ledger design demonstrate genuine domain expertise — the ERD is logical, the polymorphic relationships are well-placed, and the decision to use SQLite for a single-station desktop app is pragmatic and appropriate. However, the project is currently **at scaffolding stage (~25% complete)**. The backend has no Service layer, no API controllers, no authentication, no Form Requests, no Resources, and zero application tests. The frontend is essentially a blank page with a single test button. The Docker setup is functional but has reproducibility and security gaps. Before any production deployment, significant implementation work is needed across every layer, plus immediate fixes to several critical issues identified below.

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
- **No authentication implemented** — `routes/api.php` has no Sanctum middleware. The API is completely open.
- **No Service layer** — All 13 services described in the plan are absent. Business logic has nowhere to live.
- **No API controllers** — Only `Controller.php` (empty base class) exists. Every API endpoint is unimplemented.
- **No Form Request validation** — No `app/Http/Requests/` directory exists.
- **No API Resources** — No `app/Http/Resources/` directory exists.
- **Zero application tests** — Both `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` are Laravel scaffolding stubs.
- **Morph map NOT registered** — `AppServiceProvider::boot()` is empty. The plan identifies the need to register morph maps but it was never done. Without this, morph queries store full class names (e.g. `App\Models\Tank`) in the DB — breaking on any namespace refactor.

### High
- **`AppendOnlyLedger` trait is fundamentally broken** — The `bootAppendOnlyLedger` global scope filters all queries to rows `WHERE updated_at = 0`, which is always false. `StockTransaction` sets `UPDATED_AT = null` so the column doesn't exist. This scope will corrupt reads, silently returning empty sets.
- **`tanks.calculated_stock` is persisted but never updated** — The migration persists a `calculated_stock` column at `default(0)`, but no trigger or service updates it. The `getCalculatedStockAttribute()` accessor recalculates via `sum()` (correct), but the stored column is permanently stale data.
- **`Sale.customer()` uses wrong FK and wrong model** — `Sale::customer()` calls `belongsTo(User::class, 'customer_id')`. The ERD shows `account_id` FK on `sales` pointing to `accounts`, not `users`. The `$fillable` also lists `customer_id` which doesn't exist in the migration.
- **`accounts.current_balance` is stored but never updated** — Same anti-pattern: persisted field with no mechanism to stay in sync with `payment_transactions`.
- **Inconsistent cascade rules** — `nozzle_readings` uses `onDelete('cascade')` while all other FK children use `restrictOnDelete()`. Deleting a nozzle will silently wipe all its readings.
- **`PaymentTransaction` missing enum casts** — `payment_method` and `status` are not cast to their enum types (`PaymentMethod`, `PaymentStatus`), so they return raw strings.
- **No Sanctum, no RBAC packages installed** — `composer.json` only has `laravel/framework` + `laravel/tinker`. Neither `laravel/sanctum` nor `spatie/laravel-permission` are installed.
- **Frontend is a placeholder** — One page, zero components, zero composables, zero Pinia stores, no auth flow, no routing structure.

### Medium
- **Dev and Prod PHP Dockerfiles are identical** — `Dockerfile` and `Dockerfile.prod` are byte-for-byte identical. No multi-stage build, no OPcache, `git` and `curl` are in the prod image.
- **Composer layer caching broken** — Both Dockerfiles do `COPY . .` *before* `composer install`, invalidating the dep layer on every source file change.
- **`FuelType.current_price` denormalization** — Storing current price on `FuelType` alongside `PriceHistory` requires careful dual-write coordination. No service enforces this.
- **`Product.boot()` not `booted()`** — Laravel convention since v8 is `booted()` for model lifecycle hooks. Same issue in `FuelType`.
- **`StockTransaction::UPDATED_AT = null` + `timestamps()`** — Suppresses the column update but `timestamps()` still creates the `updated_at` column physically.
- **No Tauri files exist** — `frontend/src-tauri/` does not exist. No `tauri.conf.json`, no `main.rs`, no `Cargo.toml`.
- **SQL syntax error in migration `down()`** — `stock_adjustments` down: `DB::statement('DROP IF EXISTS stock_adjustments_adjustment_type_check')` is not valid SQL.

### Low
- **`composer.json` has default Laravel boilerplate** — `name: "laravel/laravel"`, `description: "The skeleton application..."` should be updated.
- **`ScaleUnit::Litr`** — Typo; should be `Liter` or `Litre`.
- **`SaleItem` casts `unit` as `'string'`** — Should cast to `ScaleUnit::class`.
- **`NUXT_PUBLIC_API_BASE_URL` set in both `env_file` and `environment`** in compose — The `environment` key silently overrides `env_file`, causing confusion.
- **Nginx `server_name localhost`** — Will not work with custom domain names in production.

---

## 4. Priority Issues (Fix Immediately)

### P0 — Blockers

#### 4.1 Fix the `AppendOnlyLedger` trait's broken global scope

The `bootAppendOnlyLedger` adds a global scope `WHERE updated_at = 0` — this will always return zero rows on ledger tables and is logically wrong. The trait's sole job is to block writes, not filter reads.

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

#### 4.2 Register Morph Maps in `AppServiceProvider`

Without this, morph types are stored as `App\Models\Tank` in the DB — any namespace refactor silently breaks all polymorphic queries.

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

#### 4.3 Fix `Sale` model — wrong FK, wrong related model

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

#### 4.4 Fix missing enum casts in `PaymentTransaction`

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

#### 4.5 Install required packages

```bash
composer require laravel/sanctum spatie/laravel-permission
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### P1 — High Priority

#### 4.6 Resolve `calculated_stock` ambiguity (choose one approach)

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

#### 4.7 Fix inconsistent cascade in `nozzle_readings`

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

**Missing:** No `docker/tauri/` directory and no `build-desktop.sh`. The Tauri desktop wrapper is entirely unimplemented.

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

**Missing DB enforcement — XOR constraints:**

The plan states `SALE_ITEMS: exactly ONE of (product_id, nozzle_reading_id) must be set`. This is not enforced at any level. Add SQLite triggers:

```sql
CREATE TRIGGER sale_items_xor_check
BEFORE INSERT ON sale_items
BEGIN
    SELECT CASE
        WHEN (NEW.product_id IS NULL AND NEW.nozzle_reading_id IS NULL)
          OR (NEW.product_id IS NOT NULL AND NEW.nozzle_reading_id IS NOT NULL)
        THEN RAISE(ABORT, 'sale_items must have exactly one of product_id or nozzle_reading_id')
    END;
END;
```

### 5.3 Service Layer — Absent

The plan lists 13 services; none exist. This is the highest-priority implementation work.

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

| File | Issue | Severity |
|------|-------|----------|
| `Sale.php` | `customer_id` in `$fillable` and wrong relationship | Critical |
| `AppendOnlyLedger.php` | Global scope corrupts reads | Critical |
| `Tank.php` | `calculated_stock` accessor causes N+1 when listing | High |
| `StockTransaction.php` | `stockable_type`/`stockable_id` should not be in `$fillable` | Medium |
| `FuelType.php` | `slug` is auto-generated but in `$fillable` | Medium |
| `Product.php` | `getCurrentStockAttribute()` is a no-op getter | Low |
| `Product.php` | Uses `boot()` instead of `booted()` | Low |
| `Account.php` | `UPDATED_AT = null` but `timestamps()` creates the column | Low |
| `NozzleReading.php` | Missing `hasMany(StockTransaction)` relationship | Medium |

### 7.2 Migrations — Issues Summary

- **Missing polymorphic index** on `stock_transactions` — critical for morph query performance:
  ```php
  $table->index(['stockable_type', 'stockable_id', 'created_at'], 'st_stockable_idx');
  ```
- **Missing polymorphic index** on `stock_adjustments`:
  ```php
  $table->index(['stockable_type', 'stockable_id'], 'sa_stockable_idx');
  ```
- `payment_transactions` — no `timestamps()` call. No `created_at`/`updated_at` columns.
- `nozzle_readings` — uses `onDelete('cascade')` inconsistently (see §4.7).
- `stock_adjustments` `down()` method has invalid SQL (`DROP IF EXISTS constraint_name` is not valid syntax).
- Migration gap: `100006` is skipped (accounts is `100005`, nozzles is `100007`).

### 7.3 API Routes

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
| `laravel/sanctum` | Not installed | No auth possible |
| `spatie/laravel-permission` | Not installed | No RBAC possible |
| Service layer (13 services) | Not created | No business logic |
| API Controllers (18+) | Not created | No endpoints |
| Form Requests | Not created | No input validation |
| API Resources | Not created | No response shaping |
| Events & Listeners | Not created | No event-driven patterns |
| Model Policies | Not created | No authorization |
| Seeders (RoleSeeder, etc.) | Not created | Cannot bootstrap |

---

## 8. Frontend Code Review (Nuxt 3)

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

1. **Slug generation** — `FuelType::boot()` and `Product::boot()` both have identical `Str::slug()` logic. Extract to a `HasSlug` trait:
   ```php
   // app/Models/Concerns/HasSlug.php
   trait HasSlug
   {
       protected static function bootHasSlug(): void
       {
           static::creating(fn($model) => $model->slug = Str::slug($model->title));
       }
   }
   ```

2. **SQLite trigger blocks** — copy-pasted verbatim across `stock_transactions` and `payment_transactions` migrations. Extract to a `MigrationHelper` class.

3. **DB driver check** — `if (Schema::getConnection()->getDriverName() !== 'sqlite')` appears 6 times across migrations.

4. **Sign convention comments** — `positive=in, negative=out` is commented in 4 places but never validated. Centralize in `StockTransactionService`.

---

## 10. Security Review

| Area | Status | Notes |
|------|--------|-------|
| Authentication | 🔴 CRITICAL | No Sanctum, all routes public |
| Authorization (RBAC) | 🔴 CRITICAL | No Spatie, no Policies |
| Input Validation | 🔴 CRITICAL | No Form Requests exist |
| Mass Assignment | ⚠️ WARNING | `stockable_type/id` and `slug` should not be in `$fillable` |
| SQL Injection | ✅ SAFE | Eloquent ORM parameterizes all queries |
| XSS | ✅ N/A | JSON API, no HTML rendering |
| CSRF | ✅ CORRECT | API token auth, `api/*` excluded from CSRF |
| Port Exposure | ⚠️ WARNING | PHP-FPM port 9000 exposed to host |
| Env Security | ⚠️ WARNING | `APP_KEY` not in `.env.example`; no secrets management |
| Docker Security | ⚠️ WARNING | `git`/`curl` in prod image; unnecessary attack surface |
| SQLite Access | ✅ SAFE | Nginx root is `public/` only; DB file not web-accessible |

**The three critical security items (auth, RBAC, validation) must be implemented before any production or even internal staging deployment.**

---

## 11. Improvement Recommendations

### Immediate (Fix Before Feature Work)
1. Fix `AppendOnlyLedger` trait (see §4.1)
2. Register morph maps in `AppServiceProvider` (see §4.2)
3. Fix `Sale` model customer relationship (see §4.3)
4. Add missing enum casts to `PaymentTransaction` (see §4.4)
5. Install Sanctum + Spatie (see §4.5)
6. Remove PHP-FPM port 9000 host exposure from `docker-compose.yml`

### Short Term (Next Sprint)
7. Differentiate dev/prod PHP Dockerfiles with multi-stage build
8. Fix Composer layer caching in both Dockerfiles
9. Add `--frozen-lockfile` to Node Dockerfile
10. Add `HasSlug` trait (eliminate boot() duplication)
11. Add XOR constraint triggers for `sale_items` and `stock_transactions`
12. Add composite index on `(stockable_type, stockable_id)` for morph tables
13. Create `RoleSeeder` + `DatabaseSeeder` with Owner account, sample FuelType/Tank/Nozzle

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
- [ ] PHP `Dockerfile.prod` — multi-stage, no git/curl, OPcache enabled
- [ ] PHP-FPM port 9000 NOT exposed to host
- [ ] Production compose is standalone (not an overlay of dev)
- [ ] Nginx with SSL/TLS configured
- [ ] Nginx rate limiting (`limit_req_zone`)
- [ ] Log rotation for nginx and Laravel logs
- [ ] SQLite database on a named volume with scheduled backup
- [ ] Docker secrets or env injection from secrets manager

### Application
- [ ] `laravel/sanctum` installed and configured
- [ ] `spatie/laravel-permission` installed, roles seeded
- [ ] All endpoints protected with `auth:sanctum`
- [ ] All endpoints have Form Request validation
- [ ] All endpoints return API Resources
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` set and >= 32 chars
- [ ] `config:cache`, `route:cache`, `view:cache` run on deploy
- [ ] Morph maps registered
- [ ] `AppendOnlyLedger` trait fixed
- [ ] DB-level XOR constraints on `sale_items` and `stock_transactions`

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

| Area | Score | Status |
|------|-------|--------|
| Architecture Design | 8/10 | Strong foundation, sound decisions |
| Data Model / ERD | 9/10 | Excellent domain modeling |
| Append-Only Ledger | 5/10 | Concept correct, implementation broken |
| Docker Setup | 6/10 | Functional but dev = prod, port exposed |
| Backend Implementation | 2/10 | Scaffolding only — nothing built |
| Frontend Implementation | 1/10 | Proof-of-concept placeholder |
| Testing | 0/10 | Zero tests |
| Security | 2/10 | No auth, no validation |
| Tauri Integration | 0/10 | Not started |
| Documentation | 8/10 | Implementation plan is excellent |
| **Overall** | **4.1/10** | **Solid foundation — needs extensive build-out** |

---

*End of review — 60+ files reviewed across backend, frontend, Docker, and configuration.*
