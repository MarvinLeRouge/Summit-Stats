[🇫🇷 Version française](#version-française) | [🇬🇧 English version](#english-version)

---

# Version Française

# 🏔️ Summit Stats

> *Une application full-stack Laravel 12 + Vue.js 3 construite en TDD strict, architecture en couches services et 99% de couverture de tests — du parsing algorithmique de traces GPX jusqu'au dashboard de progression.*

[![CI](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/laravel.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Coverage](https://img.shields.io/badge/Coverage-99%25-brightgreen)
![Tests](https://img.shields.io/badge/Tests-89%20passing-brightgreen)
![Status](https://img.shields.io/badge/Status-Delivered-brightgreen)
![License](https://img.shields.io/github/license/MarvinLeRouge/Summit-Stats)

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
| Couverture de tests | **99%** |
| Tests automatisés | **89 tests, 271+ assertions** |
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

```bash
php artisan test                        # lancer tous les tests
php artisan test --coverage             # avec rapport de couverture (nécessite pcov)
php artisan test --coverage --min=80    # avec seuil minimum
```

- **TDD strict** sur les 4 services GPX — tests écrits avant le code
- **Feature tests** sur les 7 endpoints API + commande Artisan
- **Tests unitaires** sur les modèles et le trait `ApiResponse`
- **Test de régression** sur une vraie trace GPX (11.8 km, ~470 m D+)
- Base de test SQLite en mémoire (`:memory:`) — isolation totale, suite complète en < 1s

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
| Backend | Laravel 12 |
| Database | SQLite |
| Auth | Laravel Sanctum (single-user token) |
| Frontend | Vue.js 3 + Vite |
| Charts | Chart.js + chartjs-adapter-date-fns |
| State | Pinia |
| Testing | Pest — strict TDD, 99% coverage |
| PHP linting | Laravel Pint (PSR-12) |
| JS linting | ESLint + Prettier |
| CI | GitHub Actions |

---

## 🗺️ Roadmap

- [x] Cadrage et architecture projet
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** — Modèle de données (migrations, Eloquent)
- [x] **P3** — Algo GPX (parsing, segmentation, stats) — *strict TDD*
- [x] **P4** — REST API (7 endpoints + feature tests)
- [x] **P5** — Frontend Vue.js (dashboard, charts, dynamic filters)
- [x] **P6** — Qualité (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7** — DevOps (GitHub Actions CI, documentation)

---

## 📋 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---
---

# English Version

# 🏔️ Summit Stats

> *A full-stack Laravel 12 + Vue.js 3 application built with strict TDD, layered service architecture and 99% test coverage — from geospatial GPX parsing algorithms to a dynamic progression dashboard.*

[![CI](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/laravel.yml/badge.svg)](https://github.com/MarvinLeRouge/Summit-Stats/actions)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Coverage](https://img.shields.io/badge/Coverage-99%25-brightgreen)
![Tests](https://img.shields.io/badge/Tests-89%20passing-brightgreen)
![Status](https://img.shields.io/badge/Status-Delivered-brightgreen)
![License](https://img.shields.io/github/license/MarvinLeRouge/Summit-Stats)

---

## Concept

Standard sports apps (Strava, Garmin Connect) provide global statistics but fall short for meaningful progression analysis:

- *What is my ascent speed on slopes steeper than 25%?*
- *How has my endurance evolved on long mountain outings over the last six months?*
- *Am I faster in trail running than hiking on moderate gradients?*

Summit Stats segments each GPX trace by terrain type and slope class, then lets you compose your own progression metrics through a dynamic filtering interface.

---

## Key figures

| Metric | Value |
|---|---|
| Test coverage | **99%** |
| Automated tests | **89 tests, 271+ assertions** |
| API endpoints | **7 REST routes** |
| Metrics per activity | **22 stats stored in database** |
| GPX pipeline | **4 services, strict TDD** |
| PHP lines of code | ~2,000 (excluding migrations and config) |

---

## Features

- **GPX import** — drag & drop upload, automatic analysis on import
- **GPX analysis pipeline** — segmentation by type (ascent / descent / flat) and slope class, Haversine formula, elevation smoothing via sliding average window
- **22 stats per activity** — total and moving speed (pauses > 30s excluded), ascent speed (average / to summit / longest non-descending segment), descent rate, % breakdown by slope class for both ascent and descent
- **On-demand recalculation** — per-activity button + `php artisan stats:recalculate` command
- **Progression dashboard** — time-series charts recalculated on the fly by metric, type, environment, period and slope range
- **Advanced filters** — combinable: activity type, environment, period, specific activity, slope range (from / to)
- **Activity history** — paginated list with filters and summary stats

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/Api/     # Thin controllers — delegate all logic to services
│   ├── Requests/            # Form Requests (input validation)
│   └── Traits/              # ApiResponse — standardised JSON responses
├── Models/                  # Activity, Segment
└── Services/
    ├── ActivityService.php              # Persistence: store, update, recalculate, destroy
    └── Gpx/                             # GPX analysis pipeline (strict TDD)
        ├── GpxParserService             # XML parsing → normalised point array
        ├── ElevationCalculatorService   # Haversine distance, D+/D-, total & moving duration
        ├── SegmentationService          # Segmentation by terrain type and slope class
        ├── StatsAggregatorService       # Aggregation of 22 metrics
        └── GpxAnalysisOrchestrator      # Full pipeline orchestration

resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart
├── helpers/       # Formatting (distance, duration, speed, date)
├── stores/        # Pinia store (activities)
└── router/        # Vue Router with authentication guard
```

---

## Testing

```bash
php artisan test                        # run all tests
php artisan test --coverage             # with coverage report (requires pcov)
php artisan test --coverage --min=80    # with minimum threshold
```

- **Strict TDD** on all 4 GPX services — tests written before implementation
- **Feature tests** on all 7 API endpoints + Artisan command
- **Unit tests** on models and the `ApiResponse` trait
- **Regression test** on a real GPX trace (11.8 km, ~470 m elevation gain)
- In-memory SQLite (`:memory:`) — full isolation, entire suite runs in < 1s

---

## API endpoints

All routes protected by `Authorization: Bearer {token}`.

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/activities` | GPX import + automatic analysis |
| `GET` | `/api/activities` | Paginated list (filters available) |
| `GET` | `/api/activities/{id}` | Detail + segments |
| `PUT` | `/api/activities/{id}` | Update metadata |
| `DELETE` | `/api/activities/{id}` | Delete activity + GPX file |
| `POST` | `/api/activities/{id}/recalculate` | Recalculate stats from raw GPX |
| `GET` | `/api/stats` | Progression data for charts |

**Example — `/api/stats`**

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

**Prerequisites** — PHP >= 8.3 (`pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `pcov`), Composer >= 2, Node.js >= 18

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git && cd Summit-Stats
composer install && npm install
cp .env.example .env
# Fill in APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD
php artisan key:generate
# ⚠️ Manually create .env.testing with DB_DATABASE=:memory: (not committed)
touch database/database.sqlite && php artisan migrate
php artisan db:seed --class=UserSeeder   # token printed in console
php artisan serve                         # → http://localhost:8000
npm run dev                               # → http://localhost:5173 (HMR)
```
---

## About

Personal project with a dual purpose:

- **Learning** — layered service architecture (TDD, single responsibility, dependency injection), geospatial algorithms (Haversine, sliding average smoothing, GPS trace segmentation), and modern tooling (Laravel 12, Vue.js 3 Composition API, Pinia, Chart.js, Pest, GitHub Actions)
- **Portfolio** — demonstrates a structured, tested and documented approach, from scoping to CI

---

## 🗂️ Slope classes

| Class | Label | Range |
|---|---|---|
| `lt5`   | Flat     |  < 5 %      |
| `5_15`  | Moderate | 5 % – 15 %  |
| `15_25` | Steep    | 15 % – 25 % |
| `25_35` | Hard     | 25 % – 35 % |
| `gt35`  | Extreme  | > 35 %      |

---

## 🛠️ Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Database | SQLite |
| Auth | Laravel Sanctum (single-user token) |
| Frontend | Vue.js 3 + Vite |
| Charts | Chart.js + chartjs-adapter-date-fns |
| State | Pinia |
| Testing | Pest — strict TDD, 99% coverage |
| PHP linting | Laravel Pint (PSR-12) |
| JS linting | ESLint + Prettier |
| CI | GitHub Actions |

---

## 🗺️ Roadmap

- [x] Project scoping and architecture / Cadrage et architecture
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** — Data model / Modèle de données (migrations, Eloquent)
- [x] **P3** — GPX algorithm (parsing, segmentation, stats) — *strict TDD*
- [x] **P4** — REST API (7 endpoints + feature tests)
- [x] **P5** — Vue.js frontend (dashboard, charts, dynamic filters)
- [x] **P6** — Quality (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7** — DevOps (GitHub Actions CI, documentation)

---

## 📋 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.