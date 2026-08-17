# Implementation Plan: Backend Scaffolding

## Proposed Changes

### 1. Remaining Services Layer Implementation
Since Laravel doesn't have a native `make:service` command, I will implement the business logic for the remaining services:
- [ ] **Ledger**: `StockTransactionService`, `PaymentTransactionService` (core methods implemented, need refinement)
- [ ] **Operations**: `DeliveryService`, `NozzleReadingService`, `DeepReadingService`
- [ ] **Sales**: `SaleService`
- [ ] **Inventory**: `StockAdjustmentService`, `ProductService`
- [ ] **Setup**: `FuelTypeService`, `TankCalibrationService`, `AccountService`
- [ ] **Other**: `PurchaseOrderService`, `ReportService`

### 2. API Controllers, Requests & Resources
I will execute a bash script within the Docker container that uses Artisan to generate the following:
- [ ] Form Requests (`php artisan make:request Store[Name]Request` & `Update[Name]Request`) (partial — missing Update requests for Sale, PurchaseOrder and Auth requests)
- [ ] API Resources (`php artisan make:resource [Name]Resource`) (pending — no resource files present)
- [ ] Model Policies (`php artisan make:policy [Name]Policy --model=[Name]`) (pending — no policy files present)

### 3. Model & Migration Fixes
- [ ] Fix `StockTransaction` `$fillable` (remove mass-assignment risk for `stockable_type`/`stockable_id`)
- [ ] Fix `Account` `UPDATED_AT = null` + timestamps column
- [ ] Add `hasMany(StockTransaction)` relationship to `NozzleReading` model
- [ ] Address migration gap (skipped 100006) - cosmetic only
- [ ] Ensure `payment_transactions` table has no `timestamps()` call (by design for append-only)

### 4. Docker & Infrastructure Improvements
- [ ] Differentiate dev/prod PHP Dockerfiles (dev still has git/curl)
- [ ] Fix Composer layer caching in dev Dockerfile (copies only composer.json without lock)
- [ ] Resolve volume conflict in dev compose (bind vs named storage)
- [ ] Make queue worker wait for migrations in prod
- [ ] Configure Nginx SSL/TLS + rate limiting
- [ ] Fix `NUXT_PUBLIC_API_BASE_URL` env duplication
- [ ] Change Nginx `server_name localhost` to support custom domains

### 5. Security Enhancements
- [ ] Complete input validation (add missing Update requests and Auth requests)
- [ ] Fix mass assignment vulnerability (`stockable_type/id` still in `$fillable` on `StockTransaction`)
- [ ] Enhance Docker security (remove git/curl from dev image)
- [ ] Enforce `APP_KEY` in `.env.example`

### 6. Frontend Development
- [ ] Install Pinia + `@vueuse/nuxt` dependencies
- [ ] Create `useApi` composable + auth store
- [ ] Build entity stores (tanks, products, sales, accounts)
- [ ] Implement auth flow (login page, token storage)
- [ ] Develop CRUD pages / components

### 7. Testing & CI
- [ ] Write feature tests for API endpoints
- [ ] Create unit tests for all Service classes
- [ ] Implement constraint tests (append-only, XOR, balances)
- [ ] Set up CI pipeline (GitHub Actions)

### 8. Tauri Desktop Wrapper
- [ ] Create `frontend/src-tauri/` scaffold
- [ ] Develop `build-desktop.sh`
- [ ] Define IPC strategy + auto-updater

### 9. DRY / Refactor Opportunities
- [ ] Extract SQLite trigger blocks to helper
- [ ] Extract DB driver check helper
- [ ] Centralize stock sign convention in service
- [ ] Implement service interfaces + DI bindings

## Current Task Status
- [x] Docker + backend scaffold work started
- [x] Core service and controller scaffolding generated
- [x] `sanctum` and `spatie/permission` packages installed
- [x] API routes registered in `bootstrap/app.php`
- [x] `AuthController` implemented with login/logout methods
- [x] `RoleSeeder` and updated `DatabaseSeeder` implemented
- [x] `User` model updated with `HasApiTokens` and `HasRoles` traits
- [x] Core ledger services (`StockTransactionService`, `PaymentTransactionService`) have `append()` and `reverse()` methods implemented
- [ ] Remaining services need business logic implementation
- [ ] API Resources not yet created
- [ ] Policy classes not yet created
- [ ] Form Requests partially complete (missing Update requests for Sale, PurchaseOrder and Auth requests)
- [ ] Frontend and desktop wrapper status remains outside this plan
- [ ] Testing infrastructure not yet implemented

## Verification Plan
1. Run `docker compose exec backend php artisan migrate:fresh --seed` to ensure the new Seeders and UserFactory work perfectly.
2. Run `docker compose exec backend php artisan route:list` to verify all API endpoints map correctly to the newly created Controllers.
3. Run `docker compose exec backend php artisan test` to verify test suite passes (once tests are implemented).
4. Run `docker compose exec frontend npm run dev` to verify frontend development server starts.