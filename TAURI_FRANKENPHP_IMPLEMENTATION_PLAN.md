# FuelStationOS - Tauri + FrankenPHP Sidecar Implementation Plan

## Overview
Transform FuelStationOS into a single executable desktop application using **Tauri (Rust) + FrankenPHP (PHP Sidecar)** that packages the entire Laravel backend + Nuxt 3 frontend into a single installer. Users install one `.exe/.dmg/.AppImage` and get a fully functional desktop app with embedded Laravel backend.

**Key Requirements:**
- Move Laravel from root to `backend/` directory
- Single executable installer for Windows/macOS/Linux
- Embedded FrankenPHP sidecar runs Laravel backend
- Tauri manages frontend (Nuxt 3) + sidecar lifecycle
- Database backup/restore via USB for disaster recovery
- Works for both development (Docker backend + host frontend) and production (single executable)

## ✅ Project Cleanup (Completed)
Before implementation, unwanted/garbage/redundant/duplicate files were removed to ensure a clean, well-structured, reliable, maintainable, and sustainable project:

**Removed files:**
- `.dockerignore` - project-specific, not needed
- `.memwalrc` - unknown purpose, removed
- `NOTES:.txt` - redundant notes, removed
- `implementation_plan_bkup_v1.2.md` - old backup, replaced by this plan
- `hs_err_pid13027.log` - JVM crash log, removed
- `docker-issue.txt` - issue documentation, removed
- `steps-to-setup-making-build-executable-file.txt` - setup steps, removed
- `fuel_station_os.postman_collection.json` - Postman collection, removed
- `GENERIC_AGENTIC_WORKFLOW_GUIDE.md` - generic workflow, removed
- `.phpunit.result.cache` - PHPUnit cache, removed
- `combined_prompt.txt` - redundant, removed
- `recommended-structure.txt` - structure recommendations, removed
- `prompt.txt` - redundant, removed
- `.dsh/` - empty directory, removed

**Current project structure is now clean and ready for restructuring.**

## ✅ Documentation Updates (Completed)
- `[x]` `Architecture.md` — Updated to v4.0 with FrankenPHP sidecar architecture, new project structure, Docker services
- `[x]` `Tasks.md` — Added Phases 7-13 for migration, updated Phase 1, new recommended next steps
- `[x]` `Decisions.md` — Added 6 new architectural decisions (19-24) for Tauri/FrankenPHP
- `[x]` `README.md` — Rewritten as FuelStationOS project documentation
- `[x]` `TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md` — This file updated with current status

---

## Current Project Structure (Before)

```
/var/www/html/fuel_station_os/
├── app/                    # Laravel app (move to backend/)
├── bootstrap/
├── config/
├── database/
├── public/
├── routes/
├── storage/
├── resources/
├── frontend/               # Nuxt 3 + Tauri
├── src-tauri/              # Tauri config
├── docker/
├── docker-compose.yml
├── makefile
└── ...
```

---

## Target Project Structure (After Migration)

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
│   ├── app/
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
│   ├── package.json
│   └── nuxt.config.ts
├── src-tauri/                  # Tauri config (moved from root/src-tauri)
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

## Phase 1: Move Laravel to backend/ Directory

### 1.1 Move Files
```bash
# Create backend directory
mkdir -p backend

# Move all Laravel files except frontend/src-tauri
mv app bootstrap config database public routes storage resources backend/
mv artisan composer.json composer.lock phpunit.xml backend/
```

### 1.2 Update Paths
- Update `bootstrap/app.php` paths if needed
- Update `composer.json` autoload paths
- Update `public/index.php` autoload path
- Update `artisan` shebang if needed

### 1.3 Update Docker Configuration
- Update `docker/php/Dockerfile` WORKDIR to `/var/www/html/backend`
- Update volume mounts in `docker-compose.dev.yml` to use `./backend`

---

## Phase 2: Backend Sidecar (FrankenPHP)

### 2.1 Create FrankenPHP Configuration

