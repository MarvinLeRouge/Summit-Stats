# Contributing

🇫🇷 [Version française](#version-française)

---

## 🇬🇧 English version

Thank you for your interest in Summit Stats.
This is primarily a personal portfolio project. External contributions are welcome but limited in scope.

### Getting started

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git
cd Summit-Stats
composer setup
cp .env.example .env
# Fill in APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD, DB_DATABASE
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=UserSeeder
```

Create `.env.testing` with `DB_DATABASE=:memory:` for isolated test runs.

### Branch naming

| Type | Pattern |
|---|---|
| Feature | `feat/short-description` |
| Bug fix | `fix/short-description` |
| Refactor | `refactor/short-description` |
| Tests | `test/short-description` |
| Docs | `docs/short-description` |
| Chore | `chore/short-description` |

### Commit messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): short summary in imperative mood

Modified files:
- path/to/file.ext — what was changed
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `style`, `perf`, `ci`, `chore`.

### Code quality

Before submitting a pull request, make sure all checks pass:

```bash
php artisan test --coverage --min=80   # Tests + coverage
vendor/bin/pint --test                 # PHP style (PSR-12)
npm run lint                           # ESLint
npm run format                         # Prettier
```

### Pull requests

- One feature or fix per PR
- All new code must have tests
- PHPDoc required on all public PHP methods
- JSDoc required on Vue components and exported JS functions

---

## 🇫🇷 Version française

Merci de l'intérêt pour Summit Stats.
Ce projet est avant tout un projet portfolio personnel. Les contributions externes sont bienvenues mais limitées en périmètre.

### Démarrage

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git
cd Summit-Stats
composer setup
cp .env.example .env
# Renseigner APP_USER_NAME, APP_USER_EMAIL, APP_USER_PASSWORD, DB_DATABASE
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=UserSeeder
```

Créer `.env.testing` avec `DB_DATABASE=:memory:` pour des tests isolés.

### Nommage des branches

| Type | Format |
|---|---|
| Fonctionnalité | `feat/description-courte` |
| Correction | `fix/description-courte` |
| Refactoring | `refactor/description-courte` |
| Tests | `test/description-courte` |
| Documentation | `docs/description-courte` |
| Maintenance | `chore/description-courte` |

### Messages de commit

Suivre [Conventional Commits](https://www.conventionalcommits.org/) :

```
type(scope): résumé court à l'impératif

Modified files:
- chemin/vers/fichier.ext — ce qui a été modifié
```

Types : `feat`, `fix`, `refactor`, `test`, `docs`, `style`, `perf`, `ci`, `chore`.

### Qualité du code

Avant de soumettre une pull request, s'assurer que tous les checks passent :

```bash
php artisan test --coverage --min=80   # Tests + couverture
vendor/bin/pint --test                 # Style PHP (PSR-12)
npm run lint                           # ESLint
npm run format                         # Prettier
```

### Pull requests

- Une fonctionnalité ou correction par PR
- Tout nouveau code doit être testé
- PHPDoc obligatoire sur toutes les méthodes PHP publiques
- JSDoc obligatoire sur les composants Vue et les fonctions JS exportées
