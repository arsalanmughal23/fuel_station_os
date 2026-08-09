# Implementation Plan: Backend Scaffolding

You are correct! In the previous iteration, I addressed the structural schema bugs, Docker issues, and routes, but stopped short of generating the actual missing files listed in the original review. This plan will scaffold the rest of the backend architecture.

## Proposed Changes

### 1. Seeders & Factories
- **[MODIFY] `DatabaseSeeder.php`**: Update to call `RoleSeeder` and fix the `username` constraint bug.
- **[NEW] `RoleSeeder.php`**: Seed Spatie roles (`admin`, `manager`, `staff`).
- **[MODIFY] `UserFactory.php`**: Add `username` generation to fix the database constraint issue.

### 2. Services Layer
Since Laravel doesn't have a native `make:service` command, I will run a script to generate the `app/Services/` directory and scaffold the 13 required services:
- **Ledger**: `StockTransactionService`, `PaymentTransactionService`
- **Operations**: `DeliveryService`, `NozzleReadingService`, `DeepReadingService`
- **Sales**: `SaleService`
- **Inventory**: `StockAdjustmentService`, `ProductService`
- **Setup**: `FuelTypeService`, `TankCalibrationService`, `AccountService`
- **Other**: `PurchaseOrderService`, `ReportService`

### 3. API Controllers, Requests & Resources
I will execute a bash script within the Docker container that uses Artisan to generate the following for each major entity (`FuelType`, `Tank`, `Nozzle`, `Product`, `Account`, `PurchaseOrder`, `Sale`, `Delivery`, `NozzleReading`, `DeepReading`, `StockAdjustment`, `AuthController`):
- API Controllers (`php artisan make:controller [Name]Controller --api`)
- Form Requests (`php artisan make:request Store[Name]Request` & `Update[Name]Request`)
- API Resources (`php artisan make:resource [Name]Resource`)

### 4. Policies (Authorization)
I will generate policies for the major models to integrate with Spatie roles:
- `php artisan make:policy [Name]Policy --model=[Name]`

## User Review Required
> [!IMPORTANT]
> This plan will automatically generate over **40 boilerplate files** (Controllers, Requests, Resources, Services) using Laravel Artisan commands and basic stub files. It will not fully implement the *internal business logic* for each function (as that requires many iterative steps), but it will create the entire correct structure and wiring so the system compiles and the API endpoints exist.

## Verification Plan
1. Run `docker compose exec backend php artisan migrate:fresh --seed` to ensure the new Seeders and UserFactory work perfectly.
2. Run `docker compose exec backend php artisan route:list` to verify all API endpoints map correctly to the newly created Controllers.
