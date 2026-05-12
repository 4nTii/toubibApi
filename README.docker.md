# 🐳 Docker — Toubib

## Structure générée

```
.
├── Dockerfile                  # Image PHP (production)
├── Dockerfile.dev              # Image PHP (développement + Xdebug + Symfony CLI)
├── docker-compose.yml          # Stack complète (prod)
├── docker-compose.override.yml # Surcharges dev (auto-chargé par Docker Compose)
├── .dockerignore
├── .env.docker                 # Template des variables d'environnement
├── Makefile                    # Commandes raccourcies
└── docker/
    ├── nginx/
    │   ├── default.conf        # Config Nginx production
    │   └── dev.conf            # Config Nginx développement
    ├── php/
    │   ├── php.ini             # PHP production
    │   ├── php.dev.ini         # PHP développement (Xdebug activé)
    │   └── php-fpm.conf        # Pool PHP-FPM
    └── mysql/
        └── my.cnf              # Config MySQL
```

## Services

| Service     | Description                        | Port(s) exposés (dev) |
|-------------|------------------------------------|-----------------------|
| `app`       | PHP 8.4-FPM + Symfony              | 9003 (Xdebug)         |
| `nginx`     | Reverse proxy                      | 8080                  |
| `db`        | MySQL 8.0                          | 3306                  |
| `redis`     | Cache + Messenger transport        | 6379                  |
| `messenger` | Worker Symfony Messenger           | —                     |
| `mailpit`   | Catcheur d'emails (dev uniquement) | 8025                  |

## Démarrage rapide

### 1. Variables d'environnement

```bash
cp .env.docker .env
# Éditez .env selon votre config
```

### 2. Build & démarrage

```bash
make build
make up
```

### 3. Migrations & clés JWT

```bash
make migrate
make jwt-keys   # Si les clés ne sont pas déjà dans config/jwt/
```

### 4. Accès

- **API** : http://localhost:8080
- **Mailpit** : http://localhost:8025
- **MySQL** : `make db-shell`

---

## Commandes utiles

```bash
make help           # Liste toutes les commandes
make shell          # Bash dans le conteneur PHP
make logs           # Logs en temps réel
make cache-clear    # Cache Symfony
make migrate-diff   # Générer une migration
make fixtures       # Charger les fixtures
```

## Production

```bash
# Build et déploiement complet
make prod-deploy

# Ou étape par étape
docker compose -f docker-compose.yml up -d
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:warmup
```

## Variables d'environnement importantes

| Variable               | Description                            |
|------------------------|----------------------------------------|
| `APP_SECRET`           | Clé secrète Symfony (32 chars min)     |
| `DB_PASSWORD`          | Mot de passe MySQL                     |
| `JWT_PASSPHRASE`       | Passphrase pour les clés JWT           |
| `REDIS_PASSWORD`       | Mot de passe Redis                     |
| `MAILER_DSN`           | DSN Symfony Mailer                     |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger (Redis/Doctrine) |
| `CORS_ALLOW_ORIGIN`    | Regex origines CORS autorisées         |

## Notes

- Les **clés JWT** sont générées automatiquement au premier build si absentes, et persistées dans un volume Docker.
- Le **Messenger worker** se relance automatiquement (`restart: unless-stopped`) avec une limite de 1h / 128 Mo par run.
- En développement, **Mailpit** capture tous les emails sans en envoyer réellement.
- **Xdebug** écoute sur le port 9003 côté hôte — configurez votre IDE pour se connecter sur ce port.
