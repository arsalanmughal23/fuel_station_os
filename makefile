# Makefile for Fuel Station OS
# ============================
# Usage: make [target]

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RED    := $(shell tput -Txterm setaf 1)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

# Read APP_PORT from .env file if it exists
ifneq ($(wildcard .env),)
    include .env
    export
    APP_PORT := $(APP_PORT)
endif

# Default port if not set in .env
APP_PORT ?= 8001

.PHONY: help dev setup rebuild logs shell tinker migrate fresh seed test clear status down clean full-clean build-prune prod prod-build prod-down prod-logs prod-status health rmi update-deps

# Add these to PHONY
.PHONY: artisan composer

help: ## Show this help message
	@echo ''
	@echo '${GREEN}Fuel Station OS${RESET} - Development Commands'
	@echo '========================================'
	@grep -E '^[a-z].*:.*?## .*$$' $(MAKEFILE_LIST) | grep -v 'prod' | grep -v 'prune' | grep -v '^help' | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${GREEN}%-15s${RESET} %s\n", $$1, $$2}'
	@echo ''
	@echo '${YELLOW}Production Commands${RESET}'
	@echo '========================================'
	@grep -E '^prod.*:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}'
	@echo ''
	@echo '${RED}Utility Commands${RESET}'
	@echo '========================================'
	@grep -E '^[a-zA-Z_-]+-prune:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = "-prune:.*?## "}; {printf "  ${RED}%-15s${RESET} %s\n", $$1, $$2}'

# Development Commands

dev: ## Start development environment
	@echo "${GREEN}🚀 Starting development environment...${RESET}"
	@docker compose up -d
	@echo "${YELLOW}⏳ Waiting for containers to be ready...${RESET}"
	@sleep 5
	@$(MAKE) setup
	@echo ""
	@echo "${GREEN}🌐 Access your application:${RESET}"
	@echo "  Backend: http://localhost:${APP_PORT}"
	@echo "  Backend API: http://localhost:${APP_PORT}/api/health"
	@echo "  Frontend: http://localhost:3000"
	@echo ""
	@$(MAKE) status

setup: ## Run initial setup (first time only)
	@echo "${GREEN}🔧 Running initial setup...${RESET}"
	@docker compose exec -u root backend sh -c "\
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
	" 2>/dev/null || echo "${RED}❌ Setup failed. Please run manually: docker compose exec backend composer install${RESET}"

rebuild: ## Rebuild containers
	@echo "${GREEN}🏗️ Rebuilding containers...${RESET}"
	docker compose build --no-cache
	@$(MAKE) dev

logs: ## View all logs
	docker compose logs -f

shell: ## Open shell in backend container
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend sh; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

tinker: ## Open Laravel tinker
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan tinker; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

migrate: ## Run migrations
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan migrate --force; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

fresh: ## Fresh migration (drop all tables and re-run)
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan migrate:fresh --force; \
		docker compose exec backend rm -f storage/database/.initialized 2>/dev/null || true; \
		$(MAKE) setup; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

seed: ## Run seeders
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan db:seed --force; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

test: ## Run tests
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan test; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

clear: ## Clear all caches
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan optimize:clear; \
	else \
		echo "${YELLOW}⚠️ Backend container is not running. Starting it first...${RESET}"; \
		make dev; \
		docker compose exec backend php artisan optimize:clear; \
	fi

artisan: ## Run artisan commands (usage: make artisan CMD="cache:clear")
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec backend php artisan $(CMD); \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

composer: ## Run composer commands (usage: make composer CMD="require package")
	@if docker compose ps --status running | grep -q backend; then \
		docker compose exec -u root backend sh -c "\
			git config --global --add safe.directory /var/www/html 2>/dev/null || true && \
			composer $(CMD) \
		"; \
	else \
		echo "${RED}❌ Backend container is not running. Please run 'make dev' first.${RESET}"; \
	fi

status: ## Show container status
	@echo "${GREEN}📊 Container Status:${RESET}"
	@docker compose ps 2>/dev/null || echo "${RED}❌ No containers running. Run 'make dev' to start.${RESET}"

down: ## Stop all containers
	@echo "${YELLOW}🛑 Stopping containers...${RESET}"
	docker compose down

clean: ## Clean everything EXCEPT composer.lock
	@echo "${YELLOW}🧹 Cleaning frontend/{.nuxt, .output} & compose down -v...${RESET}"
	docker compose down -v
	rm -rf frontend/.nuxt frontend/.output
	@echo "${GREEN}✅ Clean complete!${RESET}"

