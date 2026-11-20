🇫🇷 Version française | [🇬🇧 English version](operations.md)

---

# Exploitation

## Environnement de production

L'application tourne sur un VPS derrière un reverse proxy Traefik partagé, avec TLS automatique via Let's Encrypt. Les images Docker sont buildées en CI et stockées dans GHCR, pas de code source ni d'étape de build sur le serveur.

### Stack

| Service | Image | Rôle |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Serveur web, assets statiques, cache proxy des tuiles OSM |
| `app` | custom (PHP 8.5-FPM) | Application Laravel |
| `postgres` | postgres:16-alpine | Base de données de production |
| `redis` | redis:7-alpine | Cache, sessions, queue |
| `queue` | custom (PHP 8.5-FPM) | Worker de queue Laravel |

Seul `nginx` est exposé à Traefik via le réseau partagé `traefik-public`. Tous les autres services tournent sur un réseau Docker privé (`docker-compose.prod.yml`).

### Cache proxy des tuiles OSM

Nginx fait proxy des requêtes de tuiles OpenStreetMap via `/tiles/{z}/{x}/{y}.png` et met en cache les réponses sur un volume Docker persistant (plafonné à 1 Go, TTL 30 jours). Réduit la charge sur l'infrastructure d'OSM et accélère les visites répétées sur des zones déjà explorées.

## Déploiement continu

`.github/workflows/build-push-deploy.yml` gère le pipeline complet de build et de déploiement.

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

Organisation du serveur, sous `/home/mlr/marvinlerouge.dev/summit-stats/` :

```
compose/
└── docker-compose.prod.yml   ← récupéré par le workflow de déploiement à chaque déploiement
shared/
├── env/
│   ├── app.env               ← réglages non secrets (chmod 600, jamais commité)
│   └── secrets.env           ← secrets (chmod 600, jamais commité)
└── backups/                  ← sauvegardes quotidiennes de la base
```

Les conteneurs reçoivent leur configuration uniquement via `env_file` : le fichier compose n'interpole rien depuis un fichier (le domaine est écrit dans la règle Traefik, `IMAGE_TAG` est fourni par le workflow de déploiement).

| Fichier | Chargé par | Contenu | Modèle |
|---|---|---|---|
| `shared/env/app.env` | app, queue | réglages non secrets : identité et URL de l'app, logs, drivers de session, cache et file, Redis, élévation, Sanctum | [`docker/env/app.env.example`](../docker/env/app.env.example) |
| `shared/env/secrets.env` | app, queue (en dernier, donc prioritaire), postgres | chaque sujet contenant un secret, regroupé en entier : `APP_KEY`, base de données (`DB_CONNECTION/HOST/PORT`, `POSTGRES_*`), `APP_USER_*` | [`docker/env/secrets.env.example`](../docker/env/secrets.env.example) |

Les identifiants de la base n'existent qu'une fois, sous les noms de l'image postgres (`POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`) : `config/database.php` les utilise quand `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` ne sont pas définis. Le développement local garde `DB_*` dans le `.env` racine.

Étapes :
1. Créer `shared/env/` sur le serveur et remplir `app.env` et `secrets.env` à partir des modèles, puis `chmod 600 shared/env/*.env` (le workflow de déploiement crée les répertoires, jamais les fichiers)
2. Déclencher le workflow (push sur `main` une fois l'E2E réussi, ou exécution manuelle) pour builder les images et effectuer le premier déploiement

## Secrets et rotation des clés

- Les deux fichiers d'environnement ne doivent pas être lisibles par tous : `chmod 600 shared/env/*.env` sur le VPS après édition.
- `APP_KEY` chiffre les données de session et d'autres payloads internes à Laravel. La faire tourner invalide toutes les sessions existantes et toute donnée chiffrée avec l'ancienne clé (les tokens Sanctum sont hashés, pas chiffrés avec `APP_KEY`, donc non affectés). Ne la faire tourner qu'en cas de suspicion de fuite : depuis `compose/`, générer une nouvelle clé avec `docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show`, mettre à jour `shared/env/secrets.env`, puis `docker compose -f docker-compose.prod.yml up -d` pour redémarrer avec.
- `POSTGRES_PASSWORD` doit être généré avec `openssl rand -base64 32` ou équivalent, jamais un mot de passe mémorisable puisqu'il n'est jamais saisi par un humain.
- `LOG_LEVEL=warning` (pas `error`) : les tentatives de connexion échouées sont enregistrées au niveau `warning` pour rester visibles dans les journaux de production. Un niveau `error` plus strict les éliminerait silencieusement.
- `LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter` : les journaux de production sont écrits sur stderr à raison d'un objet JSON par ligne (`message`, `context`, `level_name`, `datetime`, ...), donc la sortie de `docker logs` se filtre avec `jq` ou s'envoie telle quelle à un collecteur de logs. Les appels de log gardent un message constant et placent les données variables dans le tableau de contexte. Le développement local garde le format texte lisible.
- Dependabot est activé (`.github/dependabot.yml`, vérifications hebdomadaires npm + Composer). Traiter chaque alerte (merge ou dismiss) sous une semaine, ne pas les laisser s'accumuler sans revue.

## Sauvegardes automatisées

`docker/scripts/backup-postgres.sh` sauvegarde la base de données et conserve les 14 derniers jours de sauvegardes. Il lit les identifiants dans le conteneur postgres, il n'a donc besoin d'aucun fichier d'environnement. Il doit être présent sur le serveur, à côté du fichier compose :

```bash
cd /home/mlr/marvinlerouge.dev/summit-stats/compose
curl -fsSL https://raw.githubusercontent.com/MarvinLeRouge/Summit-Stats/main/docker/scripts/backup-postgres.sh -o backup-postgres.sh
chmod +x backup-postgres.sh
```

L'installer en cron quotidien (commandes ci-dessous à exécuter vous-même en SSH, Claude ne les exécute jamais) :

```bash
crontab -e
# Ajouter cette ligne :
0 3 * * * cd /home/mlr/marvinlerouge.dev/summit-stats/compose && BACKUP_DIR=../shared/backups ./backup-postgres.sh >> ../shared/backups/backup.log 2>&1
```

Pour restaurer depuis une sauvegarde, depuis `compose/` :

```bash
gunzip -c ../shared/backups/summit-stats-<timestamp>.sql.gz | \
  docker compose -f docker-compose.prod.yml exec -T postgres sh -c 'psql -U "$POSTGRES_USER" "$POSTGRES_DB"'
```

## Vérification de santé

`GET /health` exécute `select 1` sur la base de données et un `ping` Redis. Il répond 200 (« Application up ») quand les deux répondent, 500 sinon, sans détail d'erreur dans la réponse (l'exception est remontée à Sentry). Il ne demande pas d'authentification : un service de supervision externe peut interroger `https://summit-stats.marvinlerouge.dev/health`.

