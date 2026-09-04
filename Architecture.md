# System Architecture
**Version:** 4.0 (Tauri + FrankenPHP Sidecar) | **Last Updated:** August 2026

---

## Tech Stack
| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.4+) |
| Frontend | Nuxt 3 (Vue 3) |
| Auth | Laravel Sanctum |
| RBAC | `spatie/laravel-permission` |
| Database | SQLite (embedded, single-file, WAL mode) |
| Desktop Wrapper | **Tauri (Rust) + FrankenPHP Sidecar** |
| Containerization | Docker + Docker Compose (dev only) |
| Queue | Laravel Queue (`database` driver) |
| PHP Runtime | **FrankenPHP (via Laravel Octane)** |

> **Architecture Shift:** Moved from `NativePHP + Tauri adapter` → **Tauri manages frontend + FrankenPHP sidecar**. Single executable bundles Laravel backend (via FrankenPHP worker) + Nuxt 3 frontend.

---

## Project Structure (Post-Migration)
```
/var/www/html/fuel_station_os/
├── backend/                    # Laravel backend (moved from root)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── routes/
│   ├── storage/
│   ├── resources/
│   ├── Caddyfile               # FrankenPHP config
│   ├── frankenphp-worker.php   # Worker entry point
│   ├── composer.json
│   └── .env.example
├── frontend/                   # Nuxt 3 + Tauri
│   ├── package.json
│   ├── nuxt.config.ts
│   ├── src-tauri/
│   │   ├── src/
│   │   │   ├── main.rs
│   │   │   ├── sidecar.rs
│   │   │   └── commands/
│   │   ├── Cargo.toml
│   │   └── tauri.conf.json
├── build/
│   ├── scripts/
│   │   ├── build-sidecar.sh
│   │   └── package-installer.sh
│   └── installers/
│       ├── windows.nsi
│       ├── macos.dmg
│       └── linux/
├── docker/
│   ├── php/Dockerfile
│   ├── node/Dockerfile
│   └── nginx/default.conf
├── docker-compose.dev.yml      # Development: Docker backend + host frontend
├── docker-compose.prod.yml     # Production: Single container (optional)
└── README.md
```

---

## Docker Services (`docker-compose.dev.yml`)
| Service | Description | Port |
|---|---|---|
| `backend` | Laravel + FrankenPHP (Octane) | 8000 (external) |
| `queue` | Laravel queue worker | — |
| `scheduler` | Laravel scheduler | — |
| `nginx` | Reverse proxy (prod-like) | 80 |
| `frontend` | Nuxt 3 dev server (host) | 3000 |

**Key volumes (dev):**
- `./backend:/var/www/html:delegated` — Laravel source
- `./vendor:/var/www/html/vendor` — Composer vendor

> **Production** uses **no Docker** — single Tauri executable bundles FrankenPHP sidecar + compiled frontend.

---

## Database Schema — 21 Tables (ERD v4)

> **Full visual ERD with all columns and FK details:** [`fuelstationos_erd_v4.mermaid`](./fuelstationos_erd_v4.mermaid)
> Read this file when implementing or verifying models and migrations.

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
| 16 | `sales` | Sale transactions (`user_id FK`, `account_id FK nullable`, `total_amount`, `paid_amount`, `change_amount`, `payment_status`, `sale_date`) |
| 17 | `sale_items` | Line items per sale (fuel XOR product) |
| 18 | `stock_adjustments` | Polymorphic inventory adjustments |
| 19 | `stock_transactions` | **Append-only inventory ledger** |
| 20 | `payment_transactions` | **Append-only payment ledger** |
| 21 | `price_history` | Polymorphic price change log |

### Critical XOR Constraints
```
SALE_ITEMS:         exactly ONE of (product_id, nozzle_reading_id) must be set
STOCK_TRANSACTIONS: exactly ONE of (delivery_id, nozzle_reading_id, sale_item_id,
                    stock_adjustment_id, reversed_transaction_id) must be set
```
> Enforced via SQLite triggers in migrations.

### Polymorphic Relationships
| Morph Key | Types |
|---|---|
| `stockable` | `Tank`, `Product` → used in `stock_transactions`, `stock_adjustments` |
| `priceable` | `FuelType`, `Product` → used in `price_history` |

> Morph map uses short keys: `'Tank'`, `'Product'`, `'FuelType'`

### Derived / Read-only Fields (never written directly)
| Field | Derived From |
|---|---|
| `tanks.calculated_stock` | SUM of `stock_transactions.quantity` WHERE stockable = Tank |
| `products.current_stock` | Updated via `StockTransaction::booted()` event on create |
| `accounts.current_balance` | `opening_balance` + SUM of linked `payment_transactions` |
| `sales.total_amount` | SUM of `sale_items.amount` |

### Migration Order (Dependency Resolved)
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

## Append-Only Ledger Architecture

### Stock Ledger (`stock_transactions`)
| FK Set | Row Meaning |
|---|---|
| `delivery_id` | Tank stock-in from delivery |
| `nozzle_reading_id` | Tank stock-out from fuel sale |
| `sale_item_id` | Product stock-out from shop sale |
| `stock_adjustment_id` | Manual adjustment (spillage, theft, etc.) |
| `reversed_transaction_id` | Reversal entry |

