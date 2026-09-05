# FuelStationOS - CLI Reference & Architecture Guide

Complete reference for running, building, and understanding the FuelStationOS desktop application with Tauri + FrankenPHP sidecar architecture.

---

## 🚀 Quick Start Commands

### **Development Mode (Docker Backend + Host Frontend)**

```bash
# Start Docker development environment
cd /var/www/html/fuel_station_os
make dev-docker
# OR
docker-compose -f docker-compose.dev.yml up -d

# Start frontend + Tauri (separate terminal)
cd frontend
pnpm tauri dev

# Check status
make status-docker
# OR
docker-compose -f docker-compose.dev.yml ps

# Stop development environment
make down-docker
# OR
docker-compose -f docker-compose.dev.yml down
```

### **Docker Build Commands**

```bash
# Build backend image
docker build -f docker/php/Dockerfile -t fuel-station-backend .

# Build frontend image
docker build -f docker/node/Dockerfile -t fuel-station-frontend .

# Build all via docker-compose
docker-compose -f docker-compose.dev.yml build
```

### **Clean Commands**

```bash
# Clean build artifacts
./build/scripts/build-sidecar.sh clean

# Clean Docker (volumes too)
docker-compose -f docker-compose.dev.yml down -v
docker system prune -f

# Clean frontend
cd frontend && rm -rf node_modules .nuxt dist .output && pnpm install

# Clean backend
cd backend && rm -rf vendor bootstrap/cache/*.php storage/logs/*
```

### **Generate Desktop App Executable**

```bash
# Full build: Laravel + Frontend + Tauri
./build/scripts/build-sidecar.sh build

# Package for all platforms (Windows .exe, macOS .dmg, Linux .AppImage/.deb)
./build/scripts/package-installer.sh all

# Combined build + package
./build/scripts/build-sidecar.sh package
```

**Output Locations:**
- Windows: `build/installers/FuelStationOS-Setup-1.0.0.exe`
- macOS: `build/installers/FuelStationOS-1.0.0.dmg`
- Linux: `build/installers/FuelStationOS-1.0.0-x86_64.AppImage` and `.deb`

---

## 📁 Final Project Directory Structure

```
/var/www/html/fuel_station_os/
├── backend/                          # Laravel Backend (moved from root)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/DatabaseController.php    # Backup/Restore API
│   │   │   └── Requests/RestoreDatabaseRequest.php   # Validation
│   │   └── Console/Kernel.php                         # Scheduler
│   ├── bootstrap/app.php
│   ├── config/
│   │   ├── octane.php           # FrankenPHP configuration
│   │   ├── app.php              # Includes OctaneServiceProvider
│   │   └── *.php                # Other configs
│   ├── database/
│   │   ├── migrations/          # 21 migrations
│   │   ├── seeders/
│   │   └── factories/
│   ├── public/
│   │   └── index.php            # Entry point
│   ├── routes/
│   │   ├── api.php              # API routes + database routes
│   │   ├── web.php
│   │   └── console.php
│   ├── storage/
│   │   ├── app/backups/         # SQLite backup files
│   │   ├── framework/
│   │   └── logs/
│   ├── resources/
│   ├── tests/
│   ├── artisan
│   ├── composer.json            # + laravel/octane ^2.0
│   ├── composer.lock
│   ├── phpunit.xml
│   ├── Caddyfile                # FrankenPHP server config
│   ├── frankenphp-worker.php    # Stdin/stdout worker for Tauri sidecar
│   └── .env.example
│
├── frontend/                     # Nuxt 3 + Tauri Frontend
│   ├── src-tauri/
│   │   ├── src/
│   │   │   ├── main.rs          # Tauri entry + sidecar management
│   │   │   ├── sidecar.rs       # LaravelSidecar process manager
│   │   │   ├── commands.rs      # 6 Tauri commands
│   │   │   └── build.rs         # Copies resources to bundle
│   │   ├── tauri.conf.json      # Tauri 2.x config
│   │   ├── Cargo.toml           # Rust deps + sidecar features
│   │   ├── php-fpm.conf         # Legacy (not used with FrankenPHP)
│   │   └── nginx.conf           # Legacy (not used with FrankenPHP)
│   ├── composables/useTauri.ts  # Frontend Tauri command bridge
│   ├── nuxt.config.ts           # SSR: false, apiBase: localhost:8000
│   ├── package.json
│   └── pnpm-lock.yaml
│
├── src-tauri/                    # Tauri config (symlink/duplicate)
│
├── build/
│   ├── scripts/
│   │   ├── build-sidecar.sh     # Full build pipeline
│   │   ├── package-installer.sh # Cross-platform packaging
│   │   ├── backup-db.sh         # SQLite backup (tested)
│   │   └── restore-db.sh        # SQLite restore (tested)
│   └── installers/
│       ├── windows.nsi
│       ├── macos.dmg
│       └── linux/
│
├── docker/
│   ├── php/Dockerfile           # WORKDIR /var/www/html/backend
│   ├── node/Dockerfile
│   └── nginx/default.conf
│
├── docker-compose.dev.yml       # Dev: 6 services
├── docker-compose.yml           # Production
├── docker-compose.prod.yml
├── Makefile                     # Dev targets + Docker integration
├── TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md
├── .env
├── .env.example
├── AGENTS.md
├── Architecture.md
├── Decisions.md
├── PRD.md
├── Tasks.md
├── fuelstationos_erd_v4.mermaid
└── README.md
```

