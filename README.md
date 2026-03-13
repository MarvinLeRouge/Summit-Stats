# 🏔️ Summit Stats

Outil de visualisation de progression pour les activités outdoor (randonnée, trail running).  
Importez vos traces GPX, laissez Summit Stats les analyser, et visualisez votre progression sur les métriques qui comptent vraiment.

![CI](https://github.com/MarvinLeRouge/Summit-Stats/actions/workflows/laravel.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Coverage](https://img.shields.io/badge/Coverage-99%25-brightgreen)
![Tests](https://img.shields.io/badge/Tests-89%20passing-brightgreen?logo=pest)
![Status](https://img.shields.io/badge/Status-Livré-brightgreen)

---

## 🎯 Concept

Les applications de sport classiques (Strava, Garmin Connect) offrent des statistiques globales, mais répondent mal aux questions vraiment utiles pour progresser :

- *Quelle est ma vitesse ascensionnelle sur les pentes à plus de 25% ?*
- *Comment évolue mon endurance sur les longues sorties en montagne ces six derniers mois ?*
- *Suis-je plus rapide en trail qu'en randonnée sur les dénivelés modérés ?*

Summit Stats segmente chaque trace GPX par type de terrain et classe de pente, puis vous laisse composer vos propres métriques de progression via une interface de filtrage dynamique.

---

## 📊 Chiffres clés

| Métrique | Valeur |
|---|---|
| Couverture de tests | **99%** |
| Tests automatisés | **89 tests, 271+ assertions** |
| Endpoints API | **6 routes REST** |
| Métriques calculées par activité | **22 stats stockées en base** |
| Pipeline GPX | **4 services en TDD strict** |
| Lignes de code PHP | ~2 000 (hors migrations et config) |

---

## ✨ Fonctionnalités

- **Import GPX** — upload avec drag & drop, analyse automatique à l'import
- **Pipeline d'analyse GPX** — segmentation par type (montée / descente / plat) et classe de pente, formule de Haversine, lissage du dénivelé par moyenne glissante
- **22 statistiques par activité** — vitesse totale et en mouvement (pauses > 30s exclues), vitesse ascensionnelle moyenne / → sommet / long tronçon non descendant, vitesse descensionnelle, répartition % par classe de pente en montée et en descente
- **Recalcul à la demande** — bouton par activité + commande `php artisan stats:recalculate`
- **Dashboard de progression** — graphes temporels recalculés à la volée par métrique, type, milieu, période et plage de pente
- **Filtres avancés** — combinables : type d'activité, milieu, période, activité spécifique, plage de pente (de / à)
- **Historique des sorties** — liste paginée avec filtres et stats résumées

---

## 🗂️ Classes de pente

Summit Stats segmente automatiquement chaque trace selon cinq classes de pente :

| Classe | Label    | Plage      |
|--------|----------|------------|
| `lt5`  | Plat     | < 5 %      |
| `5_15` | Modéré   | 5 % – 15 % |
| `15_25`| Pentu    | 15 % – 25 %|
| `25_35`| Raide    | 25 % – 35 %|
| `gt35` | Extrême  | > 35 %     |

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 12 |
| Base de données | SQLite |
| Auth | Laravel Sanctum (token mono-user) |
| Frontend | Vue.js 3 + Vite |
| Graphes | Chart.js + chartjs-adapter-date-fns |
| State | Pinia |
| Tests | Pest — TDD strict, 99% de couverture |
| Linting PHP | Laravel Pint (PSR-12) |
| Linting JS | ESLint + Prettier |
| CI | GitHub Actions |

---

## 🏗️ Architecture
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
        ├── GpxParserService             # Parsing XML → points normalisés
        ├── ElevationCalculatorService   # Distance (Haversine), D+/D-, durée, durée en mouvement
        ├── SegmentationService          # Découpage par type et classe de pente
        ├── StatsAggregatorService       # Agrégation des 22 métriques
        └── GpxAnalysisOrchestrator      # Orchestration du pipeline complet

resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart
├── helpers/       # Formatage (distance, durée, vitesse, date)
├── stores/        # Pinia (activities, filters)
└── router/        # Vue Router avec garde d'authentification
```

---

## 🧪 Tests
```bash
# Lancer tous les tests
php artisan test

# Avec rapport de couverture (nécessite pcov)
php artisan test --coverage

# Avec seuil minimum
php artisan test --coverage --min=80
```

### Approche TDD

- **TDD strict** sur les 4 services GPX — tests écrits avant le code
- **Feature tests** sur les 6 endpoints API + commande Artisan
- **Tests unitaires** sur les modèles et le trait `ApiResponse`
- **Test de régression** sur une vraie trace GPX (11.8 km, ~470 m D+)
- Base de test SQLite en mémoire (`:memory:`) — isolation totale, suite complète en < 1s

---

## 📡 API — Endpoints principaux

Toutes les routes sont protégées par `Authorization: Bearer {token}`.

| Méthode | Route | Description |
|---|---|---|
| `POST` | `/api/activities` | Import GPX + analyse automatique |
| `GET` | `/api/activities` | Liste paginée (filtres disponibles) |
| `GET` | `/api/activities/{id}` | Détail + segments |
| `PUT` | `/api/activities/{id}` | Mise à jour des métadonnées |
| `DELETE` | `/api/activities/{id}` | Suppression activité + fichier GPX |
| `POST` | `/api/activities/{id}/recalculate` | Recalcul des stats depuis le GPX brut |
| `GET` | `/api/stats` | Données de progression pour graphes |

### Exemple — `/api/stats`
```
GET /api/stats
  ?metric=avg_ascent_speed_mh   # vitesse ascensionnelle
  &type=trail                   # filtre type d'activité
  &slope_min=15                 # pente ≥ 15%
  &slope_max=35                 # pente ≤ 35% (optionnel)
  &date_from=2024-01-01
```
```json
{
  "data": [
    { "date": "2024-03-15", "value": 423.5, "activity_title": "Aiguilles Rouges" },
    { "date": "2024-04-02", "value": 512.0, "activity_title": "Col de Balme" }
  ],
  "meta": {
    "metric": "avg_ascent_speed_mh",
    "unit": "m/h",
    "count": 2
  }
}
```

---

## 🚀 Installation

### Prérequis

- PHP >= 8.3 avec extensions `pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `pcov`
- Composer >= 2.x
- Node.js >= 18

### Mise en route
```bash
# Cloner le projet
git clone https://github.com/MarvinLeRouge/Summit-Stats.git
cd Summit-Stats

# Dépendances PHP
composer install

# Dépendances Node
npm install

# Configuration
cp .env.example .env
# Renseigner APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD dans .env
php artisan key:generate

# ⚠️ Créer manuellement .env.testing avec DB_DATABASE=:memory:
# Ce fichier n'est pas committé pour des raisons de sécurité

# Base de données
touch database/database.sqlite
php artisan migrate

# Créer l'utilisateur et générer le token d'accès
php artisan db:seed --class=UserSeeder
# → Le token est affiché dans la console. Copiez-le.

# Lancer les serveurs
php artisan serve    # Backend → http://localhost:8000
npm run dev          # Vite HMR → http://localhost:5173
```

Ouvrez `http://localhost:8000`, saisissez le token généré par le seeder, c'est parti.

---

## 🗺️ Roadmap

- [x] Cadrage du projet et architecture
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** — Modèle de données (migrations, modèles Eloquent)
- [x] **P3** — Algo GPX (parsing, segmentation, calcul des stats) — *TDD strict*
- [x] **P4** — API REST (6 endpoints + feature tests)
- [x] **P5** — Frontend Vue.js (dashboard, graphes, filtres dynamiques)
- [x] **P6** — Qualité (99% de couverture, PHPDoc, Pint, ESLint)
- [x] **P7** — DevOps (CI GitHub Actions, documentation)

---

## 🤝 Contexte

Projet personnel à double vocation :

**Autoformation** — mise en pratique de :
- Architecture en couches services (TDD, responsabilité unique, injection de dépendances)
- Laravel 12 — Eloquent, Form Requests, Sanctum, Artisan commands
- Algorithmes géospatiaux — Haversine, lissage par moyenne glissante, segmentation de traces GPS
- Vue.js 3 — Composition API, Pinia, Vue Router, Chart.js
- Qualité logicielle — 99% de couverture, PHPDoc, PSR-12, ESLint

**Portfolio** — démonstration d'une approche de développement structurée, testée et documentée, du cadrage jusqu'à la CI.

---

## 📄 Licence

MIT