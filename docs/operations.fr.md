🇫🇷 Version française | [🇬🇧 English version](operations.md)

---

# Exploitation

## Environnement de production

L'application tourne sur un VPS derrière un reverse proxy Traefik partagé, avec TLS automatique via Let's Encrypt. Les images Docker sont buildées en CI et stockées dans GHCR, pas de code source ni d'étape de build sur le serveur.

### Stack

| Service | Image | Rôle |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Serveur web, assets statiques, cache proxy des tuiles OSM |
| `app` | custom (PHP 8.4-FPM) | Application Laravel |
| `postgres` | postgres:16-alpine | Base de données de production |
| `redis` | redis:7-alpine | Cache, sessions, queue |
| `queue` | custom (PHP 8.4-FPM) | Worker de queue Laravel |

Seul `nginx` est exposé à Traefik via le réseau partagé `traefik-public`. Tous les autres services tournent sur un réseau Docker privé (`docker-compose.prod.yml`).

### Cache proxy des tuiles OSM

Nginx fait proxy des requêtes de tuiles OpenStreetMap via `/tiles/{z}/{x}/{y}.png` et met en cache les réponses sur un volume Docker persistant (plafonné à 1 Go, TTL 30 jours). Réduit la charge sur l'infrastructure d'OSM et accélère les visites répétées sur des zones déjà explorées.

## Déploiement continu

`.github/workflows/build-deploy.yml` gère le pipeline complet de build et de déploiement.

**Déclenchement**
- Automatiquement, quand le workflow `E2E` réussit sur `main`
- Manuellement, via `workflow_dispatch` (contourne le garde-fou E2E, utilisé pour les hotfix, rollbacks ou redéploiements)

**Job de build**
- Build les cibles `production` et `nginx-prod` du `Dockerfile` multi-stage avec Docker Buildx
- Tague chaque image avec `sha-<short-sha>` et `latest`
- Pousse les deux images vers GHCR (`ghcr.io/marvinlerouge/summit-stats/app`, `.../nginx`)

**Job de déploiement**
- Se connecte au serveur de production en SSH
- Récupère `docker-compose.prod.yml` depuis le commit exact qui a été buildé
- Récupère les nouvelles images et redéploie la stack (`docker compose up -d --remove-orphans`)
- Exécute les migrations en attente (`php artisan migrate --force`)
- Affiche le statut de la stack résultante

## Configuration initiale du serveur

Prérequis sur le serveur :
- Docker et Docker Compose installés
- Une instance Traefik déjà en fonctionnement, exposant un entrypoint `web` (port 80) et `websecure` (port 443), avec un resolver de certificat `letsencrypt` et un réseau Docker externe `traefik-public`
- Accès SSH configuré, avec `DEPLOY_SSH_HOST`, `DEPLOY_SSH_USER` et `DEPLOY_SSH_PRIVATE_KEY` définis comme secrets du dépôt GitHub

Étapes :
1. Créer le répertoire de déploiement sur le serveur (correspond à `DEPLOY_PATH` dans le workflow)
2. Copier `.env.prod.example` vers `.env.prod` dans ce répertoire et renseigner les valeurs réelles (`APP_KEY`, identifiants de base de données, `DOMAIN`, etc.) ; `.env.prod` n'est jamais commité
3. Déclencher le workflow (push sur `main` une fois l'E2E réussi, ou exécution manuelle) pour builder les images et effectuer le premier déploiement

## Secrets et rotation des clés

- `.env.prod` ne doit pas être lisible par tous : `chmod 600 .env.prod` sur le VPS après édition.
- `APP_KEY` chiffre les données de session et d'autres payloads internes à Laravel. La faire tourner invalide toutes les sessions existantes et toute donnée chiffrée avec l'ancienne clé (les tokens Sanctum sont hashés, pas chiffrés avec `APP_KEY`, donc non affectés). Ne la faire tourner qu'en cas de suspicion de fuite : générer une nouvelle clé avec `docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm app php artisan key:generate --show`, mettre à jour `.env.prod`, puis `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d` pour redémarrer avec.
- `DB_PASSWORD` doit être généré avec `openssl rand -base64 32` ou équivalent, jamais un mot de passe mémorisable puisqu'il n'est jamais saisi par un humain.
- `LOG_LEVEL=warning` (pas `error`) : les tentatives de connexion échouées sont enregistrées au niveau `warning` pour rester visibles dans les journaux de production. Un niveau `error` plus strict les éliminerait silencieusement.
- Dependabot est activé (`.github/dependabot.yml`, vérifications hebdomadaires npm + Composer). Traiter chaque alerte (merge ou dismiss) sous une semaine, ne pas les laisser s'accumuler sans revue.

## Sauvegardes automatisées

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

## Mise à jour

Les déploiements sont automatiques : dès qu'un changement arrive sur `main` et que la suite E2E réussit, `build-deploy.yml` build de nouvelles images et redéploie la stack, migrations en attente incluses.

Pour redéployer sans nouveau commit (par exemple un rollback vers un tag d'image précédent), déclencher le workflow manuellement via `workflow_dispatch`.