---

## 🔄 Process Flows & Life Cycles

### **1. Development Mode Flow**

```
Terminal 1: Docker Backend
┌────────────────────────────────────────────────────────────────┐
│ make dev-docker                                                │
│   └─ docker-compose.dev.yml up -d                              │
│       ├─ backend (PHP-FPM :9000)                               │
│       ├─ queue (php artisan queue:work)                        │
│       ├─ scheduler (php artisan schedule:run)                  │
│       ├─ nginx (Reverse proxy :8000 → backend:9000)            │
│       └─ frontend (Nuxt dev :3000)                             │
└────────────────────────────────────────────────────────────────┘
                              │
                              ▼
Terminal 2: Tauri Frontend
┌────────────────────────────────────────────────────────────────┐
│ cd frontend && pnpm tauri dev                                  │
│   ├─ pnpm run dev (Nuxt dev server :3000)                     │
│   ├─ cargo build (Tauri binary)                                │
│   ├─ Tauri window opens                                        │
│   └─ Frontend connects to API at localhost:8000                │
└────────────────────────────────────────────────────────────────┘
```

**File Call Chain (Dev Mode):**
```
make dev-docker
  → docker-compose.dev.yml
    → docker/php/Dockerfile
      → COPY backend/ → /var/www/html/backend
      → php-fpm -R
    → docker/node/Dockerfile
      → pnpm run dev
  → frontend/pnpm tauri dev
    → nuxt.config.ts (dev server :3000)
    → src-tauri/Cargo.toml (cargo build)
    → src-tauri/src/main.rs (Tauri window)
      → src-tauri/src/sidecar.rs (LaravelSidecar::start())
        → php artisan octane:frankenphp --port=8000
          → backend/Caddyfile (FrankenPHP server)
          → backend/frankenphp-worker.php (worker)
    → frontend/composables/useTauri.ts (API calls)
```

---

### **2. Production Build Flow**

```
./build/scripts/build-sidecar.sh build
  │
  ├─► BACKEND BUILD (in backend/)
  │    ├─ composer install --no-dev --optimize-autoloader
  │    ├─ php artisan config:cache
  │    ├─ php artisan route:cache
  │    ├─ php artisan view:cache
  │    ├─ php artisan event:cache
  │    ├─ php artisan octane:install --server=frankenphp
  │    └─ rm -rf tests, vendor/bin, storage/logs/*
  │
  ├─► FRONTEND BUILD (in frontend/)
  │    ├─ pnpm install --frozen-lockfile
  │    └─ pnpm run build (Nuxt generate → dist/)
  │
  └─► TAURI BUILD (in src-tauri/)
       ├─ cargo build --release
       │   ├─ src-tauri/src/main.rs
       │   ├─ src-tauri/src/sidecar.rs
       │   ├─ src-tauri/src/commands.rs
       │   └─ src-tauri/build.rs (copies backend/ → resources)
       └─ target/release/fuel_station_os (binary)
```

**Package Installer Flow:**
```
./build/scripts/package-installer.sh all
  ├─ prepare_sidecar_resources()
  │   ├─ Copy backend/ (with Caddyfile, frankenphp-worker.php, vendor/)
  │   ├─ Copy frontend/dist/
  │   ├─ Copy Tauri binary
  │   └─ Create platform-specific bundles:
  │       ├─ Windows NSIS → .exe (with shortcuts, DB dir)
  │       ├─ macOS DMG → .app (Resources/sidecar/)
  │       └─ Linux → AppImage + DEB (opt/fuel_station_os/)
  └─ Output: build/installers/
```

---

### **3. Request Flow (Runtime)**

