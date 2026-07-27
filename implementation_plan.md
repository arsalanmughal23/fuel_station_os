# FuelStationOS — Implementation Plan
**Version:** 3.1 | **ERD:** v4 (Final) | **Last Updated:** July 19, 2026

---

## 1. Project Overview

Single-station desktop fuel management system. Manages fuel tanks, nozzles, deliveries, products, sales, accounts, and inventory with an **append-only ledger** architecture for full auditability.

---

## 2. Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.4+) |
| Frontend | Nuxt 3 (Vue 3) |
| Auth | Laravel Sanctum |
| RBAC | `spatie/laravel-permission` |
| Database | SQLite (embedded, single-file) |
| Desktop Wrapper | NativePHP + Tauri adapter |
| Containerization | Docker + Docker Compose (fully dockerized) |
| Queue | Laravel Queue (`database` driver) |

---

## 3. Docker Setup

All services run in Docker. No local PHP/Composer/Node required.

```
docker/
├── php/Dockerfile          # PHP 8.4 + Composer + Laravel
├── node/Dockerfile         # Node 20 for Nuxt 3 frontend
├── nginx/default.conf      # Nginx reverse proxy
docker-compose.yml          # Orchestrates all services
```

**Services in `docker-compose.yml`:**
- `app` — Laravel backend (PHP-FPM, port 9000)
- `nginx` — Web server (port 8000 → app)
- `frontend` — Nuxt 3 dev server (port 3000)
- `queue` — Laravel queue worker (database driver)

**Key volumes:**
- `./backend:/var/www/html/backend` — Laravel source
- `./frontend:/var/www/html/frontend` — Nuxt source

---

## 4. Database Schema (ERD v4 — 21 Tables)

### 4.1 Table Overview

| # | Table | Purpose |
|---|---|---|
| 1 | `users` | System users |
| 2 | `roles` | Spatie RBAC roles |
| 3 | `permissions` | Spatie RBAC permissions |
| 4 | `model_has_roles` | Spatie pivot |
| 5 | `model_has_permissions` | Spatie pivot |
| 6 | `fuel_types` | Petrol, Diesel, CNG, etc. |
| 7 | `tanks` | Underground storage tanks |
| 8 | `tank_calibrations` | Dip chart: cm → liters |
| 9 | `deep_readings` | Physical dip measurements |
| 10 | `nozzles` | Dispensing nozzles (belong to tank) |
| 11 | `nozzle_readings` | Opening/closing meter readings |
| 12 | `accounts` | Distributors, customers, employees, owner |
| 13 | `purchase_orders` | Fuel purchase orders |
| 14 | `deliveries` | Fuel delivery records |
| 15 | `products` | Shop items (lubricants, accessories, etc.) |
| 16 | `sales` | Sale transactions |
| 17 | `sale_items` | Line items per sale (fuel XOR product) |
| 18 | `stock_adjustments` | Polymorphic inventory adjustments |
| 19 | `stock_transactions` | **Append-only inventory ledger** |
| 20 | `payment_transactions` | **Append-only payment ledger** |
| 21 | `price_history` | Polymorphic price change log |

### 4.2 Critical Constraints

```
SALE_ITEMS:         exactly ONE of (product_id, nozzle_reading_id) must be set
STOCK_TRANSACTIONS: exactly ONE of (delivery_id, nozzle_reading_id, sale_item_id,
                    stock_adjustment_id, reversed_transaction_id) must be set
```

### 4.3 Polymorphic Relationships

| Morph Key | Types |
|---|---|
| `stockable` (Tank \| Product) | `stock_transactions`, `stock_adjustments` |
| `priceable` (FuelType \| Product) | `price_history` |

### 4.4 Derived / Read-only Fields (never written directly)

| Field | Derived From |
|---|---|
| `tanks.calculated_stock` | SUM of `stock_transactions.quantity` WHERE stockable = Tank |
| `products.current_stock` | Persisted inventory level (updated via stock_transactions) |
| `accounts.current_balance` | `opening_balance` + SUM of linked `payment_transactions` |
| `sales.total_amount` | SUM of `sale_items.amount` |

---

## 5. Migration Order (Dependency Resolved)

```
 1. users
 2-5. roles, permissions, model_has_roles, model_has_permissions  (spatie auto)
 6. fuel_types
 7. accounts                   (FK: users)
 8. tanks                      (FK: fuel_types)
 9. tank_calibrations           (FK: tanks)
10. nozzles                    (FK: tanks)
11. products
12. price_history               (polymorphic priceable; FK: users)
13. deep_readings               (FK: tanks, users)
14. nozzle_readings             (FK: nozzles, users)
15. purchase_orders             (FK: accounts, fuel_types)
16. deliveries                  (FK: purchase_orders, tanks)
17. sales                       (FK: accounts)
18. sale_items                  (FK: sales, products[null], nozzle_readings[null])
19. stock_adjustments           (polymorphic stockable; FK: users, deep_readings[null])
20. stock_transactions          (polymorphic stockable; FK: users, delivery[null],
                                 nozzle_reading[null], sale_item[null],
                                 stock_adjustment[null], reversed_transaction[null])
21. payment_transactions        (FK: accounts, sales[null], purchase_orders[null],
                                 reversed_transaction[null])
```

