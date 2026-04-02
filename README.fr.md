🇫🇷 Version française | [🇬🇧 English version](README.md)

---

# 🏔️ Summit Stats

> *Une application full-stack Laravel 12 + Vue.js 3 construite en TDD strict, architecture en couches services et 100% de couverture de tests — du parsing algorithmique de traces GPX jusqu'au dashboard de progression.*

![Status](https://img.shields.io/badge/Status-V2%20Livrée-brightgreen)
[![CI](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/ci.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/ci.yml)
[![E2E](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/e2e.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/e2e.yml)
[![CD](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/build-deploy.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/build-deploy.yml)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![License](https://img.shields.io/github/license/MarvinLeRouge/Summit-Stats?cacheSeconds=3600)

[![codecov backend](https://img.shields.io/codecov/c/github/MarvinLeRouge/Summit-Stats?flag=backend&label=backend&logo=codecov)](https://codecov.io/gh/MarvinLeRouge/Summit-Stats)
[![codecov frontend](https://img.shields.io/codecov/c/github/MarvinLeRouge/Summit-Stats?flag=frontend&label=frontend&logo=codecov)](https://codecov.io/gh/MarvinLeRouge/Summit-Stats)
![Tests](https://img.shields.io/badge/Tests-212%20passing-brightgreen)

---

## Versions

**V2 (actuelle — [v2.0.0](https://github.com/MarvinLeRouge/Summit-Stats/releases/tag/v2.0.0))** — Visualisation géographique : profil altimétrique zoomable avec synchronisation carte, carte OSM interactive avec tracé GPX, enrichissement automatique des altitudes via OpenTopoData, progression SSE à l'upload.

**V1 ([v1.0.0](https://github.com/MarvinLeRouge/Summit-Stats/releases/tag/v1.0.0))** — Pipeline complet : parsing GPX, segmentation par pente, 22 métriques par activité, API REST, dashboard Vue.js 3, 99% de couverture de tests, CI GitHub Actions.

---

## Concept

Les applications de sport classiques (Strava, Garmin Connect) offrent des statistiques globales, mais répondent mal aux questions vraiment utiles pour progresser :

- *Quelle est ma vitesse ascensionnelle sur les pentes à plus de 25% ?*
- *Comment évolue mon endurance sur les longues sorties en montagne ces six derniers mois ?*
- *Suis-je plus rapide en trail qu'en randonnée sur les dénivelés modérés ?*

Summit Stats segmente chaque trace GPX par type de terrain et classe de pente, puis vous laisse composer vos propres métriques de progression via une interface de filtrage dynamique.

---

## Chiffres clés

| Métrique | Valeur |
|---|---|
| Couverture de tests | **100%** (backend + frontend) |
| Tests automatisés | **118 backend · 62 unit frontend · 32 E2E = 212 tests** |
| Endpoints API | **7 routes REST** |
| Métriques par activité | **22 stats stockées en base** |
| Pipeline GPX | **4 services en TDD strict** |
| Lignes de code PHP | ~2 000 (hors migrations et config) |

---

## Fonctionnalités

- **Import GPX** — upload avec drag & drop, analyse automatique à l'import
- **Pipeline d'analyse GPX** — segmentation par type (montée / descente / plat) et classe de pente, formule de Haversine, lissage du dénivelé par moyenne glissante
- **22 statistiques par activité** — vitesse totale et en mouvement (pauses > 30s exclues), vitesse ascensionnelle (moyenne / → sommet / long tronçon non descendant), vitesse descensionnelle, répartition % par classe de pente en montée et en descente
- **Recalcul à la demande** — bouton par activité + commande `php artisan stats:recalculate`
- **Dashboard de progression** — graphes temporels recalculés à la volée par métrique, type, milieu, période et plage de pente
- **Filtres avancés** — combinables : type d'activité, milieu, période, activité spécifique, plage de pente (de / à)
- **Historique des sorties** — liste paginée avec filtres et stats résumées

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/     # Controllers minces — délèguent aux services
│   ├── Requests/            # Form Requests (validation)
│   └── Traits/              # ApiResponse — réponses JSON standardisées
├── Models/                  # Activity, Segment
└── Services/
    ├── ActivityService.php              # Persistance : store, update, recalculate, destroy
    └── Gpx/                             # Pipeline d'analyse GPX (TDD strict)
        ├── GpxParserService             # Parsing XML → tableau de points normalisés
        ├── ElevationCalculatorService   # Distance (Haversine), D+/D-, durée totale et en mouvement
        ├── SegmentationService          # Découpage par type et classe de pente
        ├── StatsAggregatorService       # Agrégation des 22 métriques
        └── GpxAnalysisOrchestrator      # Orchestration du pipeline complet

resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart
├── helpers/       # Formatage (distance, durée, vitesse, date)
├── stores/        # Pinia store (activities)
└── router/        # Vue Router avec garde d'authentification
```

---

## Tests

### Backend — Pest

```bash
php artisan test                        # lancer tous les tests
php artisan test --coverage             # avec rapport de couverture (nécessite pcov)
php artisan test --coverage --min=80    # avec seuil minimum
```

- **TDD strict** sur les 4 services GPX — tests écrits avant le code
- **Feature tests** sur les 7 endpoints API + commande Artisan
- **Tests unitaires** sur les modèles et le trait `ApiResponse`
- **Test de régression** sur une vraie trace GPX (11.8 km, ~470 m D+)
- Base de test SQLite en mémoire (`:memory:`) — isolation totale, suite complète en < 3s

### Frontend — Vitest

```bash
npm run test:coverage    # tests unitaires avec rapport de couverture
```

- 62 tests, 100% de couverture sur les composants, les helpers et le store Pinia
- Environnement JSDOM, Vue Test Utils

### E2E — Playwright

```bash
npm run test:e2e    # nécessite le stack Docker en cours d'exécution (voir section Docker)
```

- 5 fichiers de spec, 32 scénarios couvrant l'auth, l'upload, le dashboard, la liste et le détail des activités
- S'exécute sur le stack Docker complet (Nginx + PHP-FPM + PostgreSQL)
- Chromium uniquement

---

## CI/CD

### Hooks pre-commit

Husky + lint-staged s'exécutent automatiquement à chaque `git commit` :

- **Fichiers JS/Vue** stagés — `eslint --fix` puis `prettier --write`
- **Fichiers PHP** stagés — `vendor/bin/pint`

Les problèmes de style sont corrigés avant l'enregistrement du commit — ils n'atteignent jamais la CI.

### Workflow CI

[`CI`](.github/workflows/ci.yml) — déclenché sur push et pull request vers `main` :

1. Lint PHP (Pint) + lint JS (ESLint) — exécuté **avant** les tests pour échouer rapidement sur les problèmes de style
2. Tests backend PHP avec couverture (Pest + pcov) — rapport envoyé à Codecov (flag `backend`)
3. Tests unitaires frontend (Vitest) — rapport envoyé à Codecov (flag `frontend`)

### Workflow E2E

[`E2E`](.github/workflows/e2e.yml) — déclenché sur push vers `main` :

- Build et démarrage du stack Docker complet
- Migrations et seed des données de test (utilisateur + activité exemple)
- Exécution de la suite Playwright (Chromium)
- Upload du rapport HTML Playwright en artifact en cas d'échec (rétention 7 jours)
- **Conditionne le déploiement** — le workflow CD ne se déclenche que si l'E2E passe

### Workflow CD

[`build-deploy`](.github/workflows/build-deploy.yml) — déclenché par le succès de l'E2E via `workflow_run` :

- Build et push de deux images Docker vers GHCR : `app` (PHP-FPM) et `nginx` (assets intégrés)
- Images taguées avec le SHA court (`sha-xxxxxxx`) et `latest`
- Déploiement sur le serveur de production via SSH : pull des nouvelles images, `docker compose up -d`, application des migrations en attente
- `workflow_dispatch` disponible pour les redéploiements manuels (hotfixes, rollbacks) — bypass du gate E2E

```
push main
    ├── CI ─────────── lint → tests → couverture
    └── E2E ─────────── Playwright (stack Docker complet)
              │ workflow_run (succès uniquement)
              ▼
         build-deploy ── build → GHCR → deploy SSH

workflow_dispatch ──────► build-deploy  (manuel, bypass E2E)
```

---

## Production

L'application tourne sur un VPS derrière un reverse proxy Traefik partagé avec TLS automatique via Let's Encrypt. Les images sont buildées en CI et stockées dans GHCR — pas de code source sur le serveur, pas d'étape de build en production.

### Stack

| Service | Image | Rôle |
|---|---|---|
| `nginx` | custom (nginx:alpine) | Serveur web, assets statiques, proxy cache tuiles OSM |
| `app` | custom (PHP 8.4-FPM) | Application Laravel |
| `postgres` | postgres:16-alpine | Base de données production |
| `redis` | redis:7-alpine | Cache, sessions, file |
| `queue` | custom (PHP 8.4-FPM) | Worker de file Laravel |

Seul `nginx` est exposé à Traefik via le réseau Docker partagé `traefik-public`. Les autres services tournent dans un réseau Docker privé.

### Cache de tuiles OSM

Nginx proxifie les requêtes de tuiles OpenStreetMap via `/tiles/{z}/{x}/{y}.png` et met en cache les réponses sur un volume Docker persistant (plafonné à 1 Go, TTL 30 jours). Réduit la charge sur l'infrastructure OSM et accélère l'affichage des zones déjà explorées.

---

## Endpoints API

Toutes les routes protégées par `Authorization: Bearer {token}`.

| Méthode | Route | Description |
|---|---|---|
| `POST` | `/api/activities` | Import GPX + analyse automatique |
| `GET` | `/api/activities` | Liste paginée (filtres disponibles) |
| `GET` | `/api/activities/{id}` | Détail + segments |
| `PUT` | `/api/activities/{id}` | Mise à jour des métadonnées |
| `DELETE` | `/api/activities/{id}` | Suppression activité + fichier GPX |
| `POST` | `/api/activities/{id}/recalculate` | Recalcul des stats depuis le GPX brut |
| `GET` | `/api/stats` | Données de progression pour graphes |

**Exemple — `/api/stats`**

```
GET /api/stats?metric=avg_ascent_speed_mh&type=trail&slope_min=15&slope_max=35&date_from=2024-01-01
```

```json
{
  "data": [
    { "date": "2024-03-15", "value": 423.5, "activity_title": "Aiguilles Rouges" },
    { "date": "2024-04-02", "value": 512.0, "activity_title": "Col de Balme" }
  ],
  "meta": { "metric": "avg_ascent_speed_mh", "unit": "m/h", "count": 2 }
}
```
---

## Installation

**Prérequis** — PHP >= 8.3 (`pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `pcov`), Composer >= 2, Node.js >= 18

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git && cd Summit-Stats
composer install && npm install
cp .env.example .env
# Renseigner APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD
php artisan key:generate
# ⚠️ Créer manuellement .env.testing avec DB_DATABASE=:memory: (non committé)
touch database/database.sqlite && php artisan migrate
php artisan db:seed --class=UserSeeder   # token affiché dans la console
php artisan serve                         # → http://localhost:8000
npm run dev                               # → http://localhost:5173 (HMR)
```

---

## Docker

Un stack de développement complet est fourni via Docker Compose :

| Service | Description | Port hôte |
|---|---|---|
| `nginx` | Serveur web | 8081 |
| `app` | PHP-FPM (Laravel) | — |
| `postgres` | PostgreSQL 16 | 5433 |
| `redis` | Redis 7 | 6379 |
| `queue` | Worker de file Laravel | — |
| `vite` | Serveur Vite (HMR) | 5174 |

```bash
cp .env.example .env
# Renseigner APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD et DB_PASSWORD
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=UserSeeder   # token affiché dans la console
```

L'application est alors disponible sur `http://localhost:8081`.

> Note : PostgreSQL est utilisé dans Docker. SQLite (`:memory:`) est réservé aux tests unitaires et feature.

---

## Contexte

Projet personnel à double vocation :

- **Autoformation** — architecture en couches services (TDD, responsabilité unique, injection de dépendances), algorithmes géospatiaux (Haversine, lissage par moyenne glissante, segmentation de traces GPS), et outillage moderne (Laravel 12, Vue.js 3 Composition API, Pinia, Chart.js, Pest, GitHub Actions)
- **Portfolio** — démonstration d'une approche structurée, testée et documentée, du cadrage jusqu'à la CI

---

## 🗂️ Classes de pente

| Class | Label | Intervalle |
|---|---|---|
| `lt5`   | Plat     | < 5 %       |
| `5_15`  | Modéré   | 5 % – 15 %  |
| `15_25` | Pentu    | 15 % – 25 % |
| `25_35` | Raide    | 25 % – 35 % |
| `gt35`  | Extrême  | > 35 %      |

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Backend | ![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?logo=laravel&logoColor=white&style=flat-square) ![PHP](https://img.shields.io/badge/PHP_8.4-777BB4?logo=php&logoColor=white&style=flat-square) |
| Database | ![PostgreSQL](https://img.shields.io/badge/PostgreSQL_16-336791?logo=postgresql&logoColor=white&style=flat-square) ![SQLite](https://img.shields.io/badge/SQLite_%3Amemory%3A_(tests)-003B57?logo=sqlite&logoColor=white&style=flat-square) |
| Auth | Laravel Sanctum (single-user token) |
| Frontend | ![Vue.js](https://img.shields.io/badge/Vue.js_3-4FC08D?logo=vuedotjs&logoColor=white&style=flat-square) ![Vite](https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white&style=flat-square) |
| Charts | ![Chart.js](https://img.shields.io/badge/Chart.js-FF6384?logo=chartdotjs&logoColor=white&style=flat-square) |
| Map | ![Leaflet](https://img.shields.io/badge/Leaflet-199900?logo=leaflet&logoColor=white&style=flat-square) |
| State | ![Pinia](https://img.shields.io/badge/Pinia-FFD859?logo=pinia&logoColor=black&style=flat-square) |
| Tests backend | ![Pest](https://img.shields.io/badge/Pest-8A2BE2?style=flat-square) — strict TDD, 100% coverage |
| Tests frontend | ![Vitest](https://img.shields.io/badge/Vitest-6E9F18?logo=vitest&logoColor=white&style=flat-square) + Vue Test Utils — 100% coverage |
| Tests E2E | ![Playwright](https://img.shields.io/badge/Playwright-2EAD33?logo=playwright&logoColor=white&style=flat-square) (Chromium) |
| PHP linting | ![Pint](https://img.shields.io/badge/Laravel_Pint-FF2D20?logo=laravel&logoColor=white&style=flat-square) (PSR-12) |
| JS linting | ![ESLint](https://img.shields.io/badge/ESLint-4B32C3?logo=eslint&logoColor=white&style=flat-square) ![Prettier](https://img.shields.io/badge/Prettier-F7B93E?logo=prettier&logoColor=black&style=flat-square) |
| Infrastructure | ![Docker](https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white&style=flat-square) ![Nginx](https://img.shields.io/badge/Nginx-009639?logo=nginx&logoColor=white&style=flat-square) PHP-FPM |
| Production | ![Traefik](https://img.shields.io/badge/Traefik-24A1C1?logo=traefikproxy&logoColor=white&style=flat-square) reverse proxy + TLS Let's Encrypt |
| Pre-commit | ![Husky](https://img.shields.io/badge/Husky-000000?style=flat-square&logo=git&logoColor=white) + lint-staged (auto-fix sur les fichiers stagés) |
| CI | ![GitHub Actions](https://img.shields.io/badge/GitHub_Actions-2088FF?logo=githubactions&logoColor=white&style=flat-square) ![Codecov](https://img.shields.io/badge/Codecov-F01F7A?logo=codecov&logoColor=white&style=flat-square) |
| CD | ![GHCR](https://img.shields.io/badge/GHCR-181717?logo=github&logoColor=white&style=flat-square) GitHub Container Registry |

---

## 🗺️ Roadmap

### ✅ V1 — Livrée

- [x] Cadrage et architecture projet
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** — Modèle de données (migrations, Eloquent)
- [x] **P3** — Algo GPX (parsing, segmentation, stats) — *strict TDD*
- [x] **P4** — REST API (7 endpoints + feature tests)
- [x] **P5** — Frontend Vue.js (dashboard, charts, dynamic filters)
- [x] **P6** — Qualité (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7** — DevOps (GitHub Actions CI, documentation)

### ✅ V2 — Livrée

- [x] **P1** — Stockage des points GPS + endpoint `/api/activities/{id}/track`
- [x] **P2** — Profil altimétrique (Chart.js + zoom)
- [x] **P3** — Carte OSM avec tracé GPX (Leaflet)
- [x] **P4** — Qualité (tests, couverture, linting)
- [x] **P5** — DevOps (mise à jour CI, documentation V2)

### ✅ V3 — Livrée

- [x] Suite de tests E2E (Playwright — 32 scénarios, workflow CI dédié)
- [x] Stack Docker Compose (Nginx, PHP-FPM, PostgreSQL, Redis, worker de file)
- [x] Couverture frontend sur Codecov (badges par flag)
- [x] Déploiement en production — Traefik, HTTPS, GHCR, pipeline CD par SSH
- [x] Hooks pre-commit — Husky + lint-staged (auto-fix PHP et JS/Vue)
- [x] Pipeline CI/CD optimisé — lint en premier, déploiement conditionné par l'E2E, workflow chaining

---

## 📋 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.