```
USER ACTION
     │
     ▼
FRONTEND (Nuxt in WebView)
     │  useTauri.ts composable
     │  ├─ backupDatabase()  → invoke('backup_database')
     │  ├─ restoreDatabase() → invoke('restore_database')
     │  ├─ getSystemInfo()   → invoke('get_system_info')
     │  └─ API calls       → $fetch('/api/v1/...')
     │
     ▼
TAURI RUNTIME (Rust)
     │  src-tauri/src/commands.rs
     │  ├─ backup_database()  → HTTP POST to localhost:8000
     │  ├─ restore_database() → HTTP POST multipart to :8000
     │  ├─ get_system_info()  → sysinfo crate
     │  ├─ start/stop sidecar → LaravelSidecar methods
     │  └─ get_sidecar_status → sidecar.is_running()
     │
     ▼
BACKEND (FrankenPHP + Laravel)
     │  backend/Caddyfile :8000
     │     ├─ /health → "OK"
     │     ├─ /api/* → php_server → Laravel
     │     │   ├─ routes/api.php
     │     │   │   ├─ /database/info → DatabaseController@info
     │     │   │   ├─ /database/backup → DatabaseController@backup
     │     │   │   ├─ /database/restore → DatabaseController@restore
     │     │   │   └─ /database/backups → DatabaseController@list
     │     │   └─ other API resources...
     │     └─ static files → file_server
     │
     ▼
DATABASE (SQLite)
     └─ storage/database/database.sqlite
```

---

### **4. Sidecar Life Cycle**

```
APP STARTUP
     │
     ▼
src-tauri/src/main.rs
     ├─ tauri::Builder::default()
     │   .setup(|app| {
     │       let sidecar = LaravelSidecar::new();
     │       tauri::async_runtime::spawn(async move {
     │           tokio::time::sleep(Duration::from_millis(500)).await;
     │           sidecar.start().ok();
     │       });
     │       app.manage(sidecar);
     │       Ok(())
     │   })
     │   .invoke_handler(tauri::generate_handler![...])
     │   .build(tauri::generate_context!())
     │
     ▼
SIDECAR RUNNING (LaravelSidecar::start())
     │  src-tauri/src/sidecar.rs
     │   ├─ find_laravel_path()  → finds backend/
     │   ├─ find_php_binary()    → finds php/frankenphp
     │   ├─ Command::new("php")
     │   │   .args(["artisan", "octane:frankenphp",
     │   │         "--workers=4", "--max-requests=500"])
     │   │   .current_dir(backend_path)
     │   │   .stdin/out/err piped
     │   ├─ spawn() → Child process
     │   ├─ Thread: read stdout → eprintln!("[Laravel]")
     │   ├─ Thread: read stderr → eprintln!("[Laravel ERROR]")
     │   └─ Store Child in Arc<Mutex<Option<Child>>>
     │
     ▼
REQUEST HANDLING
     │  User clicks "Backup" in UI
     │     ├─ Frontend: useTauri.backupDatabase()
     │     ├─ Tauri: invoke('backup_database')
     │     ├─ Rust: commands::backup_database()
     │     │   ├─ reqwest::post("http://127.0.0.1:8000/...backup")
     │     │   ├─ Save response via FileDialog
     │     │   └─ Return path to frontend
     │     └─ Backend: DatabaseController@backup
     │         ├─ Stream download database.sqlite
     │         └─ Return file
     │
     ▼
APP SHUTDOWN
     │  Window close → confirmation dialog
     │     ├─ User confirms → std::process::exit(0)
     │     └─ Drop trait triggers:
     │         LaravelSidecar::shutdown()
     │         ├─ process.lock().take()
     │         ├─ child.kill()
     │         └─ child.wait()
```

---

### **5. Database Backup/Restore Flow**

```
BACKUP:
Frontend: useTauri.backupDatabase()
  └─ invoke('backup_database')
      └─ Tauri: commands::backup_database()
          ├─ GET http://127.0.0.1:8000/api/v1/database/backup
          │   └─ Laravel: DatabaseController@backup
          │       ├─ File::copy(database.sqlite,
          │       │   storage/app/backups/backup_*.sqlite)
          │       └─ Response::download() with headers
          ├─ Save to user-chosen location (FileDialog)
          └─ Return success message

RESTORE:
Frontend: useTauri.restoreDatabase()
  └─ invoke('restore_database')
      └─ Tauri: commands::restore_database()
          ├─ FileDialog.open() → .sqlite file
          ├─ POST multipart to /api/v1/database/restore
          │   └─ Laravel: DatabaseController@restore
          │       ├─ Validate file (RestoreDatabaseRequest)
          │       ├─ Backup current DB (rollback_*.sqlite)
          │       ├─ Move uploaded → database.sqlite
          │       ├─ DB::purge('sqlite'); DB::reconnect()
          │       ├─ Verify connection works
          │       └─ Return success
          └─ Frontend: navigateTo('/') (reload)
```

