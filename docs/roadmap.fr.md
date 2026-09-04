🇫🇷 Version française | [🇬🇧 English version](roadmap.md)

---

# Roadmap

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

### 🔜 V4 - En cours

Plan complet : `docs/work-in-progress/v4-action-plan.md` (fichier local, non versionné).

**Phase 1 - Durcissement sécurité (cible v3.1.0)**
- [x] Authentification par mot de passe - formulaire de login (Sanctum via identifiants)
- [ ] Limitation de débit sur le login, politique d'expiration des tokens Sanctum
- [ ] Configuration CORS explicite et security headers (CSP, HSTS, X-Frame-Options, ...)
- [ ] Sauvegarde automatique de la base - `pg_dump` planifié sur le VPS, rotation, documentation du restore
- [ ] Audit OWASP Top 10:2025 + ASVS
- [ ] Analyse statique (PHPStan/Larastan) dans le hook pre-commit et en CI
- [ ] Audit et optimisation du pipeline CI

**Phase 2 - Partage en lecture seule (cible v4.0.0)**
- [ ] Comptes lecteurs en lecture seule avec contrôle de visibilité par activité
- [ ] Flux d'invitation et d'activation par email (Brevo)
- [ ] Compte démo public en lecture seule

**Phase 3 - Déploiement portfolio (cible v4.1.0)**
- [ ] Sous-domaine public en ligne derrière Traefik, parité dev/prod maintenue
- [ ] Export des activités - téléchargement CSV ou JSON depuis l'interface

**Phase 4 - Refonte design (cible v4.2.0)**
- [ ] Refonte complète de l'identité visuelle
- [ ] Dark mode
- [ ] Audit accessibilité