---

## 6. Append-Only Ledger Architecture

### Stock Ledger (`stock_transactions`)

The single source of truth for all inventory (tanks + products).

| FK Set | Row Meaning |
|---|---|
| `delivery_id` | Tank stock-in from delivery |
| `nozzle_reading_id` | Tank stock-out from fuel sale |
| `sale_item_id` | Product stock-out from shop sale |
| `stock_adjustment_id` | Any manual adjustment |
| `reversed_transaction_id` | Reversal (all other FKs null) |

**Reversal pattern** — never UPDATE/DELETE ledger rows:
1. Post new row: `quantity = -original.quantity`, set `reversed_transaction_id`
2. Post corrected row if needed

### Payment Ledger (`payment_transactions`)

The single source of truth for all money movement.

| FK Set | Row Meaning |
|---|---|
| `sale_id` | Revenue from a sale |
| `purchase_order_id` | Expense from fuel purchase |
| both null | Salary / utility / maintenance / other |

---

## 7. Enums

| Enum | Values |
|---|---|
| `AccountType` | `distributor`, `customer`, `employee`, `owner` |
| `AdjustmentType` | `correction`, `spillage`, `evaporation`, `theft`, `return`, `other` |
| `PaymentType` | `income`, `expense` |
| `PaymentCategory` | `fuel_purchase`, `fuel_sale`, `salary`, `utility`, `maintenance`, `other` |
| `PaymentMethod` | `cash`, `bank_transfer`, `cheque`, `card` |
| `PaymentStatus` | `pending`, `completed`, `failed`, `cancelled` |
| `PurchaseOrderStatus` | `pending`, `partially_received`, `received`, `cancelled` |
| `ProductCategory` | `lubricant`, `accessory`, `snack`, `other` |
| `ProductUnit` | `Liter`, `Piece`, `Box`, `Kg`, `Ml` |

---

## 8. Phased Execution Plan

### Phase 1 — Docker Infrastructure

- `docker-compose.yml` with `app`, `nginx`, `frontend`, `queue` services
- PHP 8.4 Dockerfile with Composer
- Node 20 Dockerfile for Nuxt 3
- Nginx config routing API → Laravel, `/` → Nuxt
- SQLite volume mount
- `.env` for each service

### Phase 2 — Database & Models

#### 2.1 Install Packages
- [ ] `composer require spatie/laravel-permission laravel/sanctum`
- [ ] `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`

#### 2.2 Fix Existing Migrations (align to ERD v4)
- [ ] **`nozzle_readings`** — remove `shift_id` FK (not in ERD v4)
- [ ] **`stock_adjustments`** — replace `tank_id FK` + `quantity_liters` with polymorphic `stockable_type`/`stockable_id` + `quantity`
- [ ] **`stock_transactions`** — replace `tank_id FK` with polymorphic `stockable_type`/`stockable_id`; add `user_id FK`; add `sale_item_id FK`; rename `quantity_liters` → `quantity`
- [ ] **`payment_transactions`** — replace `nozzle_reading_id FK` with `sale_id FK`; add `timestamps()`
- [ ] **`products`** — add `current_stock` column (persisted inventory level, default 0)
- [ ] **Delete** `create_shifts_table.php` migration (not in ERD v4)

#### 2.3 Create Missing Migrations
- [ ] `price_history` — polymorphic `priceable`; fields: `priceable_type`, `priceable_id`, `old_price`, `new_price`, `user_id FK`, `reason`, `changed_at`, `timestamps`
- [ ] `sales` — fields: `account_id FK (nullable)`, `total_amount (derived)`, `sold_at`, `timestamps`
- [ ] `sale_items` — fields: `sale_id FK`, `product_id FK (nullable)`, `nozzle_reading_id FK (nullable)`, `unit`, `quantity`, `unit_price`, `amount`, `timestamps`

#### 2.4 Fix Existing Models (align to ERD v4)
- [ ] **`StockTransaction`** — add `morphTo('stockable')`; add `user()`, `saleItem()` relations; fix `$fillable` (remove `tank_id`, add `stockable_type`, `stockable_id`, `user_id`, `sale_item_id`, rename `quantity_liters` → `quantity`)
- [ ] **`StockAdjustment`** — add `morphTo('stockable')`; fix `$fillable` (remove `tank_id`, add `stockable_type`, `stockable_id`, rename `quantity_liters` → `quantity`); remove `tank()` relation
- [ ] **`PaymentTransaction`** — remove `nozzleReading()` relation; add `sale()` relation; fix `$fillable` (`nozzle_reading_id` → `sale_id`); add `timestamps`
- [ ] **`Tank`** — replace `hasMany(StockTransaction)` / `hasMany(StockAdjustment)` with `morphMany(StockTransaction, 'stockable')` / `morphMany(StockAdjustment, 'stockable')`; add `getCalculatedStockAttribute()`
- [ ] **`Product`** — add `morphMany(StockTransaction, 'stockable')`, `morphMany(StockAdjustment, 'stockable')`, `morphMany(PriceHistory, 'priceable')`, `hasMany(SaleItem)`; add `getCalculatedStockAttribute()`; remove stored `current_stock`
- [ ] **Delete** `Shift.php` model