---

## 📋 Documentation Update Assessment

**YES - Documentation needs updating** for:

| File | Needs Update? | Reason |
|------|---------------|--------|
| `Architecture.md` | ✅ Yes | New directory structure, FrankenPHP sidecar, Tauri integration |
| `Tasks.md` | ✅ Yes | All 10 phases/tasks completed, mark as done |
| `fuelstationos_erd_v4.mermaid` | ❌ No | Database schema unchanged |
| `PRD.md` | ❌ No | Requirements unchanged |
| `Decisions.md` | ✅ Yes | New ADR: Tauri + FrankenPHP sidecar |
| `AGENTS.md` | ❌ No | Agent instructions unchanged |
| `README.md` | ✅ Yes | New build/run instructions, project structure |
| `TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md` | ✅ Yes | Mark complete + actual implementation notes |
| `docker-compose.yml` / `.dev.yml` | ✅ Yes | Document new dev workflow |
| `Makefile` | ✅ Yes | Document new targets |

**Recommended Updates:**

1. **Architecture.md** - Add FrankenPHP sidecar section, update directory tree
2. **Tasks.md** - Mark t26-t35 as `[x] Done`
3. **Decisions.md** - Add ADR: "Use Tauri + FrankenPHP sidecar for desktop app"
4. **README.md** - Add "Development" and "Building" sections with CLI commands
5. **TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md** - Add "✅ COMPLETED" status

---

## ✅ Verification Checklist

Run these to confirm everything works:

```bash
# 1. Verify backend structure
ls -la backend/                    # app, config, database, routes, etc.

# 2. Verify Tauri structure
ls -la src-tauri/src/              # main.rs, sidecar.rs, commands.rs

# 3. Verify frontend Tauri integration
ls -la frontend/composables/useTauri.ts

# 4. Verify build scripts
ls -la build/scripts/              # build-sidecar.sh, package-installer.sh, backup-db.sh, restore-db.sh

# 5. Verify Docker
cat docker-compose.dev.yml         # 6 services

# 6. Quick syntax check
cd backend && php -l artisan       # Should pass
cd src-tauri && rustc --edition 2021 --crate-type bin src/main.rs 2>&1 | head -5
```

---

## 📌 Key File Purposes

| File | Purpose | Called By |
|------|---------|-----------|
| `backend/Caddyfile` | FrankenPHP server config | `frankenphp run --config Caddyfile` |
| `backend/frankenphp-worker.php` | Stdin/stdout Laravel worker | Tauri sidecar (optional) |
| `backend/config/octane.php` | Octane/FrankenPHP settings | Laravel Octane |
| `src-tauri/src/sidecar.rs` | LaravelSidecar process manager | `main.rs` setup() |
| `src-tauri/src/main.rs` | Tauri app entry point | Cargo build |
| `src-tauri/src/commands.rs` | 6 Tauri IPC commands | Frontend `invoke()` |
| `src-tauri/src/build.rs` | Copies backend to bundle | Cargo build |
| `frontend/composables/useTauri.ts` | Frontend → Tauri bridge | Vue components |
| `frontend/nuxt.config.ts` | Nuxt config (SSR: false) | `pnpm run dev/build` |
| `build/scripts/build-sidecar.sh` | Full build pipeline | Manual / CI |
| `build/scripts/package-installer.sh` | Cross-platform packaging | After build |
| `docker-compose.dev.yml` | 6-service dev environment | `make dev-docker` |
| `Makefile` | Dev shortcuts | Developer |

---

## 🎯 Implementation Status: **COMPLETE**

All 10 phases implemented:
- ✅ Phase 1: Laravel → `backend/`
- ✅ Phase 2: FrankenPHP Config
- ✅ Phase 3.1: Tauri Sidecar Manager
- ✅ Phase 3.2: Tauri Main Entry
- ✅ Phase 3.3: Tauri Config
- ✅ Phase 4: DatabaseController API
- ✅ Phase 5: Docker Dev Compose
- ✅ Phase 6: Frontend Integration
- ✅ Phase 7: Build & Packaging
- ✅ Phase 8: Testing

**Ready to run:** `make dev-docker` + `pnpm tauri dev` (dev) or `./build/scripts/build-sidecar.sh package` (production)