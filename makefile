# Makefile for Fuel Station OS
# ============================
# Usage: make [target]
# Structure: backend/ (Laravel + FrankenPHP) + frontend/ (Nuxt 3 + Tauri)

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RED    := $(shell tput -Txterm setaf 1)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# Read APP_PORT from backend/.env file if it exists
ifneq ($(wildcard backend/.env),)
    include backend/.env
    export
    APP_PORT := $(APP_PORT)
endif

# Default port if not set in .env
APP_PORT ?= 8000

.PHONY: help dev dev-docker dev-down setup setup-docker rebuild logs shell tinker migrate fresh seed test clear status status-docker down down-docker clean full-clean rmi update-deps prod prod-build prod-down prod-logs prod-status prod-clean health tauri-dev tauri-build tauri-package

help: ## Show this help message
	@echo ''
	@echo '${GREEN}Fuel Station OS${RESET} - Development Commands'
	@echo '========================================'
	@grep -E '^[a-z].*:.*?## .*$$' $(MAKEFILE_LIST) | grep -v 'prod' | grep -v 'prune' | grep -v 'tauri' | grep -v '^help' | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${GREEN}%-20s${RESET} %s\n", $$1, $$2}'
	@echo ''
	@echo '${YELLOW}Tauri Commands${RESET}'
	@echo '========================================'
	@grep -E '^tauri.*:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-20s${RESET} %s\n", $$1, $$2}'
	@echo ''
	@echo '${YELLOW}Production Commands${RESET}'
	@echo '========================================'
	@grep -E '^prod.*:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-20s${RESET} %s\n", $$1, $$2}'
	@echo ''
	@echo '${RED}Utility Commands${RESET}'
	@echo '========================================'
	@grep -E '^[a-zA-Z_-]+-prune:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = "-prune:.*?## "}; {printf "  ${RED}%-20s${RESET} %s\n", $$1, $$2}'

# Development Commands

dev: ## Start development environment (Docker backend + FrankenPHP on port 8000)
	@echo "${GREEN}🚀 Starting development environment (FrankenPHP backend)...${RESET}"
	@docker compose -f docker-compose.dev.yml up -d
	@echo "${YELLOW}⏳ Waiting for containers to be ready...${RESET}"
	@sleep 5
	@$(MAKE) setup-docker
	@echo ""
	@echo "${GREEN}🌐 Access your application:${RESET}"
	@echo "  Backend API: http://localhost:8000/api/v1"
	@echo "  Health:      http://localhost:8000/health"
	@echo "  Frontend:    Run 'make tauri-dev' in another terminal"
	@echo ""
	@$(MAKE) status-docker

dev-docker: ## Alias for dev (uses docker-compose.dev.yml)
	@$(MAKE) dev

tauri-dev: ## Start Tauri development (requires Docker backend running on port 8000)
	@echo "${GREEN}🚀 Starting Tauri development...${RESET}"
	@cd frontend && pnpm tauri dev

tauri-build: ## Build Tauri production app (requires Rust toolchain)
	@echo "${GREEN}🏗️ Building Tauri production app...${RESET}"
	@cd frontend && pnpm tauri build

tauri-package: ## Build and package Tauri installers (NSIS, DMG, AppImage)
	@echo "${GREEN}📦 Packaging Tauri installers...${RESET}"
	@cd frontend && pnpm tauri build --bundle

setup-docker: ## Run initial setup for docker-compose.dev.yml (install deps, migrate, key:generate)
	@echo "${GREEN}🔧 Running initial setup in Docker backend...${RESET}"
	@docker compose -f docker-compose.dev.yml exec -u root backend sh -c "\
		git config --global --add safe.directory /var/www/html 2>/dev/null || true && \
		if [ ! -d vendor ] || [ -z \"\$$(ls -A vendor 2>/dev/null)\" ]; then \
			echo '📦 Installing dependencies...'; \
			if [ -f composer.lock ]; then \
				echo '📋 Installing from lock file...'; \
				composer install --no-interaction --prefer-dist --ignore-platform-req=php; \
			else \
				echo '⚠️  No composer.lock found. Creating one...'; \
				composer update --no-interaction --prefer-dist --ignore-platform-req=php; \
			fi; \
		else \
			echo '✅ Vendor directory already exists, skipping composer install'; \
		fi && \
		if [ ! -f storage/database/.initialized ]; then \
			echo '🗄️ Running migrations...'; \
			php artisan migrate --force --no-interaction; \
			touch storage/database/.initialized; \
		fi && \
		if ! grep -q '^APP_KEY=' .env || [ -z \"\$$(grep '^APP_KEY=' .env | cut -d= -f2)\" ]; then \
			echo '🔑 Generating application key...'; \
			php artisan key:generate --force; \
		fi && \
		echo '✅ Setup complete!' \
	" 2>/dev/null || echo "${RED}❌ Setup failed. Please run manually: docker compose -f docker-compose.dev.yml exec backend composer install${RESET}"