**`backend/Caddyfile`**
```caddyfile
{
    admin off
    log { format json output stdout }
}

:8000 {
    root * /app/public
    php_server

    @laravel { path /api/* /storage/* /broadcasting/* }
    handle @laravel { php_server }

    file_server

    respond /health "OK" 200

    header Access-Control-Allow-Origin "*"
    header Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    header Access-Control-Allow-Credentials "true"
}
```

### 2.2 Create Worker Entry Point
**`backend/frankenphp-worker.php`** - Long-running PHP worker for stdin/stdout communication with Tauri sidecar

### 2.3 Update `backend/composer.json`
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/octane": "^2.0",
        "laravel/sanctum": "^4.0",
        "spatie/laravel-permission": "^6.0"
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ]
    }
}
```

### 2.4 Add Octane + FrankenPHP
```bash
composer require laravel/octane
php artisan octane:install --server=frankenphp
```

---

## Phase 3: Tauri Sidecar Management (Rust)

### 3.1 Sidecar Manager (`src-tauri/src/sidecar.rs`)
- Find backend directory (bundled vs development)
- Find PHP/FrankenPHP binary (bundled or system)
- Start/stop FrankenPHP worker process
- Handle stdout/stderr logging

### 3.2 Main Entry Point (`src-tauri/src/main.rs`)
- Initialize sidecar on startup
- Register Tauri commands for:
  - `start_laravel_sidecar`
  - `stop_laravel_sidecar`
  - `backup_database`
  - `restore_database`
  - `get_system_info`

### 3.3 Tauri Config (`tauri.conf.json`)
- Configure sidecar external binary
- Register backend resources in bundle
- Configure CSP for local API access
- Register external binary `php`

---

## Phase 4: Database Backup/Restore

### 4.1 Backend API (`backend/app/Http/Controllers/DatabaseController.php`)
- `GET /api/v1/database/info` - Database info
- `POST /api/v1/database/backup` - Download SQLite file
- `POST /api/v1/database/restore` - Upload and restore
- `GET /api/v1/database/backups` - List backups

### 4.2 Frontend Integration
- Tauri commands for save/restore dialogs
- File picker for backup/restore

---

## Phase 5: Development Environment

### 5.1 Docker Compose (`docker-compose.dev.yml`)
```yaml
services:
  backend:
    build: .
    dockerfile: docker/php/Dockerfile
    volumes:
      - ./backend:/var/www/html:delegated
      - ./vendor:/var/www/html/vendor
    ports:
      - "8000:8000"
    env_file: .env

  queue:
    # Queue worker

  scheduler:
    # Scheduler

  nginx:
    # Reverse proxy
```

### 4.2 Frontend Development
```bash
# Terminal 1: Docker backend
make dev