full-clean: ## Clean EVERYTHING including composer.lock
	@echo "${RED}⚠️  WARNING: This will remove composer.lock and vendor!${RESET}"
	@echo "${YELLOW}Are you sure? Type 'yes' to continue:${RESET}" && read answer && [ "$$answer" = "yes" ]
	docker compose down -v
	rm -rf bootstrap/cache/*.php
	rm -rf vendor composer.lock
	rm -f storage/database/database.sqlite
	rm -f storage/database/.initialized
	rm -rf frontend/.nuxt frontend/.output frontend/node_modules frontend/.pnpm-store
	@echo "${GREEN}✅ Full clean complete!${RESET}"

rmi: ## Remove all matching images (fuel_station*)
	@echo "${YELLOW}🗑️ Removing fuel_station* images...${RESET}"
	@IMAGES=$$(docker images fuel_station* -q 2>/dev/null); \
	if [ -n "$$IMAGES" ]; then \
		docker rmi $$IMAGES; \
		echo "${GREEN}✅ Images removed!${RESET}"; \
	else \
		echo "${GREEN}ℹ️ No matching images found.${RESET}"; \
	fi

update-deps: ## Update dependencies (composer update)
	@echo "${YELLOW}📦 Updating dependencies...${RESET}"
	docker compose exec -u root backend sh -c "\
		git config --global --add safe.directory /var/www/html 2>/dev/null || true && \
		composer update --no-interaction --prefer-dist \
	"
	@echo "${GREEN}✅ Dependencies updated!${RESET}"

# Utility Commands

build-prune: ## Prune Docker builder cache (remove all unused build cache)
	@echo "${YELLOW}🧹 Pruning Docker builder cache...${RESET}"
	docker builder prune -a -f
	@echo "${GREEN}✅ Builder cache pruned!${RESET}"

image-prune: ## Prune Docker images (remove dangling images)
	@echo "${YELLOW}🧹 Pruning Docker images...${RESET}"
	docker image prune -a -f
	@echo "${GREEN}✅ Images pruned!${RESET}"

volume-prune: ## Prune Docker volumes (remove unused volumes)
	@echo "${YELLOW}🧹 Pruning Docker volumes...${RESET}"
	docker volume prune -f
	@echo "${GREEN}✅ Volumes pruned!${RESET}"

system-prune: ## Prune all Docker system resources (use with caution)
	@echo "${RED}⚠️  WARNING: This will remove all unused Docker resources!${RESET}"
	@echo "${YELLOW}Are you sure? Type 'yes' to continue:${RESET}" && read answer && [ "$$answer" = "yes" ]
	docker system prune -a -f --volumes
	@echo "${GREEN}✅ System pruned!${RESET}"

# Production Commands

prod: ## Start production environment
	@echo "${GREEN}🚀 Starting production environment...${RESET}"
	@if [ ! -f .env.production ]; then \
		echo "${YELLOW}📄 Creating .env.production from .env.example...${RESET}"; \
		cp .env.example .env.production; \
		echo "${RED}⚠️ Please update .env.production with production values!${RESET}"; \
	fi
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
	@echo "${GREEN}✅ Production environment ready!${RESET}"

prod-build: ## Build production images
	@echo "${GREEN}🏗️ Building production images...${RESET}"
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache

prod-down: ## Stop production environment
	@echo "${YELLOW}🛑 Stopping production...${RESET}"
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down

prod-logs: ## View production logs
	docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

prod-status: ## Show production container status
	docker compose -f docker-compose.yml -f docker-compose.prod.yml ps

prod-clean: ## Clean production environment
	@echo "${YELLOW}🧹 Cleaning production...${RESET}"
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down -v
	@echo "${GREEN}✅ Production clean complete!${RESET}"

# Health Check
health: ## Check application health
	@echo "${GREEN}🔍 Checking health...${RESET}"
	@echo "${YELLOW}Backend:${RESET}"
	@curl -s http://localhost:${APP_PORT:-8001}/api/health 2>/dev/null | jq . 2>/dev/null || echo "❌ Backend not responding"
	@echo ""
	@echo "${YELLOW}Frontend:${RESET}"
	@curl -s -o /dev/null -w "%{http_code}" http://localhost:3000 2>/dev/null | grep -q 200 && echo "✅ Frontend is running" || echo "❌ Frontend not responding"

# Default target
.DEFAULT_GOAL := help