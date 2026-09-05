# FuelStationOS

<p align="center">
  <strong>Single-station desktop fuel management system with append-only ledger architecture</strong>
</p>

<p align="center">
  <a href="#tech-stack"><img src="https://img.shields.io/badge/stack-Laravel%2012%20%7C%20Nuxt%203%20%7C%20Tauri%20%7C%20FrankenPHP-blue" alt="Tech Stack"></a>
  <a href="#architecture"><img src="https://img.shields.io/badge/architecture-append--only%20ledger-green" alt="Architecture"></a>
  <a href="#database"><img src="https://img.shields.io/badge/database-SQLite%20(WAL)-orange" alt="Database"></a>
  <a href="#desktop"><img src="https://img.shields.io/badge/desktop-Tauri%20%2B%20FrankenPHP%20sidecar-purple" alt="Desktop"></a>
</p>

---

## Overview

FuelStationOS is a **single-station desktop application** for managing fuel station operations — tanks, nozzles, fuel sales, shop inventory, procurement, accounts, and reporting. Built as a **local-first desktop app** with an embedded SQLite database and **Tauri + FrankenPHP sidecar** architecture for a single executable installer (Windows `.exe`, macOS `.dmg`, Linux `.AppImage`).

**Key capabilities:**
- **Fuel Setup** — Tanks, nozzles, calibration charts (dip cm → liters), fuel types
- **Operations** — Nozzle readings (opening/closing), deep readings (physical dips)
- **Sales & POS** — Fuel + shop products, multiple payment methods, receipts
- **Shop Inventory** — Products, pricing, price history, stock adjustments
- **Procurement** — Purchase orders, fuel deliveries, stock-in ledger
- **Accounts** — Distributors, customers, employees, owner with payment ledger
- **Reporting** — Dashboard KPIs, daily/monthly sales, volume, variance, delivery reports

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 12 (PHP 8.4+) |
| **Frontend** | Nuxt 3 (Vue 3) |
| **Auth** | Laravel Sanctum (token-based) |
| **RBAC** | `spatie/laravel-permission` |
| **Database** | SQLite (embedded, WAL mode, single file) |
| **Desktop Wrapper** | **Tauri (Rust) + FrankenPHP Sidecar** |
| **PHP Runtime** | FrankenPHP via Laravel Octane (long-running worker) |
| **Containerization** | Docker + Docker Compose (development only) |
| **Queue** | Laravel Queue (`database` driver) |

> **Production:** No Docker. Single Tauri executable bundles FrankenPHP sidecar + compiled Nuxt frontend.

---

## Architecture

### Project Structure (Post-Migration)
```
fuel_station_os/
├── backend/                    # Laravel backend
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── routes/
│   ├── storage/
│   ├── resources/
│   ├── Caddyfile               # FrankenPHP config
│   ├── frankenphp-worker.php   # Sidecar worker entry point
│   ├── composer.json
│   └── .env.example
├── frontend/                   # Nuxt 3 + Tauri
│   ├── src-tauri/
│   │   ├── src/
│   │   │   ├── main.rs
│   │   │   ├── sidecar.rs
│   │   │   └── commands/
│   │   ├── Cargo.toml
│   │   └── tauri.conf.json
├── build/                      # Build & packaging scripts
│   ├── scripts/
│   │   ├── build-sidecar.sh
│   │   └── package-installer.sh
│   └── installers/
│       ├── windows.nsi
│       ├── macos.dmg
│       └── linux/
├── docker/                     # Dev Docker files
├── docker-compose.dev.yml      # Development: Docker backend + host frontend
└── README.md
```

### Core Architectural Principles
1. **Append-only ledgers** — `stock_transactions` and `payment_transactions` are immutable; reversals via new rows
2. **Polymorphic relationships** — `stockable` (Tank/Product), `priceable` (FuelType/Product)
3. **XOR constraints** — Enforced via SQLite triggers (exactly one FK set per ledger row)
4. **Derived columns** — `calculated_stock`, `current_balance` synced via model events
5. **Services own business logic** — Controllers are thin HTTP adapters only

