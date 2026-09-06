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

## Mise à jour

Les déploiements sont automatiques : dès qu'un changement arrive sur `main` et que la suite E2E réussit, `build-deploy.yml` build de nouvelles images et redéploie la stack, migrations en attente incluses.

Pour redéployer sans nouveau commit (par exemple un rollback vers un tag d'image précédent), déclencher le workflow manuellement via `workflow_dispatch`.
