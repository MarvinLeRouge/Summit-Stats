[🇫🇷 Version française](CONTRIBUTING.fr.md) | 🇬🇧 English version

---

# Contributing to Summit Stats

This is primarily a personal portfolio project. External contributions are welcome but limited in scope.

## Prerequisites

- PHP 8.2+ with Composer
- Node.js 20+
- SQLite (bundled with PHP)

## Local setup

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

## Running tests

```bash
php artisan test --coverage --min=80   # tests + coverage
```

## Workflow

1. Fork the repository and create a branch off `main`.
2. Make your change, with tests covering it.
3. Commit following the convention below.
4. Push and open a pull request against `main`.
5. CI must pass before review.

## Branch naming

| Type | Pattern |
|---|---|
| Feature | `feat/short-description` |
| Bug fix | `fix/short-description` |
| Refactor | `refactor/short-description` |
| Tests | `test/short-description` |
| Documentation | `docs/short-description` |
| Chore | `chore/short-description` |

Use lowercase kebab-case. No special characters.

## Commit convention

Follow [Conventional Commits](https://www.conventionalcommits.org/), imperative mood, lowercase summary, no trailing period, with a mandatory `Modified files:` section:

```
<type>(<optional scope>): <short summary>

Modified files:
- path/to/file-a.ext - what was changed
- path/to/file-b.ext - what was changed
```

Types: `feat`, `fix`, `chore`, `docs`, `refactor`, `test`, `style`, `perf`, `ci`.

## Code style

```bash
vendor/bin/pint --test   # PHP style (PSR-12)
npm run lint             # ESLint
npm run format           # Prettier
```

CI will reject any pull request that fails these checks. PHPDoc is required on all public PHP methods, JSDoc on Vue components and exported JS functions.

## Code of Conduct

This project follows a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold it.

## License

By contributing, you agree that your contributions will be licensed under the project's license (see [LICENSE](LICENSE)).

---

## Pull requests

- One feature or fix per PR
- All new code must have tests
