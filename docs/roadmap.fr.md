🇫🇷 Version française | [🇬🇧 English version](roadmap.md)

---

# Roadmap

### ✅ V1 - Livrée

- [x] Cadrage et architecture projet
- [x] **P1** : Setup (Laravel 12, Pest, Sanctum, Vue.js 3)
- [x] **P2** : Modèle de données (migrations, Eloquent)
- [x] **P3** : Algo GPX (parsing, segmentation, stats), *strict TDD*
- [x] **P4** : REST API (7 endpoints + feature tests)
- [x] **P5** : Frontend Vue.js (dashboard, charts, dynamic filters)
- [x] **P6** : Qualité (99% coverage, PHPDoc, Pint, ESLint)
- [x] **P7** : DevOps (GitHub Actions CI, documentation)

### ✅ V2 - Livrée

- [x] **P1** : Stockage des points GPS + endpoint `/api/activities/{id}/track`
- [x] **P2** : Profil altimétrique (Chart.js + zoom)
- [x] **P3** : Carte OSM avec tracé GPX (Leaflet)
- [x] **P4** : Qualité (tests, couverture, linting)
- [x] **P5** : DevOps (mise à jour CI, documentation V2)

### ✅ V3 - Livrée

- [x] Suite de tests E2E (Playwright, 32 scénarios, workflow CI dédié)
- [x] Stack Docker Compose (Nginx, PHP-FPM, PostgreSQL, Redis, worker de file)
- [x] Couverture frontend sur Codecov (badges par flag)
- [x] Déploiement en production : Traefik, HTTPS, GHCR, pipeline CD par SSH
- [x] Hooks pre-commit : Husky + lint-staged (auto-fix PHP et JS/Vue)
- [x] Pipeline CI/CD optimisé : lint en premier, déploiement conditionné par l'E2E, workflow chaining

### 🔜 V4 - En cours

Plan complet : `docs/work-in-progress/v4-action-plan.md` (fichier local, non versionné).

**Phase 1 - Durcissement sécurité (cible v3.1.0)**
- [x] Authentification par mot de passe - formulaire de login (Sanctum via identifiants)
- [x] Limitation de débit sur le login, politique d'expiration des tokens Sanctum
- [x] Configuration CORS explicite et security headers (CSP, HSTS, X-Frame-Options, ...)
- [ ] Sauvegarde automatique de la base - `pg_dump` planifié sur le VPS, rotation, documentation du restore
- [x] Audit OWASP Top 10:2025 + ASVS
- [x] Analyse statique (PHPStan/Larastan) dans le hook pre-commit et en CI
- [x] Audit et optimisation du pipeline CI

**Phase 2 - Partage en lecture seule (cible v4.0.0)**
- [ ] Comptes lecteurs en lecture seule avec contrôle de visibilité par activité
- [ ] Flux d'invitation et d'activation par email (Brevo)
- [ ] Compte démo public en lecture seule

**Phase 3 - Déploiement portfolio (cible v4.1.0)**
- [ ] Sous-domaine portfolio public dédié (comptes démo et lecteurs) derrière Traefik, distinct de l'instance de production privée actuelle, parité dev/prod revérifiée
- [ ] Export des activités - téléchargement CSV ou JSON depuis l'interface

**Phase 4 - Refonte design (cible v4.2.0)**
- [ ] Refonte complète de l'identité visuelle
- [ ] Dark mode
- [ ] Audit accessibilité

## Section 5 - Maintenance

- [x] FIX-001 - Corriger les failles de dépendances qui bloquent les contrôles de sécurité de la CI (shell-quote, vite, undici, follow-redirects, vitest, symfony, laravel)
- [x] CI-001 - Lancer la CI sur la PR du bot changelog pour que son auto-merge aboutisse
- [x] TEST-001 - Combler les trous de coverage par fichier : suppression de la commande ponctuelle `MigrateFromSqlite` (données déjà migrées)
- [x] DOCS-001 - Décrire PostgreSQL comme base de développement (README, CONTRIBUTING, architecture, mise à jour de l'ADR 0004)
- [x] FIX-002 - Épingler l'extension pecl redis et mettre en cache le build Docker de l'E2E (une panne passagère de pecl.php.net a cassé l'E2E du merge)
- [x] FIX-003 - Mettre DB_PORT=5433 dans .env.example pour correspondre au port host du conteneur postgres de dev
- [x] FIX-004 - Lancer npm ci dans le conteneur vite de dev pour qu'il ne réécrive plus package-lock.json
- [x] FIX-005 - Garder le test du health check fonctionnel avec Laravel 12.73 (mock DB partiel), monter le groupe Laravel mineur et patch en gardant Symfony en 7.4
- [x] CHORE-001 - Passer à Node 26 LTS : `node:20-alpine` dans le Dockerfile et le service vite de dev, CI en 22, ajout de `engines` (montées des actions traitées avec la tâche cross-project des actions)
- [ ] DOCS-002 - Mettre à jour la section CI du README : elle décrit un enchaînement lint puis tests, alors que `ci.yml` lance des jobs parallèles filtrés par chemin derrière l'agrégateur `ci-status`
- [x] CHORE-002 - Charger la config prod depuis shared/env (app.env + secrets.env) au lieu de compose/.env.prod, une seule copie du mot de passe de la base
- [x] CI-002 - Passer chaque GitHub Action sur sa majeure actuelle épinglée par SHA de commit, workflow changelog sur Node 26, entrées Dependabot docker et github-actions
- [x] CI-003 - Renommer le workflow de déploiement en build-push-deploy.yml, deux jobs explicites (Build & push, Deploy), sans changement fonctionnel
- [x] CHORE-003 - Mettre à jour eslint et @eslint/js en 10
- [x] CHORE-004 - Passer en PHP 8.5 (image Docker, CI, contrainte composer, docs)

## Section 6 - Observabilité

- [x] FEAT-001 - Écrire les journaux de production en JSON (un objet par ligne sur stderr), messages de log constants avec contexte
- [x] FEAT-002 - Remonter à Sentry les erreurs Laravel et frontend non gérées, événements frontend relayés par le backend
- [x] FEAT-003 - Endpoint de santé `/health` qui vérifie la base de données et Redis