Il n'y a volontairement pas de `healthcheck` Docker sur `app` ni sur `nginx` : Traefik cesse de router vers les conteneurs « unhealthy », donc une panne de base deviendrait un 404 de Traefik au lieu du 500 de l'app.

## Suivi des erreurs (Sentry)

Les exceptions backend non gérées (5xx, jobs de queue en échec, erreurs du pipeline d'import) et les erreurs frontend non interceptées sont remontées à Sentry. Les erreurs attendues ne le sont pas : validation et fichiers GPX invalides (422), authentification (401), ressources introuvables (404), limitation de débit (429). Sans DSN, rien n'est envoyé : le développement local et la CI ne remontent rien.

Seules les stack traces sont envoyées : ni utilisateur, ni IP, ni cookies, ni en-têtes, ni corps de requête (les imports GPX contiennent des lieux de domicile), ni query strings, ni paramètres SQL, ni breadcrumbs, ni arguments de fonctions, ni lignes de source, et pas de tracing. Backend : `data_collection`, `before_send` et `before_breadcrumb` dans `config/sentry.php` (`App\Services\Observability\SentryScrubber`). Frontend : `resources/js/observability.js`.

Les événements du navigateur partent vers `POST /api/<hash du DSN>` sur l'origine de l'app, puis `SentryTunnelController` les transmet à Sentry : les bloqueurs de publicité et la protection contre le pistage des navigateurs ne peuvent pas les bloquer. Le relais n'accepte que le DSN frontend (403 sinon), plafonne les envelopes à 1 Mo et est limité à 30 requêtes par minute et par IP.

| Variable | Lue par | Local | Production |
|---|---|---|---|
| `SENTRY_LARAVEL_DSN` | backend, à l'exécution | `.env` | `shared/env/secrets.env` |
| `SENTRY_FRONTEND_DSN` | backend, à l'exécution (liste autorisée du relais) | `.env` | `shared/env/secrets.env` |
| `SENTRY_ENVIRONMENT` | backend, à l'exécution (vide : `APP_ENV`) | `.env` | `shared/env/app.env` (`production`) |
| `VITE_SENTRY_DSN` | frontend, au build | `.env` | secret GitHub `VITE_SENTRY_DSN`, argument de build Docker |
| `VITE_SENTRY_ENVIRONMENT` | frontend, au build (vide : `development`) | `.env` | `production`, défini dans `build-push-deploy.yml` |

`SENTRY_FRONTEND_DSN` doit être identique, caractère pour caractère, à `VITE_SENTRY_DSN` : le navigateur calcule le chemin du relais à partir du DSN intégré au bundle, donc un écart fait finir chaque événement frontend en 404. Le backend logue alors `Sentry tunnel path matches no configured DSN`.

Mise en place, une fois :

1. Dans Sentry (région EU), un projet par côté : `summit-stats-backend` (Laravel) et `summit-stats-frontend` (Vue). Dans chacun, *Settings*, *Security & Privacy* : activer *Data Scrubber*, *Use Default Scrubbers* et *Prevent Storing of IP Addresses*.
2. Secret GitHub du dépôt `VITE_SENTRY_DSN` : le DSN frontend.
3. Sur le VPS : les deux DSN dans `shared/env/secrets.env`, `SENTRY_ENVIRONMENT=production` dans `shared/env/app.env`, puis redéployer.

Vérification après un déploiement : `docker compose -f docker-compose.prod.yml exec app php artisan sentry:test` pour le backend. Pour le frontend, lancer `setTimeout(() => { throw new Error('sentry check') }, 0)` dans la console du navigateur, d'abord sans extension, puis avec un bloqueur de publicité actif pour valider le relais. L'issue doit apparaître sous `environment: production`, avec un `POST /api/<hash>` qui répond 200 et aucune requête vers `sentry.io`.

## Mise à jour

Les déploiements sont automatiques : dès qu'un changement arrive sur `main` et que la suite E2E réussit, `build-push-deploy.yml` build de nouvelles images et redéploie la stack, migrations en attente incluses.

Pour redéployer sans nouveau commit (par exemple un rollback vers un tag d'image précédent), déclencher le workflow manuellement via `workflow_dispatch`.