#### 2.5 Create Missing Models
- [ ] `PriceHistory` — `morphTo('priceable')`, `belongsTo(User)`, `$fillable`, `$casts`
- [ ] `Sale` — `belongsTo(Account)`, `hasMany(SaleItem)`, `hasOne(PaymentTransaction)`, `getTotalAmountAttribute()`
- [ ] `SaleItem` — `belongsTo(Sale)`, `belongsTo(Product, nullable)`, `belongsTo(NozzleReading, nullable)`, `hasOne(StockTransaction)`, `$fillable`

#### 2.6 Fix / Create Enums
- [ ] `AdjustmentType` — `correction`, `spillage`, `evaporation`, `theft`, `return`, `other`
- [ ] `PaymentMethod` — `cash`, `bank_transfer`, `cheque`, `card`
- [ ] `PaymentCategory` — `fuel_purchase`, `fuel_sale`, `salary`, `utility`, `maintenance`, `other`
- [ ] `PaymentStatus` — `pending`, `completed`, `failed`, `cancelled`
- [ ] `PurchaseOrderStatus` — `pending`, `partially_received`, `received`, `cancelled`
- [ ] `ScaleUnit` — `Litr`, `Pcs`, `Box`, `Kg`, `Ml` (renamed from ProductUnit)

#### 2.7 Register Morph Maps (`AppServiceProvider`)
- [ ] Stockable: `'Tank' => Tank::class`, `'Product' => Product::class`
- [ ] Priceable: `'FuelType' => FuelType::class`, `'Product' => Product::class`

#### 2.8 Seeders
- [ ] `RoleSeeder` — create roles: `admin`, `manager`, `staff`
- [ ] `DatabaseSeeder` — call `RoleSeeder` + seed Owner account + sample FuelType, Tank, Nozzle, TankCalibration

### Phase 3 — Business Logic Services

| Service | Responsibility |
|---|---|
| `FuelTypeService` | CRUD; price update → writes `PriceHistory` row |
| `TankCalibrationService` | Linear interpolation `deep_cm → volume_liters` |
| `DeepReadingService` | Record dip; compute variance vs system stock |
| `NozzleReadingService` | Opening/closing; compute `liters_sold`, `amount` |
| `ProductService` | CRUD; price update → writes `PriceHistory` row |
| `SaleService` | Create Sale + SaleItems; posts StockTransaction per product item |
| `DeliveryService` | Receive delivery → posts StockTransaction (tank stock-in) |
| `PurchaseOrderService` | CRUD; status transitions |
| `StockAdjustmentService` | Record adjustment (Tank or Product) → posts StockTransaction |
| `StockTransactionService` | Core append-only ledger writer; reversal logic |
| `PaymentTransactionService` | Core payment ledger writer; reversal logic |
| `AccountService` | CRUD; balance derived from ledger |
| `ReportService` | Dashboard KPIs + report aggregations |

### Phase 4 — API Layer

- Laravel Sanctum token authentication
- API versioned at `/api/v1/`
- Form Request validation for all inputs
- API Resources for all models
- Controllers: Auth, FuelType, Tank, TankCalibration, Nozzle, NozzleReading, DeepReading, Product, PriceHistory, Sale, SaleItem, StockAdjustment, StockTransaction, PurchaseOrder, Delivery, Account, PaymentTransaction, Dashboard, Report

### Phase 5 — Frontend (Nuxt 3 / Vue 3)

**Design:** Dark sidebar + glassmorphism cards + smooth animations

| Section | Pages |
|---|---|
| Auth | Login |
| Dashboard | KPIs (volume, revenue, variance), tank levels |
| Fuel Setup | Fuel types, Tanks, Calibration charts, Nozzles |
| Operations | Nozzle readings (opening/closing), Deep readings |
| Products | Catalog, pricing, price history |
| Sales | POS (fuel + products), transaction history, receipts |
| Procurement | Purchase orders, deliveries |
| Inventory | Stock ledger, adjustments, variance reports |
| Accounts | Account management, payment entries, statements |
| Reports | Daily/monthly sales, volume, delivery, variance |
| Settings | Users, roles & permissions |

### Phase 6 — Testing

- Unit tests: all Service classes
- Feature tests: all API endpoints
- Constraint tests:
  - `SaleItem` dual-FK constraint
  - `StockTransaction` single-FK constraint
  - Ledger immutability (no UPDATE/DELETE on ledger rows)
  - Derived balance correctness

---

## 9. Key Design Decisions

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

*Document Version: 3.1 | Aligned with ERD v4 (Final)*