setup: ## Alias for setup-docker
	@$(MAKE) setup-docker

rebuild: ## Rebuild Docker containers (no cache)
	@echo "${GREEN}🏗️ Rebuilding containers...${RESET}"
	@docker compose -f docker-compose.dev.yml build --no-cache
	@$(MAKE) dev

logs: ## View all Docker logs
	@docker compose -f docker-compose.dev.yml logs -f

logs-backend: ## View backend logs only
	@docker compose -f docker-compose.dev.yml logs -f backend

logs-queue: ## View queue worker logs
	@docker compose -f docker-compose.dev.yml logs -f queue

logs-scheduler: ## View scheduler logs
	@docker compose -f docker-compose.dev.yml logs -f scheduler

shell: ## Open shell in backend container
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend sh; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

tinker: ## Open Laravel tinker in backend container
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan tinker; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

migrate: ## Run migrations in backend container
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan migrate --force; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

fresh: ## Fresh migration (drop all tables and re-run)
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan migrate:fresh --force; \
		docker compose -f docker-compose.dev.yml exec backend rm -f storage/database/.initialized 2>/dev/null || true; \
		$(MAKE) setup; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

seed: ## Run seeders in backend container
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan db:seed --force; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

test: ## Run tests in backend container
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan test; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

clear: ## Clear all Laravel caches
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan optimize:clear; \
	else \
		echo "${YELLOW}⚠️ Backend container is not running. Starting it first...${RESET}"; \
		make dev; \
		docker compose -f docker-compose.dev.yml exec backend php artisan optimize:clear; \
	fi

artisan: ## Run artisan commands (usage: make artisan CMD="cache:clear")
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec backend php artisan $(CMD); \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

composer: ## Run composer commands (usage: make composer CMD="require package")
	@if docker compose -f docker-compose.dev.yml ps --status running | grep -q backend; then \
		docker compose -f docker-compose.dev.yml exec -u root backend sh -c "\
			git config --global --add safe.directory /var/www/html 2>/dev/null || true && \
			composer $(CMD) \
		"; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

status: ## Show container status (alias for status-docker)
	@$(MAKE) status-docker

status-docker: ## Show container status for docker-compose.dev.yml
	@echo "${GREEN}📊 Container Status (dev compose):${RESET}"
	@docker compose -f docker-compose.dev.yml ps 2>/dev/null || echo "${RED}❌ No containers running. Run 'make dev' to start.${RESET}"

down: ## Stop all Docker containers (alias for down-docker)
	@$(MAKE) down-docker

down-docker: ## Stop all containers for docker-compose.dev.yml
	@echo "${YELLOW}🛑 Stopping containers (dev compose)...${RESET}"
	@docker compose -f docker-compose.dev.yml down

clean: ## Clean frontend build artifacts & Docker volumes
	@echo "${YELLOW}🧹 Cleaning frontend build artifacts & Docker volumes...${RESET}"
	@docker compose -f docker-compose.dev.yml down -v
	@rm -rf frontend/.nuxt frontend/.output frontend/dist
	@echo "${GREEN}✅ Clean complete!${RESET}"

