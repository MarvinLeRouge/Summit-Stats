🇫🇷 Version française | [🇬🇧 English version](frontend_developer_guide.md)

---

# Guide développeur frontend

Ce guide couvre les conventions de développement frontend au quotidien. Voir
[docs/design-system.md](../design-system.fr.md) pour les conventions de
style, de graphiques et de carte actuellement en place.

## Démarrage

```bash
composer setup       # Setup complet (deps + migration + build) si pas déjà fait
npm run dev          # Serveur de dev Vite (port 5173, HMR)
```

`composer dev` lance le serveur Laravel, le worker de queue, les logs et le
HMR Vite en parallèle. Voir [docs/operations.md](../operations.fr.md) pour
l'alternative Docker Compose.

## Structure

```
resources/js/
├── pages/         # Dashboard, Activities, ActivityDetail, Login
├── components/    # NavBar, GpxUploadForm, StatCard, PctBar, ProgressionChart...
├── helpers/       # Formatage (distance, durée, vitesse, date)
├── stores/        # Store Pinia (activities)
└── router/        # Vue Router avec garde d'authentification
```

Les pages composent des composants et lisent/écrivent via le store Pinia ;
la logique de formatage (distance, durée, vitesse, date) va dans `helpers/`,
pas dupliquée directement dans les composants.

## Ajouter une page

1. Créer le composant de page sous `pages/`.
2. Ajouter ou étendre un store Pinia sous `stores/` si la page a besoin
   d'état partagé ; garder les appels API dans le store, pas dans le
   composant.
3. Enregistrer la route dans `router/` ; la garde de navigation redirige
   déjà vers `/login` en l'absence de token Bearer, inutile de
   réimplémenter cette vérification pour les pages protégées.
4. Ajouter un fichier de test `.spec.js` à côté de la page ou du composant.

## Graphiques et cartes

- Tous les graphiques passent par `BaseChart.vue` : il enregistre Chart.js
  une seule fois et gère le cycle de vie mount/update/destroy du
  `<canvas>`. Un nouveau graphique doit construire son objet
  `type`/`data`/`options` et le passer à `BaseChart`, sans réimplémenter le
  cycle de vie Chart.js.
- `TrackMap.vue` affiche les traces GPS via Leaflet en passant par le
  proxy de tuiles OSM propre à l'application (jamais un appel direct à un
  serveur de tuiles public, voir [docs/operations.md](../operations.fr.md)).
- Le profil d'élévation et la carte restent synchronisés via des
  événements de survol : `ElevationProfile.vue` émet `{ lat, lon }`, et
  `TrackMap.vue` réagit en dessinant un marqueur mis en évidence à cette
  position. Suivre le même patron d'événements pour toute nouvelle
  visualisation synchronisée.

## Appels API et authentification

- Axios est configuré dans `bootstrap.js` avec un intercepteur de token
  Bearer ; passer par cette instance partagée plutôt que de configurer un
  nouveau client HTTP.
- Le flux d'import GPX diffuse sa progression via Server-Sent Events ; pour
  toute autre opération longue avec retour de progression, suivre le même
  patron SSE plutôt que du polling.

## Conventions de style

- Tailwind CSS v4, configuré via `resources/css/app.css` (pas de
  `tailwind.config.js`) ; utiliser la palette et l'échelle par défaut
  directement, pas de tokens de design personnalisés.
- Suivre les conventions déjà observées dans les composants (voir
  [docs/design-system.md](../design-system.fr.md)) : les cartes utilisent
  `bg-white rounded-lg shadow-sm border`, les données de montée utilisent
  des accents verts, les données de descente des accents bleus.

## Conventions de test

```bash
npm run test:coverage    # Vitest, avec rapport de couverture
```

Environnement JSDOM, Vue Test Utils. Faire correspondre la structure des
tests à celle du code source : le fichier de spec d'un composant est à côté
du composant.

## Linting

```bash
npm run lint      # Vérification ESLint (JS/Vue)
npm run format    # Auto-formatage Prettier
```

Husky + lint-staged exécutent ces commandes automatiquement sur les
fichiers stagés au moment du commit.
