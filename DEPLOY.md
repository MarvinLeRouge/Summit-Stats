# Deployment Guide

🇫🇷 [Version française](#version-française)

---

## 🇬🇧 English version

Self-hosting Summit Stats on a Linux server via Docker Compose, behind a Traefik reverse proxy with automatic TLS (Let's Encrypt). Production runs PHP-FPM, nginx, PostgreSQL, and Redis as containers; images are built in CI and pulled from GHCR, no source code or build step on the server itself.

### Prerequisites

- Docker and Docker Compose v2 on the VPS
- A running Traefik instance attached to an external `traefik-public` Docker network, with the `websecure` entrypoint and a `letsencrypt` certresolver configured
- A GitHub Personal Access Token with `read:packages` scope, for the first manual `docker login ghcr.io` (the CD workflow authenticates on its own afterward)

### 1. Server setup

```bash
mkdir -p /opt/summit-stats/compose
cd /opt/summit-stats/compose
```

Download `docker-compose.prod.yml` from the repository:

```bash
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker-compose.prod.yml -o docker-compose.prod.yml
```

> The path above (`/opt/summit-stats`) is an example. If you use the project's own CD workflow (`.github/workflows/build-deploy.yml`) instead of deploying by hand, its `DEPLOY_PATH` must match wherever you actually place this directory.

### 2. Configure environment

```bash
cp .env.prod.example .env.prod
```

Edit `.env.prod`:

```dotenv
APP_KEY=                      # generate in step 3
APP_URL=https://your-domain.example.com

APP_USER_NAME=Your Name
APP_USER_EMAIL=you@example.com
APP_USER_PASSWORD=a-strong-password

DB_PASSWORD=                  # generate with: openssl rand -base64 32

LOG_LEVEL=warning

DOMAIN=your-domain.example.com
IMAGE_TAG=latest
```

> `LOG_LEVEL=warning` (not `error`): failed login attempts are logged at `warning` level so they remain visible in production logs. A stricter `error` level would silently drop this signal.

### 3. First deployment

Authenticate to GHCR once, manually (subsequent deploys via the CD workflow authenticate on their own):

```bash
docker login ghcr.io -u <your-github-username>
```

Start the database and cache first:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d postgres redis
```

Generate the application key and copy the output into `APP_KEY=` in `.env.prod`:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show
```

Run migrations and seed the user:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan migrate --force
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan db:seed --class=UserSeeder
```

Start the full stack:

```bash
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod up -d
```

### Secrets and key rotation

- `.env.prod` must not be world-readable: `chmod 600 .env.prod` on the VPS after editing it.
- `APP_KEY` encrypts session data and other Laravel-internal payloads. Rotating it invalidates all existing sessions and any data encrypted with the old key (Sanctum plaintext tokens are hashed, not encrypted with `APP_KEY`, so they are unaffected). Rotate only if the key is suspected to have leaked: generate a new one with `docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show`, update `.env.prod`, then `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d` to restart with it.
- `DB_PASSWORD` should be generated with `openssl rand -base64 32` or similar, never a memorable password, since it is never typed by a human.
- Dependabot is enabled (`.github/dependabot.yml`, weekly npm + Composer checks). Review and merge or dismiss each alert within a week of it opening; do not let alerts accumulate unreviewed.

### Automated backups

`docker/scripts/backup-postgres.sh` dumps the database and keeps the last 14 days of backups. It needs to live on the server alongside the compose file:

```bash
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/scripts/backup-postgres.sh -o backup-postgres.sh
chmod +x backup-postgres.sh
```

Install it as a daily cron job (commands below are for you to run over SSH, Claude never executes these):

```bash
crontab -e
# Add this line (adjust the path to where docker-compose.prod.yml lives):
0 3 * * * cd /opt/summit-stats/compose && BACKUP_DIR=/opt/summit-stats/backups ./backup-postgres.sh >> /var/log/summit-stats-backup.log 2>&1
```

To restore from a backup:

```bash
set -a; source .env.prod; set +a
gunzip -c /opt/summit-stats/backups/summit-stats-<timestamp>.sql.gz | \
  docker compose -f docker-compose.prod.yml --env-file .env.prod exec -T postgres psql -U "$DB_USERNAME" "$DB_DATABASE"
```

### Updating

Deployment is normally automatic: every push to `main` that passes CI and E2E triggers [`build-deploy`](.github/workflows/build-deploy.yml), which builds new images, pushes them to GHCR, and redeploys over SSH (pulls images, restarts the stack, runs pending migrations). No manual action is needed on the server for a routine update.

For a manual redeploy (hotfix, rollback to a specific `IMAGE_TAG`, or bypassing the E2E gate), trigger the `Build, Push & Deploy` workflow manually from the GitHub Actions tab (`workflow_dispatch`), or run the same steps by hand on the server:

```bash
cd /opt/summit-stats/compose
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod pull
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --remove-orphans
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan migrate --force
```

---

## 🇫🇷 Version française

Auto-hébergement de Summit Stats sur un serveur Linux via Docker Compose, derrière un reverse proxy Traefik avec TLS automatique (Let's Encrypt). La production fait tourner PHP-FPM, nginx, PostgreSQL et Redis en conteneurs ; les images sont buildées en CI et récupérées depuis GHCR, pas de code source ni d'étape de build sur le serveur lui-même.

### Prérequis

- Docker et Docker Compose v2 sur le VPS
- Une instance Traefik en cours d'exécution, attachée à un réseau Docker externe `traefik-public`, avec l'entrypoint `websecure` et un certresolver `letsencrypt` configurés
- Un Personal Access Token GitHub avec le scope `read:packages`, pour le premier `docker login ghcr.io` manuel (le workflow CD s'authentifie ensuite tout seul)

### 1. Préparation du serveur

```bash
mkdir -p /opt/summit-stats/compose
cd /opt/summit-stats/compose
```

Télécharger `docker-compose.prod.yml` depuis le dépôt :

```bash
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker-compose.prod.yml -o docker-compose.prod.yml
```

> Le chemin ci-dessus (`/opt/summit-stats`) est un exemple. Si vous utilisez le workflow CD du projet (`.github/workflows/build-deploy.yml`) plutôt qu'un déploiement manuel, son `DEPLOY_PATH` doit correspondre à l'endroit où vous placez réellement ce répertoire.

### 2. Configurer l'environnement

```bash
cp .env.prod.example .env.prod
```

Éditer `.env.prod` :

```dotenv
APP_KEY=                      # généré à l'étape 3
APP_URL=https://votre-domaine.exemple.fr

APP_USER_NAME=Votre Nom
APP_USER_EMAIL=vous@exemple.fr
APP_USER_PASSWORD=un-mot-de-passe-fort

DB_PASSWORD=                  # généré avec : openssl rand -base64 32

LOG_LEVEL=warning

DOMAIN=votre-domaine.exemple.fr
IMAGE_TAG=latest
```

> `LOG_LEVEL=warning` (pas `error`) : les tentatives de connexion échouées sont enregistrées au niveau `warning` pour rester visibles dans les journaux de production. Un niveau `error` plus strict les éliminerait silencieusement.

### 3. Premier déploiement

S'authentifier sur GHCR une fois, manuellement (les déploiements suivants via le workflow CD s'authentifient tout seuls) :

```bash
docker login ghcr.io -u <votre-nom-utilisateur-github>
```

Démarrer d'abord la base de données et le cache :

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d postgres redis
```

Générer la clé applicative et copier le résultat dans `APP_KEY=` de `.env.prod` :

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show
```

Lancer les migrations et créer l'utilisateur :

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan migrate --force
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan db:seed --class=UserSeeder
```

Démarrer le stack complet :

```bash
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod up -d
```

### Secrets et rotation des clés

- `.env.prod` ne doit pas être lisible par tous : `chmod 600 .env.prod` sur le VPS après édition.
- `APP_KEY` chiffre les données de session et d'autres payloads internes à Laravel. La faire tourner invalide toutes les sessions existantes et toute donnée chiffrée avec l'ancienne clé (les tokens Sanctum sont hashés, pas chiffrés avec `APP_KEY`, donc non affectés). Ne la faire tourner qu'en cas de suspicion de fuite : générer une nouvelle clé avec `docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show`, mettre à jour `.env.prod`, puis `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d` pour redémarrer avec.
- `DB_PASSWORD` doit être généré avec `openssl rand -base64 32` ou équivalent, jamais un mot de passe mémorisable puisqu'il n'est jamais saisi par un humain.
- Dependabot est activé (`.github/dependabot.yml`, vérifications hebdomadaires npm + Composer). Traiter chaque alerte (merge ou dismiss) sous une semaine, ne pas les laisser s'accumuler sans revue.

### Sauvegardes automatisées

`docker/scripts/backup-postgres.sh` sauvegarde la base de données et conserve les 14 derniers jours de sauvegardes. Il doit être présent sur le serveur, à côté du fichier compose :

```bash
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/scripts/backup-postgres.sh -o backup-postgres.sh
chmod +x backup-postgres.sh
```

L'installer en cron quotidien (commandes ci-dessous à exécuter vous-même en SSH, Claude ne les exécute jamais) :

```bash
crontab -e
# Ajouter cette ligne (adapter le chemin vers docker-compose.prod.yml) :
0 3 * * * cd /opt/summit-stats/compose && BACKUP_DIR=/opt/summit-stats/backups ./backup-postgres.sh >> /var/log/summit-stats-backup.log 2>&1
```

Pour restaurer depuis une sauvegarde :

```bash
set -a; source .env.prod; set +a
gunzip -c /opt/summit-stats/backups/summit-stats-<timestamp>.sql.gz | \
  docker compose -f docker-compose.prod.yml --env-file .env.prod exec -T postgres psql -U "$DB_USERNAME" "$DB_DATABASE"
```

### Mise à jour

Le déploiement est normalement automatique : chaque push sur `main` qui passe la CI et l'E2E déclenche [`build-deploy`](.github/workflows/build-deploy.yml), qui build les nouvelles images, les pousse sur GHCR, et redéploie en SSH (pull des images, redémarrage du stack, migrations en attente). Aucune action manuelle n'est nécessaire sur le serveur pour une mise à jour de routine.

Pour un redéploiement manuel (hotfix, rollback vers un `IMAGE_TAG` précis, ou pour bypasser le gate E2E), déclencher le workflow `Build, Push & Deploy` manuellement depuis l'onglet GitHub Actions (`workflow_dispatch`), ou reproduire les mêmes étapes à la main sur le serveur :

```bash
cd /opt/summit-stats/compose
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod pull
IMAGE_TAG=latest docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --remove-orphans
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan migrate --force
```
