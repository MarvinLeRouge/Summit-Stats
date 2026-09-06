🇫🇷 Version française | [🇬🇧 English version](design-system.md)

---

# Design system

## Styles — Tailwind CSS v4

Tailwind est configuré via CSS, pas via un fichier `tailwind.config.js` (plugin `@tailwindcss/vite`, style v4).

`resources/css/app.css` :
- `@import 'tailwindcss';`
- des directives `@source` étendent le scan des classes aux vues de pagination Blade et à `resources/**/*.{blade.php,js}`
- `@theme` surcharge la pile de polices par défaut : `--font-sans: 'Instrument Sans', ui-sans-serif, system-ui, ...`

Aucun autre token de design (couleurs, espacements, arrondis) n'est personnalisé, les composants utilisent directement la palette et l'échelle par défaut de Tailwind.

### Conventions observées dans les composants

- Cartes : `bg-white rounded-lg shadow-sm border`
- Texte discret / labels : `text-gray-400` ou `text-gray-500`, souvent `text-xs font-semibold uppercase`
- Valeurs numériques principales : `text-gray-700` / `text-gray-900`, graisse medium/semibold
- Données liées à la montée : accents verts (ex. `bg-green-300`)
- Données liées à la descente : accents bleus (ex. `bg-blue-300`)
- États interactifs/hover : `transition-all duration-500` sur les barres animées (voir `PctBar.vue`)

## Graphiques — Chart.js

Tous les graphiques passent par un wrapper unique, `resources/js/components/BaseChart.vue` :
- Enregistre `Chart.register(...registerables)` une seule fois, puis instancie un `Chart` sur un `<canvas>` dans `onMounted`
- Détruit l'instance dans `onUnmounted` pour éviter de fuiter des canvas à la navigation
- Pousse réactivement les nouvelles données dans l'instance existante (`chartInstance.data = ...; chartInstance.update()`) via un `watch` profond sur la prop `data`, plutôt que de recréer le graphique

Les graphiques spécifiques (`ProgressionChart.vue`, `ElevationProfile.vue`) construisent leurs objets `type`/`data`/`options` et les transmettent à `BaseChart`, gardant la configuration Chart.js hors du wrapper générique.

## Cartes — Leaflet

`TrackMap.vue` affiche les traces GPS via `@vue-leaflet/vue-leaflet` :
- Les tuiles sont servies via le proxy/cache de tuiles OSM propre à l'application (`/tiles/{z}/{x}/{y}.png`, voir [operations.fr.md](operations.fr.md)) via `tileLayerOffline` de `leaflet.offline`, et non un appel direct à un serveur de tuiles OSM public
- La polyligne et le marqueur de survol utilisent une palette bleue fixe : `#3B82F6` (trace), `#2563EB` (point survolé)
- La carte ajuste automatiquement ses limites à l'étendue lat/lon de la trace au chargement
- `ElevationProfile.vue` émet des événements de survol avec `{ lat, lon }` ; `TrackMap.vue` réagit en dessinant un `l-circle-marker` surligné à cette position, c'est ainsi que le profil altimétrique et la carte restent synchronisés
