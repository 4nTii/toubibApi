.PHONY: all help build up down restart logs logs-app shell db-shell \
        redis-cli migrate migrate-diff db-fixtures fixtures cache-clear \
        jwt-keys jwt-keys-force composer-install composer-update \
        setup db-init prod-up prod-deploy

ifeq ($(OS),Windows_NT)
ENV_CHECK = powershell -NoProfile -ExecutionPolicy Bypass -Command "if (-not (Test-Path '.env')) { if (Test-Path '.env.docker') { Copy-Item '.env.docker' '.env'; Write-Host 'WARNING: .env created from .env.docker -- fill in the values then re-run make'; exit 1 } else { Write-Host 'ERROR: No .env file found. Create one before continuing.'; exit 1 } }"
HELP_CMD = powershell -NoProfile -ExecutionPolicy Bypass -Command "Get-Content '$(firstword $(MAKEFILE_LIST))' | Where-Object { $$_ -match '^[a-zA-Z_-]+:.*?\#\# ' } | ForEach-Object { $$parts = $$_ -split ':.*?\#\# ', 2; Write-Host ('{0,-22} {1}' -f $$parts[0], $$parts[1]) }"
WAIT_FOR_DB = powershell -NoProfile -ExecutionPolicy Bypass -Command "while ($$true) { docker compose exec db mysqladmin ping -h localhost --silent 2>$$null; if ($$LASTEXITCODE -eq 0) { break }; Write-Host -NoNewline '.'; Start-Sleep -Seconds 2 }; Write-Host ' ready'"
DB_SHELL_CMD = powershell -NoProfile -ExecutionPolicy Bypass -Command "$$u = if ($$env:DB_USER) { $$env:DB_USER } else { 'toubib_user' }; $$p = if ($$env:DB_PASSWORD) { $$env:DB_PASSWORD } else { 'toubib_password' }; $$d = if ($$env:DB_NAME) { $$env:DB_NAME } else { 'toubib' }; docker compose exec db mysql -u $$u ('-p' + $$p) $$d"
REDIS_CLI_CMD = powershell -NoProfile -ExecutionPolicy Bypass -Command "$$p = if ($$env:REDIS_PASSWORD) { $$env:REDIS_PASSWORD } else { 'redis_password' }; docker compose exec redis redis-cli -a $$p"
DB_FIXTURES_CMD = powershell -NoProfile -ExecutionPolicy Bypass -Command "$$u = if ($$env:DB_USER) { $$env:DB_USER } else { 'toubib_user' }; $$p = if ($$env:DB_PASSWORD) { $$env:DB_PASSWORD } else { 'toubib_password' }; $$d = if ($$env:DB_NAME) { $$env:DB_NAME } else { 'toubib' }; Get-Content -Raw 'docker/mysql/data-dev.sql' | docker compose exec -T db mysql -u $$u ('-p' + $$p) $$d"
else
ENV_CHECK = if [ ! -f .env ]; then \
		if [ -f .env.docker ]; then \
			cp .env.docker .env; \
			echo "WARNING: .env created from .env.docker -- fill in the values then re-run make"; \
			exit 1; \
		else \
			echo "ERROR: No .env file found. Create one before continuing."; \
			exit 1; \
		fi \
	fi
HELP_CMD = grep -E '^[a-zA-Z_-]+:.*?\#\# .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-22s\033[0m %s\n", $$1, $$2}'
WAIT_FOR_DB = until docker compose exec db mysqladmin ping -h localhost --silent 2>/dev/null; do \
		printf '.'; sleep 2; \
	done; \
	echo " ready"
DB_SHELL_CMD = docker compose exec db mysql -u $${DB_USER:-toubib_user} -p$${DB_PASSWORD:-toubib_password} $${DB_NAME:-toubib}
REDIS_CLI_CMD = docker compose exec redis redis-cli -a $${REDIS_PASSWORD:-redis_password}
DB_FIXTURES_CMD = docker compose exec -T db mysql -u $${DB_USER:-toubib_user} -p$${DB_PASSWORD:-toubib_password} $${DB_NAME:-toubib} < docker/mysql/data-dev.sql
endif

# --- Default target: full install and start -----------------------------------

