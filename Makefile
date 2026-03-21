# ============================================================
#  Makefile — Symfony Setup (cross-platform)
# ============================================================

.PHONY: all install setup parameters jwt cache-clear dev help

# ── Détection OS ────────────────────────────────────────────
ifeq ($(OS),Windows_NT)
  PLATFORM = windows
else
  PLATFORM = unix
endif

# ── Cible par défaut : tout faire ───────────────────────────
all: setup cache-clear dev

# ── Aide ────────────────────────────────────────────────────
help:
ifeq ($(PLATFORM),windows)
	@echo.
	@echo Commandes disponibles :
	@echo   make             - setup complet + cache-clear + symfony serve
	@echo   make setup       - parameters + composer install + jwt
	@echo   make install     - composer install uniquement
	@echo   make parameters  - cree parameters.yml depuis parameters.yml.dist
	@echo   make jwt         - genere les cles JWT (lexik)
	@echo   make cache-clear - vide le cache Symfony
	@echo   make dev         - lance le serveur Symfony
	@echo.
else
	@echo ""
	@echo "Commandes disponibles :"
	@echo "  make             — setup complet + cache-clear + symfony serve"
	@echo "  make setup       — parameters + composer install + jwt"
	@echo "  make install     — composer install uniquement"
	@echo "  make parameters  — crée parameters.yml depuis parameters.yml.dist"
	@echo "  make jwt         — génère les clés JWT (lexik)"
	@echo "  make cache-clear — vide le cache Symfony"
	@echo "  make dev         — lance le serveur Symfony"
	@echo ""
endif

# ── Setup complet ───────────────────────────────────────────
setup: parameters install jwt
	@echo Setup termine.

# ── Création de parameters.yml depuis parameters.yml.dist ───
ifeq ($(PLATFORM),windows)
parameters:
	@if not exist app\config\parameters.yml ( \
		if exist app\config\parameters.yml.dist ( \
			copy app\config\parameters.yml.dist app\config\parameters.yml > nul && \
			echo [OK] parameters.yml cree depuis parameters.yml.dist && \
			echo [!]  Pense a remplir les valeurs dans app/config/parameters.yml \
		) else ( \
			echo [!]  Aucun parameters.yml.dist trouve - parameters.yml non cree \
		) \
	) else ( \
		echo [.]  parameters.yml deja present, rien a faire \
	)
else
parameters:
	@if [ ! -f app/config/parameters.yml ]; then \
		if [ -f app/config/parameters.yml.dist ]; then \
			cp app/config/parameters.yml.dist app/config/parameters.yml; \
			echo "✔  parameters.yml créé depuis parameters.yml.dist"; \
			echo "⚠  Pense à remplir les valeurs dans app/config/parameters.yml"; \
		else \
			echo "⚠  Aucun parameters.yml.dist trouvé — parameters.yml non créé"; \
		fi \
	else \
		echo "·  parameters.yml déjà présent, rien à faire"; \
	fi
endif

# ── Installation des dépendances Composer ───────────────────
install:
	@echo Installation des dependances Composer...
	@composer install
	@echo composer install termine.

# ── Génération des clés JWT (lexik/jwt-authentication-bundle)
ifeq ($(PLATFORM),windows)
jwt:
	@if not exist config\jwt\private.pem ( \
		echo [.]  Generation des cles JWT... && \
		php bin/console lexik:jwt:generate-keypair && \
		echo [OK] Cles JWT generees dans config/jwt/ \
	) else ( \
		echo [.]  Cles JWT deja presentes, rien a faire \
	)
else
jwt:
	@if [ ! -f config/jwt/private.pem ]; then \
		echo "·  Génération des clés JWT..."; \
		php bin/console lexik:jwt:generate-keypair; \
		echo "✔  Clés JWT générées dans config/jwt/"; \
	else \
		echo "·  Clés JWT déjà présentes, rien à faire"; \
	fi
endif

# ── Vidage du cache Symfony ──────────────────────────────────
cache-clear:
	@echo Vidage du cache Symfony...
	@php bin/console cache:clear
	@echo Cache vide.

# ── Lancement du serveur Symfony ────────────────────────────
dev:
	@echo Demarrage du serveur Symfony...
	@symfony serve
