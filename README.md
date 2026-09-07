# Toubib — API de gestion de rendez-vous médicaux

API REST (Symfony 8 / PHP 8.4) consommée par les frontends React `toubib`
(patients) et `toubibBO` (back-office praticiens).

---

## Stack technique

- **PHP 8.4** / **Symfony 8.0**
- **Doctrine ORM 3** + **Doctrine Migrations**
- **MySQL 8.0**
- **Redis 7** — transport Symfony Messenger + cache
- **Lexik JWT Authentication Bundle** — authentification JWT en cookie HttpOnly
- **Nelmio CORS Bundle** — politique CORS par environnement
- **Symfony Mailer** — emails transactionnels
- **Nginx** — reverse proxy devant PHP-FPM
- **Mailpit** — catcher d'emails en développement
- **Docker / Docker Compose** — toute la stack

---

## Prérequis

- [Docker](https://www.docker.com/get-started/) + Docker Compose
- [Make](https://gnuwin32.sourceforge.net/packages/make.htm)

Aucune installation locale de PHP / Composer / MySQL n'est nécessaire — tout
tourne dans les conteneurs.

---

## Démarrage rapide

```bash
git clone <repo> && cd toubibApi
make
```

`make` enchaîne : création de `.env.local` si absent → build des images →
démarrage des conteneurs → `composer install` → migrations → fixtures SQL →
génération des clés JWT → vidage du cache.

- API : https://localhost:8000 (certificat auto-signé)
- Mailpit : http://localhost:8025

Ensuite, au quotidien :

```bash
make up        # démarrer
make down      # arrêter
make logs-app  # logs du conteneur PHP
make help      # toutes les commandes
```

---

## Image Docker (`Dockerfile` multi-stage)

| Stage  | Contenu                                                       | Utilisé par |
|--------|-------------------------------------------------------------|-------------|
| `base` | PHP 8.4-FPM + extensions + Composer + pool PHP-FPM          | —           |
| `dev`  | `base` + Xdebug + Symfony CLI + `php.dev.ini` (code monté)  | `docker-compose.override.yml` → `target: dev` |
| `prod` | `base` + `php.ini` + code applicatif + autoloader optimisé  | `docker-compose.yml` → `target: prod` |

Build manuel d'un stage : `docker build --target dev -t toubib-api:dev .`

```
.
├── Dockerfile                   # image multi-stage : base → dev / prod
├── .dockerignore
├── docker-compose.yml           # stack de base (cible l'image `prod`)
├── docker-compose.override.yml  # surcharges dev (auto-chargé) — cible `dev`
├── .env / .env.dev / .env.example
├── Makefile
└── docker/
    ├── nginx/{default.conf, dev.conf, ssl/}
    ├── php/{php.ini, php.dev.ini, php-fpm.conf}
    └── mysql/{my.cnf, data-dev.sql}
```

---

## Services

| Service   | Description                        | Port local (dev)       |
|-----------|------------------------------------|------------------------|
| `app`     | PHP 8.4-FPM + Symfony              | 9003 (Xdebug)          |
| `nginx`   | Reverse proxy (HTTPS auto-signé)   | 8000                   |
| `db`      | MySQL 8.0                          | 3306                   |
| `redis`   | Cache + transport Messenger        | 6379                   |
| `mailer`  | Mailpit — catcher d'emails (dev)   | 8025 (UI) / 1025 (SMTP)|

---

## Fichiers d'environnement

Ordre de chargement (Symfony Dotenv, le dernier gagne) :

1. `.env` — versionné, défauts communs, **aucun secret**. Lu aussi par Docker Compose pour l'interpolation `${...}`.
2. `.env.local` — **non versionné**, secrets et valeurs propres à la machine / au déploiement. Créé automatiquement par `make` depuis `.env.example` (modèle complet de tous les variables).
3. `.env.dev` — versionné, surcharges de comportement pour `APP_ENV=dev`.
4. `.env.dev.local` — non versionné.

`.env.example` est le **modèle de référence complet** : toutes les variables, avec
valeurs d'exemple et commentaires.

Le stack de dev démarre **sans configuration** : les valeurs de `.env` suffisent.
`docker compose` ne lit que `.env` ; en dev les valeurs clés sont injectées via
`docker-compose.yml`, donc éditer `.env.local` compte surtout pour `make prod-*`
(chargé avec `--env-file`) et pour lancer `bin/console` sur l'hôte. Pour un vrai
déploiement, renseigner dans `.env.local` : `APP_ENV=prod`, `APP_SECRET`, mots de
passe DB/Redis, `JWT_PASSPHRASE`, SMTP, FTP…

---

## Production

`make prod-*` utilise uniquement `docker-compose.yml` (image `prod`) et charge
`.env.local` par-dessus `.env` s'il existe.

```bash
$EDITOR .env.local      # décommenter APP_ENV=prod + les secrets
make prod-deploy        # build + up + migrate + cache warmup
```

---

## Accès à la base de données

En développement, le port MySQL du conteneur `db` est publié sur l'hôte
(`3306:3306` dans `docker-compose.override.yml`). La base est donc consultable
depuis un client SQL externe — **DBeaver**, TablePlus, DataGrip, `mysql` CLI…

| Paramètre    | Valeur                   | Où le trouver                              |
|--------------|--------------------------|--------------------------------------------|
| Hôte / Host  | `127.0.0.1`              | —                                          |
| Port         | `3306`                   | `docker-compose.override.yml` → `db.ports` |
| Base         | `toubib`                 | `DB_NAME` dans `.env`                       |
| Utilisateur  | `toubib_user`            | `DB_USER` dans `.env`                       |
| Mot de passe | `toubib_password`        | `DB_PASSWORD` dans `.env`                   |
| Compte root  | `root` / `root_password` | `DB_ROOT_PASSWORD` dans `.env`              |

> Les valeurs par défaut ci-dessus viennent de [.env](.env) (versionné, dev).
> Si tu les as surchargées dans `.env.local`, ce sont **ces** valeurs qui priment
> (`.env.local` > `.env`). Vérifie la valeur réellement injectée avec :
>
> ```bash
> docker compose exec db printenv MYSQL_USER MYSQL_PASSWORD MYSQL_DATABASE
> ```

### DBeaver — pas à pas

1. *New Database Connection* → **MySQL**
2. Server Host `127.0.0.1`, Port `3306`, Database `toubib`
3. Username `toubib_user`, Password `toubib_password`
4. Onglet *Driver properties* : `allowPublicKeyRetrieval = true` et `useSSL = false`
   (MySQL 8 + connexion locale non chiffrée)
5. *Test Connection* → *Finish*

Sans outil externe : `make db-shell` ouvre un client `mysql` dans le conteneur.

---

## Commandes utiles

```bash
make help           # Liste toutes les commandes
make shell          # Bash dans le conteneur PHP
make rebuild        # Rebuild + restart du conteneur app (après changement .env / Dockerfile)
make logs-app       # Logs du conteneur PHP
make migrate        # Exécuter les migrations en attente
make migrate-diff   # Générer une migration depuis les entités
make db-fixtures    # Recharger docker/mysql/data-dev.sql
make db-shell       # Client MySQL dans le conteneur
make jwt-keys       # Générer les clés JWT si absentes
make jwt-keys-force # Régénérer les clés JWT
make cache-clear    # Vider le cache Symfony
```

---

## Authentification

JWT via `lexik/jwt-authentication-bundle`. Les clés RSA sont générées au premier
`make` dans `config/jwt/` (git-ignoré) et persistées dans le volume `jwt_keys` ;
la passphrase provient de `JWT_PASSPHRASE`. Le token est posé dans un cookie
`app_auth` HttpOnly et renvoyé dans l'en-tête `Authorization: Bearer`.

---

## Notes

- **Xdebug** écoute sur le port 9003 côté hôte — configurez votre IDE en conséquence.
- En dev, **Mailpit** capture tous les emails sans les envoyer.
- Le stage `prod` embarque le code (`COPY . .` + `composer install --no-dev`) ;
  `.dockerignore` exclut `vendor/`, `var/`, `.env.local`, `config/jwt/`, etc.

---

## Licence

Yassine ECHCHOUROUQ — tous droits réservés.