all: ## Run everything: build -> up -> setup (migrate + jwt + cache)
	@$(ENV_CHECK)
	@echo ""
	@echo "--- Building Docker images --------------------------"
	docker compose build --build-arg http_proxy="" --build-arg https_proxy="" --build-arg HTTP_PROXY="" --build-arg HTTPS_PROXY="" --build-arg NO_PROXY="*"
	@echo ""
	@echo "--- Starting containers -----------------------------"
	docker compose up -d
	@echo "Waiting for MySQL..."
	@$(WAIT_FOR_DB)
	@echo ""
	@echo "--- Application setup -------------------------------"
	@echo "-- Composer install -----------------------------------------"
	@$(MAKE) --no-print-directory composer-install
	@$(MAKE) --no-print-directory setup
	@echo ""
	@echo "-----------------------------------------------------"
	@echo "  Toubib is ready"
	@echo ""
	@echo "  API   -> http://localhost:8000"
	@echo "  Mails -> http://localhost:8025"
	@echo ""
	@echo "  Run 'make help' to see all available commands"
	@echo "-----------------------------------------------------"

# --- Help ---------------------------------------------------------------------

help: ## Show this help
	@$(HELP_CMD)

# --- Docker lifecycle ---------------------------------------------------------

build: ## Build Docker images
	docker compose build --no-cache --build-arg http_proxy="" --build-arg https_proxy="" --build-arg HTTP_PROXY="" --build-arg HTTPS_PROXY="" --build-arg NO_PROXY="*"

up: ## Start containers (dev)
	docker compose up -d
	@echo "App    : http://localhost:8000"
	@echo "Mails  : http://localhost:8025"

down: ## Stop and remove containers
	docker compose down

restart: ## Restart containers
	docker compose restart

logs: ## Follow all container logs
	docker compose logs -f

logs-app: ## Follow PHP container logs only
	docker compose logs -f app

# --- Container access ---------------------------------------------------------

shell: ## Open a shell in the PHP container
	docker compose exec app bash

db-shell: ## Open a MySQL shell
	@$(DB_SHELL_CMD)

redis-cli: ## Open a Redis CLI
	@$(REDIS_CLI_CMD)

# --- Symfony ------------------------------------------------------------------

migrate: ## Run Doctrine migrations
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

db-init: ## Create/update DB schema idempotently + mark all migrations done
	docker compose exec app php bin/console doctrine:schema:update --force --no-interaction
	docker compose exec app php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
	docker compose exec app php bin/console doctrine:migrations:version --add --all --no-interaction

migrate-diff: ## Generate a migration from entities
	docker compose exec app php bin/console doctrine:migrations:diff

db-fixtures: ## Load SQL test data into the database
	@$(DB_FIXTURES_CMD)

fixtures: ## Load Symfony fixtures (dev)
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

cache-clear: ## Clear Symfony cache
	docker compose exec app php bin/console cache:clear

composer-install: ## Run composer install inside the container
	docker compose exec -u root app chown -R www-data:www-data /var/www/html/var
	docker compose exec app composer install --prefer-dist --no-scripts

composer-update: ## Run composer update inside the container
	docker compose exec app composer update

# --- JWT ----------------------------------------------------------------------

jwt-keys: ## Generate JWT keys if missing (lexik:jwt:generate-keypair)
	@docker compose exec app sh -c " \
		if [ ! -f config/jwt/private.pem ]; then \
			php bin/console lexik:jwt:generate-keypair; \
			echo 'JWT keys generated in config/jwt/'; \
		else \
			echo 'JWT keys already present -- use jwt-keys-force to overwrite'; \
		fi"

jwt-keys-force: ## Regenerate JWT keys even if they already exist
	docker compose exec app php bin/console lexik:jwt:generate-keypair --overwrite
	@echo "JWT keys regenerated"

# --- Initial setup ------------------------------------------------------------

setup: ## Application setup: db-init + db-fixtures + jwt-keys + cache-clear
	@echo "-- Database schema ------------------------------------------"
	@$(MAKE) --no-print-directory db-init
	@echo "-- SQL Fixtures ---------------------------------------------"
	@$(MAKE) --no-print-directory db-fixtures
	@echo "-- JWT keys -------------------------------------------------"
	@$(MAKE) --no-print-directory jwt-keys
	@echo "-- Symfony cache --------------------------------------------"
	@$(MAKE) --no-print-directory cache-clear
	@echo "Setup complete"

# --- Production ---------------------------------------------------------------

prod-up: ## Start in production mode (no dev override)
	docker compose -f docker-compose.yml up -d

prod-deploy: ## Full deploy: build + migrate + cache warmup
	docker compose -f docker-compose.yml build
	docker compose -f docker-compose.yml up -d
	docker compose -f docker-compose.yml exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker compose -f docker-compose.yml exec app php bin/console cache:warmup
	@echo "Deployment complete"
