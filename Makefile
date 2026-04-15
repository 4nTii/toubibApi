# ============================================================
#  Makefile — Symfony Setup (cross-platform)
# ============================================================

.PHONY: all install setup parameters jwt cache-clear dev server-stop wamp help

# ── Détection OS ────────────────────────────────────────────
ifeq ($(OS),Windows_NT)
  PLATFORM = windows
else
  PLATFORM = unix
endif

WAMP_EXE = D:\wamp64\wampmanager.exe

# ── Cible par défaut : tout faire ───────────────────────────
all: setup cache-clear wamp dev

# ── Aide ────────────────────────────────────────────────────
help:
ifeq ($(PLATFORM),windows)
	@echo.
	@echo Commandes disponibles :
	@echo   make             - setup complet + cache-clear + database + symfony serve
	@echo   make setup       - parameters + composer install + jwt
	@echo   make install     - composer install uniquement
	@echo   make parameters  - cree parameters.yml depuis parameters.yml.dist
	@echo   make jwt         - genere les cles JWT (lexik)
	@echo   make cache-clear - vide le cache Symfony
	@echo   make database    - lance server DB si non demarré
	@echo   make dev         - lance le serveur Symfony
	@echo   make server-stop - arrete le serveur Symfony
	@echo.
else
	@echo ""
	@echo "Commandes disponibles :"
	@echo "  make             — setup complet + cache-clear + database + symfony serve"
	@echo "  make setup       — parameters + composer install + jwt"
	@echo "  make install     — composer install uniquement"
	@echo "  make parameters  — crée parameters.yml depuis parameters.yml.dist"
	@echo "  make jwt         — génère les clés JWT (lexik)"
	@echo "  make cache-clear — vide le cache Symfony"
	@echo "  make database    — lance server DB si non démarré (Windows uniquement)"
	@echo "  make dev         — lance le serveur Symfony"
	@echo "  make server-stop — arrête le serveur Symfony"
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

# ── Lancement de Database si non démarré (Windows only) ───
# ── Ajoutez votre propre base de données, ou commenter ce block en cas de base en ligne via .env ───
ifeq ($(PLATFORM),windows)
wamp:
	@tasklist /FI "IMAGENAME eq wampmanager.exe" 2>nul | find /I "wampmanager.exe" >nul \
		&& echo [.]  DatabaseServer deja en cours d'execution, rien a faire \
		|| (echo [.]  Demarrage de DatabaseServer... && start "" "$(WAMP_EXE)" && echo [OK] DatabaseServer demarre)
else
wamp:
	@echo "·  DatabaseServer est une application Windows uniquement, ignoré."
endif

# ── Lancement du serveur Symfony ────────────────────────────
dev:
	@echo Demarrage du serveur Symfony...
	@symfony serve

# ── Arrêt du serveur Symfony ─────────────────────────────────
server-stop:
	@echo Arret du serveur Symfony...
	@symfony server:stop
	@echo Serveur Symfony arrete.