📖 **Full architecture details:** [`Architecture.md`](./Architecture.md)

---

## Database Schema (21 Tables)

| # | Table | Purpose |
|---|-------|---------|
| 1 | `users` | System users |
| 2-5 | `roles`, `permissions`, `model_has_roles`, `model_has_permissions` | Spatie RBAC |
| 6 | `fuel_types` | Petrol, Diesel, CNG, etc. |
| 7 | `tanks` | Underground storage tanks |
| 8 | `tank_calibrations` | Dip chart: cm → liters |
| 9 | `deep_readings` | Physical dip measurements |
| 10 | `nozzles` | Dispensing nozzles |
| 11 | `nozzle_readings` | Opening/closing meter readings |
| 12 | `accounts` | Distributors, customers, employees, owner |
| 13 | `purchase_orders` | Fuel purchase orders |
| 14 | `deliveries` | Fuel delivery records |
| 15 | `products` | Shop items (lubricants, accessories, etc.) |
| 16 | `sales` | Sale transactions |
| 17 | `sale_items` | Line items (fuel XOR product) |
| 18 | `stock_adjustments` | Polymorphic inventory adjustments |
| 19 | `stock_transactions` | **Append-only inventory ledger** |
| 20 | `payment_transactions` | **Append-only payment ledger** |
| 21 | `price_history` | Polymorphic price change log |

📖 **Visual ERD:** [`fuelstationos_erd_v4.mermaid`](./fuelstationos_erd_v4.mermaid)

---

## Development Workflow

### Prerequisites
- Docker + Docker Compose
- Node.js 20+ & pnpm (for frontend development)
- Rust toolchain (for Tauri development)

### Start Development Environment
```bash
# Terminal 1: Start Docker backend (FrankenPHP + queue + scheduler + nginx)
make dev

# Terminal 2: Start Tauri frontend (connects to http://localhost:8000)
cd frontend && pnpm tauri dev
```

### Development Commands
```bash
make dev              # Start Docker dev stack
make dev-down         # Stop Docker dev stack
make test             # Run PHP tests
make lint             # Run PHP linting
cd frontend && pnpm dev      # Nuxt dev server only
cd frontend && pnpm tauri dev # Tauri dev (connects to Docker backend)
```

---

## Production Build

### Single Executable Installer
```bash
# Build everything (Laravel optimized, Nuxt built, Tauri compiled)
./build/scripts/build-sidecar.sh build

# Package installers for all platforms
./build/scripts/package-installer.sh all
```

**Outputs:**
- **Windows:** `fuel-station-os-setup.exe` (NSIS)
- **macOS:** `FuelStationOS.dmg` + `.app` bundle
- **Linux:** `FuelStationOS.AppImage` + `.deb`

### Database Backup/Restore (User Workflow)
1. **Backup:** Click "Backup" in app → Save `.sqlite` file to USB
2. **Restore:** Fresh install → Click "Restore" → Select `.sqlite` from USB → App reloads with data

---

## Documentation

| Document | Description |
|----------|-------------|
| [`Architecture.md`](./Architecture.md) | System architecture, tech stack, database schema, service layer |
| [`Tasks.md`](./Tasks.md) | Progress tracker, phases, checklists, next steps |
| [`Decisions.md`](./Decisions.md) | Key architectural decisions (immutable rules) |
| [`PRD.md`](./PRD.md) | Product requirements document |
| [`TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md`](./TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md) | Detailed migration plan for Tauri + FrankenPHP |
| [`fuelstationos_erd_v4.mermaid`](./fuelstationos_erd_v4.mermaid) | Visual ERD with all columns, FKs, constraints |

---

## License

MIT License — see [`LICENSE`](./LICENSE) for details.

---

## Contributing

This is a single-station desktop fuel management system. See [`Tasks.md`](./Tasks.md) for current progress and [`Decisions.md`](./Decisions.md) for architectural constraints before contributing.