🇫🇷 Version française | [🇬🇧 English version](architecture.md)

# Summit Stats - Architecture

[← Retour au README](../README.fr.md)

---

## Structure du dépôt

```
summit-stats/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # ActivityController, LoginController, StatsController
│   │   ├── Requests/            # Form Requests (validation des entrées)
│   │   └── Traits/              # ApiResponse - réponses JSON standardisées
│   ├── Models/                  # Activity, Segment, TrackPoint
│   └── Services/
│       ├── ActivityService.php  # Persistance : store, update, recalculate, destroy
│       ├── Geo/                 # GeoCalculatorService - calculs Haversine partagés
│       └── Gpx/                 # Pipeline d'analyse GPX (TDD strict)
├── resources/js/
│   ├── pages/         # Dashboard, Activities, ActivityDetail, Login
│   ├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart, carte/profil altimétrique
│   ├── helpers/       # Formatage (distance, durée, vitesse, date)
│   ├── stores/        # Store Pinia (activities)
│   └── router/        # Vue Router avec garde d'authentification
├── routes/
│   └── api.php         # Routes REST, toutes derrière Sanctum sauf /login
├── config/
│   ├── slope_thresholds.php  # 5 classes de pente : lt5, 5_15, 15_25, 25_35, gt35
│   └── geo.php                # Rayon terrestre, seuil de pause, réglages OpenTopoData
└── database/
    ├── migrations/
    ├── factories/
    └── seeders/
```

---

## Backend - architecture en services par couches

Les contrôleurs sont légers ; toute la logique métier vit dans les services.

### Pipeline d'analyse GPX (`app/Services/Gpx/`)

TDD strict, un service par étape du pipeline, orchestrés de bout en bout :

1. `GpxParserService` - parsing XML vers un tableau de points normalisés (lat/lon/ele/time)
2. `ElevationEnrichmentService` - enrichissement altimétrique optionnel via l'API OpenTopoData, avec callback de progression SSE
3. `ElevationCalculatorService` - distance Haversine, dénivelé positif/négatif (D+/D-), lissage, durée en mouvement
4. `SegmentationService` - découpe la trace en segments typés (montée/plat/descente) par classe de pente
5. `StatsAggregatorService` - agrège 22 métriques à partir de la trace segmentée
6. `GpxAnalysisOrchestrator` - coordonne le pipeline complet

`GeoCalculatorService` (`app/Services/Geo/`) porte les calculs Haversine partagés dans le pipeline. `ActivityService` (`app/Services/`) orchestre la persistance : stockage du fichier, création du modèle, recalcul, suppression en cascade.

---

## Frontend - Vue 3 Composition API

```
resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # Graphiques (Chart.js), carte (Leaflet/vue-leaflet), formulaire d'upload, nav
├── helpers/       # Utilitaires de formatage
├── stores/        # Store Pinia (activités + état d'authentification)
└── router/        # Vue Router, redirige vers /login en l'absence de token Bearer
```

Axios est configuré dans `bootstrap.js` avec un intercepteur de token Bearer. La progression de l'upload est suivie via Server-Sent Events.

---

## Base de données

SQLite (fichier en dev, `:memory:` en tests). Trois tables principales :

- `activities` - métadonnées + les 22 statistiques agrégées
- `segments` - une ligne par section montée/plat/descente, référençant les indices des points de trace
- `track_points` - données GPS brutes (lat/lon/ele/time/distance/order), utilisées pour la carte et le profil altimétrique

---

## API

Routes REST sous `/api`, protégées par un token Bearer Laravel Sanctum (sauf `POST /login`). Les réponses utilisent le trait `ApiResponse` pour une structure JSON cohérente. La route de création d'activité diffuse la progression de l'upload via SSE. Voir [docs/api/](api/) pour la référence des endpoints.