**Reversal pattern** — never UPDATE/DELETE ledger rows:
1. Post new row: `quantity = -original.quantity`, set `reversed_transaction_id`
2. Post corrected row if needed

### Payment Ledger (`payment_transactions`)
| FK Set | Row Meaning |
|---|---|
| `sale_id` | Revenue from a sale |
| `purchase_order_id` | Expense from fuel purchase |
| both null | Salary / utility / maintenance / other |

---

## Enums
| Enum | Values |
|---|---|
| `AccountType` | `distributor`, `customer`, `employee`, `owner` |
| `AdjustmentType` | `correction`, `spillage`, `evaporation`, `theft`, `return`, `other` |
| `PaymentType` | `income`, `expense` |
| `PaymentCategory` | `fuel_purchase`, `fuel_sale`, `salary`, `utility`, `maintenance`, `other` |
| `PaymentMethod` | `cash`, `bank_transfer`, `cheque`, `card` |
| `PaymentStatus` | `pending`, `completed`, `failed`, `cancelled` |
| `SalePaymentStatus` | `pending`, `paid`, `partially_paid`, `refunded` |
| `PurchaseOrderStatus` | `pending`, `partially_received`, `received`, `cancelled` |
| `ProductCategory` | `lubricant`, `accessory`, `snack`, `other` |
| `ScaleUnit` | DB values: `ltr`, `pcs`, `box`, `kg`, `ml` (labels: Liter, Piece, Box, Kg, Ml) |

---

## Service Layer (13 Planned Services)
All services live flat in `app/Services/` (not in subdirectories).

| Service | Responsibility |
|---|---|
| `StockTransactionService` | Core append-only ledger writer; reversal logic |
| `PaymentTransactionService` | Core payment ledger writer; reversal logic |
| `FuelTypeService` | CRUD; price update → writes `PriceHistory` row |
| `TankCalibrationService` | Linear interpolation `deep_cm → volume_liters` |
| `DeepReadingService` | Record dip; compute variance vs. system stock |
| `NozzleReadingService` | Opening/closing; compute `liters_sold`, `amount` |
| `ProductService` | CRUD; price update → writes `PriceHistory` row |
| `SaleService` | Create Sale + SaleItems; posts StockTransaction per product item |
| `DeliveryService` | Receive delivery → posts StockTransaction (tank stock-in) |
| `PurchaseOrderService` | CRUD; status transitions |
| `StockAdjustmentService` | Record adjustment (Tank or Product) → posts StockTransaction |
| `AccountService` | CRUD; balance derived from ledger |
| `ReportService` | Dashboard KPIs + report aggregations |
| `TankService` | CRUD for tanks (extra — not in original 13-service plan) |
| `NozzleService` | CRUD for nozzles (extra — not in original 13-service plan) |

---

## API Layer
- Auth: Laravel Sanctum token-based (`auth:sanctum` middleware)
- Versioned at `/api/v1/`
- Form Request validation for all inputs
- API Resources for all model responses
- **Controllers:** Auth, FuelType, Tank, TankCalibration, Nozzle, NozzleReading, DeepReading, Product, PriceHistory, Sale, SaleItem, StockAdjustment, StockTransaction, PurchaseOrder, Delivery, Account, PaymentTransaction, Dashboard, Report

---

## Frontend Pages (Nuxt 3 / Vue 3)
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

---

## Service Layer — Reference Implementation

### `StockTransactionService` (Core Ledger Pattern)
```php
// backend/app/Services/StockTransactionService.php
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

    public function reverse(StockTransaction $original, int $userId, string $reason): StockTransaction
    {
        return $this->append(
            stockable: $original->stockable,
            quantity:  -$original->quantity,
            unit:      $original->unit,
            userId:    $userId,
            sourceFK:  ['reversed_transaction_id' => $original->id],
            remarks:   "Reversal: {$reason}"
        );
    }
}
```
> `PaymentTransactionService` follows the exact same `append/reverse` pattern.

---

## Long-Term Recommendations

1. **Event-Driven Ledger Writes** — Decouple with Laravel events (`DeliveryReceived` → `WriteStockTransactionOnDelivery`). Simplifies audit logging and notifications.
2. **Pessimistic Locking** — When migrating to PostgreSQL/MySQL, use `SELECT FOR UPDATE` in `append()` to prevent balance race conditions.
3. **Read Model for Dashboard** — Cache or snapshot expensive KPI aggregations (daily revenue, tank levels) every N minutes instead of computing per request.
4. **Multi-Station Path** — If needed later, add a `stations` table and scope all queries through `station_id`. The polymorphic design makes this non-breaking.
5. **SQLite → PostgreSQL Migration Path** — Keep all SQLite-specific SQL in `if (getDriverName() === 'sqlite')` blocks.
6. **Frozen API Versioning** — Keep `/api/v1/` frozen once live. Introduce `/api/v2/` for breaking changes only.
