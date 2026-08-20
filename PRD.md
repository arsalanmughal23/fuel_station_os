# Product Requirements Document (PRD)
**Version:** 1.0 | **Last Updated:** August 2026

## Project Overview
**FuelStationOS** is a single-station desktop fuel management system. It manages fuel tanks, nozzles, deliveries, products, sales, accounts, and inventory with an **append-only ledger** architecture for full auditability. Designed to run offline on a local machine via a Dockerized backend and a Tauri desktop wrapper.

---

## User Roles
| Role | Access |
|------|--------|
| `admin` | Full access to all features and settings |
| `manager` | All operational features; no user management |
| `staff` | POS sales and nozzle readings only |

---

## Core Feature Modules

### Fuel Setup
- Manage fuel types (Petrol, Diesel, CNG, etc.) with current price tracking
- Manage underground storage tanks (capacity, fuel type assignment)
- Upload and manage dip chart calibrations (cm → liters linear interpolation)
- Manage dispensing nozzles assigned to tanks

### Operations
- Record nozzle readings (opening & closing meter) to compute liters sold
- Record deep readings (physical dip measurement) to compute variance vs. system stock

### Sales & POS
- Create sales with mixed line items: fuel nozzle readings and/or shop products
- Auto-compute total amount from line items; handle cash/card/bank payments
- Generate and view transaction history and receipts

### Shop Inventory
- Product catalog with categories, unit types, and pricing
- Price change history for all products and fuel types
- Stock tracking via append-only ledger entries

### Procurement
- Create and manage fuel purchase orders (with status lifecycle)
- Record deliveries against purchase orders; auto-update tank stock via ledger

### Accounts
- Manage accounts: Distributor, Customer, Employee, Owner types
- Account balance is derived from payment ledger (no manual balance edits)
- Record standalone payments (salary, utility, maintenance, etc.)

### Reporting & Dashboard
- Dashboard KPIs: total volume sold, revenue, stock variance, tank levels
- Daily and monthly reports: sales, volume, deliveries, adjustments
- Audit trail: full ledger history for stock and payments

### Settings
- User management (create, update, deactivate)
- Role and permission assignment

---

## Non-Functional Requirements
- **Auditability:** All inventory and payment changes via append-only ledger. No deletions or edits on ledger rows.
- **Offline-first:** Must work without internet connectivity.
- **Deployment:** Single Docker Compose stack + Tauri desktop wrapper.
- **Security:** Sanctum token auth, RBAC via `spatie/laravel-permission`.
- **Performance:** All list queries must avoid N+1; use eager loading.
