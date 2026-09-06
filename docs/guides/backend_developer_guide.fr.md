🇫🇷 Version française | [🇬🇧 English version](backend_developer_guide.md)

---

# Guide développeur backend

Ce guide couvre les conventions de développement backend au quotidien. Voir
la [section architecture du README](../../README.md#architecture) pour la
carte des modules, et
[docs/api/api_endpoints.md](../api/api_endpoints.md) pour la référence
complète des routes.

## Démarrage

```bash
composer setup          # Installe les deps, migre, build les assets
composer dev            # Serveur Laravel + worker de queue + logs + Vite HMR
```

Voir [docs/operations.md](../operations.md) pour l'alternative Docker Compose.

## Architecture en couches

Les contrôleurs sont volontairement minces : toute la logique métier vit
dans les services.

```
app/
├── Http/
│   ├── Controllers/Api/     # Contrôleurs minces - délèguent toute la logique aux services
│   ├── Requests/            # Form Requests (validation d'entrée)
│   └── Traits/              # ApiResponse - réponses JSON standardisées
├── Models/                  # Activity, Segment
└── Services/
    ├── ActivityService.php  # Persistance : store, update, recalculate, destroy
    └── Gpx/                 # Pipeline d'analyse GPX (TDD strict)
```

Les données circulent dans un seul sens :
`route -> controller -> Form Request -> service -> model`. Un contrôleur ne
contient jamais de logique métier ; il valide via un Form Request, appelle
un service, et retourne via le trait `ApiResponse`.

## Le pipeline d'analyse GPX

`GpxAnalysisOrchestrator` (`app/Services/Gpx/`) coordonne cinq étapes,
exécutées dans cet ordre :

1. `GpxParserService` - XML -> points de trace normalisés (lat/lon/ele/time)
2. `ElevationEnrichmentService` - enrichissement altitude optionnel via
   l'API OpenTopoData, avec un callback de progression SSE
3. `ElevationCalculatorService` - distances Haversine, dénivelé
   positif/négatif, lissage, durée en mouvement
4. `SegmentationService` - découpe la trace en segments typés
   (montée/plat/descente) avec une classe de pente
5. `StatsAggregatorService` - agrège les 22 métriques stockées à partir des
   segments

`GeoCalculatorService` (`app/Services/Geo/`) contient les calculs Haversine
partagés utilisés dans tout le pipeline ; ne pas dupliquer les calculs de
distance/cap ailleurs.

## Ajouter une étape au pipeline ou une nouvelle métrique

1. Pour une nouvelle métrique stockée, ajouter la colonne via une migration
   sur `activities` (ou `segments` si c'est par segment), et étendre
   `StatsAggregatorService` pour la calculer.
2. Pour un changement de logique de segmentation (ex. une nouvelle classe de
   pente), mettre à jour `config/slope_thresholds.php` plutôt que de coder
   en dur des seuils dans `SegmentationService`.
3. Ajouter un test unitaire Pest pour le service sous
   `tests/Unit/Services/Gpx/`, en suivant le TDD strict : écrire le test
   avant l'implémentation.
4. Lancer `php artisan stats:recalculate` en local pour vérifier que les
   activités existantes se recalculent correctement avec le changement.

## Ajouter un endpoint API

1. Ajouter la route sous `/api` dans `routes/api.php`, protégée par un
   token Bearer Sanctum (sauf `/api/login`).
2. Ajouter un Form Request pour la validation d'entrée si l'endpoint
   prend un payload.
3. Ajouter une méthode de contrôleur mince dans `Http/Controllers/Api/` qui
   valide, appelle le service concerné, et retourne via le trait
   `ApiResponse` pour une forme JSON cohérente.
4. Ajouter la route à
   [docs/api/api_endpoints.md](../api/api_endpoints.md) (et son miroir
   anglais).
5. Ajouter un test Feature sous `tests/Feature/Api/` couvrant l'endpoint.

## Conventions de test

```bash
php artisan test                            # Lance tous les tests Pest
php artisan test --filter=GpxParserService  # Lance un seul fichier/classe de test
php artisan test --coverage --min=80        # Avec couverture (nécessite pcov)
```

- Les tests Feature dans `tests/Feature/Api/` couvrent tous les endpoints
  API de bout en bout.
- Les tests unitaires dans `tests/Unit/Services/Gpx/` couvrent chaque
  étape du pipeline en isolation.
- Les tests s'exécutent sur SQLite en mémoire (`:memory:`), avec
  `Storage::fake('local')` et `Sanctum::actingAs($user)` pour les requêtes
  authentifiées.
- Les fichiers GPX de test vivent dans `tests/Fixtures/gpx/` ; y ajouter les
  nouvelles fixtures plutôt que d'insérer du XML GPX directement dans les
  fichiers de test.
- La couverture est vérifiée en CI avec `--min=80`.

## Conventions

- Le style PHP suit PSR-12, imposé par Pint (`vendor/bin/pint`) ; lancer
  `vendor/bin/pint --test` avant de commit.
- Les services ont une seule responsabilité : une étape de pipeline, une
  classe.
- `.env`, `.env.testing`, et tout fichier contenant des secrets ne doivent
  jamais être commités.
- Les messages de commit suivent Conventional Commits ; voir le `CLAUDE.md`
  du dépôt.