full-clean: ## Clean EVERYTHING including vendor, composer.lock, database
	@echo "${RED}⚠️  WARNING: This will remove vendor, composer.lock, database, and frontend deps!${RESET}"
	@echo "${YELLOW}Are you sure? Type 'yes' to continue:${RESET}" && read answer && [ "$$answer" = "yes" ]
	@docker compose -f docker-compose.dev.yml down -v
	@rm -rf backend/bootstrap/cache/*.php
	@rm -rf backend/vendor backend/composer.lock
	@rm -f backend/storage/database/database.sqlite
	@rm -f backend/storage/database/.initialized
	@rm -rf frontend/.nuxt frontend/.output frontend/dist frontend/node_modules frontend/.pnpm-store
	@echo "${GREEN}✅ Full clean complete!${RESET}"

rmi: ## Remove all matching Docker images (fuel_station_os-*)
	@echo "${YELLOW}🗑️ Removing fuel_station_os-* images...${RESET}"
	@IMAGES=$$(docker images fuel_station_os-* -q 2>/dev/null); \
	if [ -n "$$IMAGES" ]; then \
		docker rmi $$IMAGES; \
		echo "${GREEN}✅ Images removed!${RESET}"; \
	else \
		echo "${GREEN}ℹ️ No matching images found.${RESET}"; \
	fi

update-deps: ## Update PHP dependencies (composer update)
	@echo "${YELLOW}📦 Updating PHP dependencies...${RESET}"
	@docker compose -f docker-compose.dev.yml exec -u root backend sh -c "\
		git config --global --add safe.directory /var/www/html 2>/dev/null || true && \
		composer update --no-interaction --prefer-dist \
	"
	@echo "${GREEN}✅ Dependencies updated!${RESET}"

# Utility Commands

build-prune: ## Prune Docker builder cache
	@echo "${YELLOW}🧹 Pruning Docker builder cache...${RESET}"
	@docker builder prune -a -f
	@echo "${GREEN}✅ Builder cache pruned!${RESET}"

image-prune: ## Prune Docker images
	@echo "${YELLOW}🧹 Pruning Docker images...${RESET}"
	@docker image prune -a -f
	@echo "${GREEN}✅ Images pruned!${RESET}"

volume-prune: ## Prune Docker volumes
	@echo "${YELLOW}🧹 Pruning Docker volumes...${RESET}"
	@docker volume prune -f
	@echo "${GREEN}✅ Volumes pruned!${RESET}"

system-prune: ## Prune all Docker system resources (use with caution)
	@echo "${RED}⚠️  WARNING: This will remove all unused Docker resources!${RESET}"
	@echo "${YELLOW}Are you sure? Type 'yes' to continue:${RESET}" && read answer && [ "$$answer" = "yes" ]
	@docker system prune -a -f --volumes
	@echo "${GREEN}✅ System pruned!${RESET}"

# Production Commands

prod: ## Start production environment (docker-compose.prod.yml)
	@echo "${GREEN}🚀 Starting production environment...${RESET}"
	@if [ ! -f backend/.env.production ]; then \
		echo "${YELLOW}📄 Creating .env.production from backend/.env.example...${RESET}"; \
		cp backend/.env.example backend/.env.production; \
		echo "${RED}⚠️ Please update backend/.env.production with production values!${RESET}"; \
	fi
	@docker compose -f docker-compose.prod.yml up -d
	@echo "${GREEN}✅ Production environment ready!${RESET}"

prod-build: ## Build production Docker images
	@echo "${GREEN}🏗️ Building production images...${RESET}"
	@docker compose -f docker-compose.prod.yml build --no-cache

prod-down: ## Stop production environment
	@echo "${YELLOW}🛑 Stopping production...${RESET}"
	@docker compose -f docker-compose.prod.yml down

prod-logs: ## View production logs
	@docker compose -f docker-compose.prod.yml logs -f

prod-status: ## Show production container status
	@docker compose -f docker-compose.prod.yml ps

prod-clean: ## Clean production environment
	@echo "${YELLOW}🧹 Cleaning production...${RESET}"
	@docker compose -f docker-compose.prod.yml down -v
	@echo "${GREEN}✅ Production clean complete!${RESET}"

# Health Check

health: ## Check application health
	@echo "${GREEN}🔍 Checking health...${RESET}"
	@echo "${YELLOW}Backend:${RESET}"
	@curl -s http://localhost:${APP_PORT:-8000}/health 2>/dev/null | jq . 2>/dev/null || echo "❌ Backend not responding"
	@echo ""
	@echo "${YELLOW}Frontend (if running):${RESET}"
	@curl -s -o /dev/null -w "%{http_code}" http://localhost:1420 2>/dev/null | grep -q 200 && echo "✅ Frontend is running" || echo "❌ Frontend not responding"

# Default target
.DEFAULT_GOAL := help