# Terminal 2: Frontend
cd frontend
pnpm tauri dev  # Connects to http://localhost:8000
```

---

## Phase 5: Production Build & Packaging

### 5.1 Build Script (`build/scripts/build-sidecar.sh`)
```bash
# 1. Build Laravel sidecar (composer install --no-dev + optimizations)
# 2. Build frontend (pnpm build)
# 3. Build Tauri (cargo build --release)
```

### 5.2 Packaging (`build/scripts/package-installer.sh`)
- **Windows**: NSIS installer (.exe)
- **macOS**: DMG + .app bundle
- **Linux**: AppImage + .deb

### 5.2 Resource Bundling
```json
// tauri.conf.json
"bundle": {
  "resources": [
    "backend/**/*",
    "!backend/vendor/**/*",
    "!backend/storage/logs/**/*"
  ],
  "externalBin": ["php"]
}
```

---

## Phase 6: Database Backup/Restore Flow

### User Workflow
1. **Backup**: Click "Backup" → Download `.sqlite` → Save to USB
2. **Restore**: Fresh install → Click "Restore" → Select `.sqlite` from USB → App reloads with data

### API Endpoints
- `POST /api/v1/database/backup` → Download `.sqlite`
- `POST /api/v1/database/restore` → Upload `.sqlite` → Replace DB → Reconnect

---

## Phase 7: Frontend Integration (Nuxt + Tauri)

### 7.1 API Configuration
```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1'
    }
  }
})
```

### 7.2 Tauri Commands
```typescript
// frontend/composables/useTauri.ts
export const useTauri = () => {
  const backupDatabase = () => invoke('backup_database')
  const restoreDatabase = () => invoke('restore_database')
  const getSystemInfo = () => invoke('get_system_info')
}
```

---

## Migration Checklist

### Pre-Migration
- [ ] Backup current project
- [ ] Test current Docker setup works
- [ ] Verify `pnpm tauri dev` works with Docker backend

### Migration Steps
1. [ ] Move Laravel to `backend/`
2. [ ] Update all paths in Docker, docker-compose, Makefile
2. [ ] Create `backend/Caddyfile` and `frankenphp-worker.php`
3. [ ] Add Octane + FrankenPHP to backend
4. [ ] Create Tauri sidecar manager (Rust)
4. [ ] Update `tauri.conf.json` for sidecar
5. [ ] Create DatabaseController + routes
6. [ ] Update Tauri commands for backup/restore
6. [ ] Create build/packaging scripts
7. [ ] Update `docker-compose.dev.yml`
7. [ ] Test development workflow
7. [ ] Test production build

### Post-Migration
- [ ] Test `make dev` (Docker backend + host frontend)
- [ ] Test `pnpm tauri dev` (connects to Docker backend)
- [ ] Test production build (`cargo build --release`)
- [ ] Test installer creation
- [ ] Test database backup/restore flow

---

## File Creation Checklist

### Backend Files (New)
- [ ] `backend/Caddyfile`
- [ ] `backend/frankenphp-worker.php`
- [ ] `backend/.env.example` (updated paths)

### Tauri Files (New/Update)
- [ ] `src-tauri/src/sidecar.rs` (Sidecar manager)
- [ ] `src-tauri/src/main.rs` (Entry + commands)
- [ ] `src-tauri/tauri.conf.json` (Config)
- [ ] `src-tauri/Cargo.toml` (Dependencies)

### Build Scripts
- [ ] `build/scripts/build-sidecar.sh`
- [ ] `build/scripts/package-installer.sh`
- [ ] `build/installers/windows.nsi`
- [ ] `build/installers/macos.dmg`
- [ ] `build/installers/linux/`

### Dev Ops
- [ ] `docker-compose.dev.yml`
- [ ] `.env.example` (updated)
- [ ] `Makefile` updates

### Documentation
- `[x]` `TAURI_FRANKENPHP_IMPLEMENTATION_PLAN.md` (this plan)
- `[x]` `Architecture.md` (v4.0 updated)
- `[x]` `Tasks.md` (Phases 7-13 added)
- `[x]` `Decisions.md` (decisions 19-24 added)
- `[x]` `README.md` (FuelStationOS documentation)
- `[ ]` `DEPLOYMENT.md`
- `[ ]` `BACKUP_RESTORE_GUIDE.md`

---

## Verification Steps

### Development Mode
```bash
# Terminal 1
make dev  # Starts Docker backend

# Terminal 2
cd frontend && pnpm tauri dev  # Connects to localhost:8000
```

### Production Build
```bash
./build/scripts/build-sidecar.sh build
./build/scripts/package-installer.sh all
```

### Database Backup/Restore Test
1. Create data in app
2. Backup → Save to USB
3. Fresh install app
4. Restore from USB
5. Verify data restored

---

## Resources

- [FrankenPHP Documentation](https://frankenphp.dev/docs/)
- [Laravel Octane](https://octane.laravel.com/docs)
- [Tauri Sidecar](https://tauri.app/v1/guides/building/sidecar/)
- [Tauri v2 Migration](https://tauri.app/v2/guides/migration/)

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Path issues in bundled app | Use `std::env::current_exe()` to find base path |
| PHP binary not found | Bundle PHP/FrankenPHP in installer |
| Database locking | Use WAL mode for SQLite |
| Large installer size | Exclude dev files, use `cargo strip` |
| Cross-platform paths | Use `std::path::PathBuf` consistently |

---

**Estimated Timeline**: 2-3 days for complete implementation

**Ready to implement?** Start with Phase 1: Move Laravel to backend/ directory.