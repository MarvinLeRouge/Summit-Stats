[🇫🇷 Version française](CONTRIBUTING.fr.md) | 🇬🇧 English version

---

# Contributing

Thank you for your interest in Summit Stats.
This is primarily a personal portfolio project. External contributions are welcome but limited in scope.

## Getting started

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

## Branch naming

| Type | Pattern |
|---|---|
| Feature | `feat/short-description` |
| Bug fix | `fix/short-description` |
| Refactor | `refactor/short-description` |
| Tests | `test/short-description` |
| Docs | `docs/short-description` |
| Chore | `chore/short-description` |

## Commit messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): short summary in imperative mood

Modified files:
- path/to/file.ext — what was changed
```

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `style`, `perf`, `ci`, `chore`.

## Code quality

Before submitting a pull request, make sure all checks pass:

```bash
php artisan test --coverage --min=80   # Tests + coverage
vendor/bin/pint --test                 # PHP style (PSR-12)
npm run lint                           # ESLint
npm run format                         # Prettier
```

## Pull requests

- One feature or fix per PR
- All new code must have tests
- PHPDoc required on all public PHP methods
- JSDoc required on Vue components and exported JS functions
