# 🏔️ Summit Stats

> **⚠️ Projet en cours de développement — Work in progress**

Outil de visualisation de progression pour les activités outdoor (randonnée, trail running).  
Importez vos traces GPX, laissez Summit Stats les analyser, et visualisez votre progression sur les métriques qui comptent vraiment.

![CI](https://github.com/marvinlerouge/summit-stats/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)
![Tests](https://img.shields.io/badge/Tests-Pest-F59E0B)
![Status](https://img.shields.io/badge/Status-En%20développement-orange)

---

## 🎯 Concept

Les applications de sport classiques (Strava, Garmin Connect) offrent des statistiques globales, mais répondent mal aux questions vraiment utiles pour progresser :

- *Quelle est ma vitesse ascensionnelle sur les pentes à plus de 25% ?*
- *Comment évolue mon endurance sur les longues sorties en montagne ces six derniers mois ?*
- *Suis-je plus rapide en trail qu'en randonnée sur les dénivelés modérés ?*

Summit Stats segmente chaque trace GPX par type de terrain et classe de pente, puis vous laisse composer vos propres métriques de progression via une interface de filtrage dynamique.

---

## ✨ Fonctionnalités

### Disponibles
> *(à compléter au fil des livraisons)*

### Prévues

- **Import GPX** — upload de fichiers GPX après chaque sortie
- **Analyse automatique** — segmentation de la trace en sections homogènes (montée / descente / plat) par classe de pente
- **Statistiques par segment** — vitesse, vitesse ascensionnelle, dénivelé, durée par type de terrain
- **Dashboard de progression** — graphes recalculés à la volée selon vos critères
- **Filtres avancés** — par type d'activité, milieu (urbain / campagne / montagne), période, et intervalle de pente (ouvert ou fermé)
- **Historique des sorties** — liste complète avec stats résumées et profil altimétrique

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

| Couche      | Technologie                          |
|-------------|--------------------------------------|
| Backend     | Laravel 12                           |
| Base de données | SQLite                           |
| Auth        | Laravel Sanctum (token mono-user)    |
| Frontend    | Vue.js 3 + Vite                      |
| Graphes     | Chart.js                             |
| State       | Pinia                                |
| Tests       | Pest (TDD services + feature tests API) |
| CI          | GitHub Actions                       |

---

## 🏗️ Architecture

```
app/
├── Http/
│   ├── Controllers/Api/     # Controllers API REST
│   └── Requests/            # Form Requests (validation)
├── Models/                  # Activity, Segment
└── Services/
    └── Gpx/                 # Pipeline d'analyse GPX
        ├── GpxParserService              # Parsing XML → points normalisés
        ├── ElevationCalculatorService    # Distance (Haversine), D+/D-, durée
        ├── SegmentationService           # Découpage par classe de pente
        ├── StatsAggregatorService        # Agrégation des stats
        └── GpxAnalysisOrchestrator       # Orchestration du pipeline complet

resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # BaseChart, GpxUploadForm, ...
├── stores/        # Pinia (activities, filters)
└── router/        # Vue Router
```

---

## 🚀 Installation

### Prérequis

- PHP >= 8.2 avec extensions `pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`
- Composer >= 2.x
- Node.js >= 18

### Mise en route

```bash
# Cloner le projet
git clone https://github.com/marvinlerouge/summit-stats.git
cd summit-stats

# Dépendances PHP
composer install

# Dépendances Node
npm install

# Configuration
cp .env.example .env
php artisan key:generate
> ⚠️ Créer manuellement un fichier `.env.testing` basé sur `.env.example` avec `DB_DATABASE=:memory:` — ce fichier n'est pas committé pour des raisons de sécurité.

# Base de données
touch database/database.sqlite
php artisan migrate

# Créer l'utilisateur et générer le token d'accès
php artisan db:seed --class=UserSeeder
# → Le token est affiché dans la console. Copiez-le.

# Lancer les serveurs
php artisan serve       # Backend sur http://localhost:8000
npm run dev             # Frontend Vite sur http://localhost:5173
```

Ouvrez `http://localhost:5173`, saisissez le token généré par le seeder, c'est parti.

---

## 🧪 Tests

```bash
# Lancer tous les tests
php artisan test

# Avec rapport de couverture
php artisan test --coverage

# Avec seuil minimum (80%)
php artisan test --coverage --min=80
```

### Approche

- **TDD strict** sur les services d'analyse GPX (`app/Services/Gpx/`)
- **Feature tests** sur tous les endpoints API
- Base de test SQLite en mémoire (`:memory:`) — rapide et isolée

---

## 📡 API — Endpoints principaux

Toutes les routes sont protégées par `Authorization: Bearer {token}`.

| Méthode  | Route                   | Description                          |
|----------|-------------------------|--------------------------------------|
| `POST`   | `/api/activities`       | Import GPX + analyse automatique     |
| `GET`    | `/api/activities`       | Liste paginée (filtres disponibles)  |
| `GET`    | `/api/activities/{id}`  | Détail + segments                    |
| `PUT`    | `/api/activities/{id}`  | Mise à jour des métadonnées          |
| `DELETE` | `/api/activities/{id}`  | Suppression activité + fichier GPX   |
| `GET`    | `/api/stats`            | Données de progression pour graphes  |

### Exemple — `/api/stats`

```
GET /api/stats
  ?metric=avg_ascent_speed_mh   # vitesse ascensionnelle
  &type=trail                   # filtre activité
  &slope_min=15                 # pente ≥ 15% (intervalle ouvert)
  &slope_max=35                 # pente ≤ 35% (intervalle fermé, optionnel)
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

## 🗺️ Roadmap

- [x] Cadrage du projet et architecture
- [x] **P1** — Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [ ] **P2** — Modèle de données (migrations, modèles Eloquent)
- [ ] **P3** — Algo GPX (parsing, segmentation, calcul des stats) — *TDD*
- [ ] **P4** — API REST (endpoints + feature tests)
- [ ] **P5** — Frontend Vue.js (dashboard, graphes, filtres dynamiques)
- [ ] **P6** — Qualité (couverture, PHPDoc, linting)
- [ ] **P7** — DevOps (CI GitHub Actions, documentation)

---

## 🤝 Contexte

Projet personnel à double vocation :
- **Autoformation** — mise en pratique de Laravel 12, TDD avec Pest, architecture en couches services, Vue.js 3 + Pinia
- **Portfolio** — démonstration d'une approche de développement structurée, testée et documentée

---

## 📄 Licence

MIT
