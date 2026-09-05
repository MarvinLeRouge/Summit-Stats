🇫🇷 Version française | [🇬🇧 English version](CONTRIBUTING.md)

---

# Contribuer à Summit Stats

Il s'agit avant tout d'un projet portfolio personnel. Les contributions externes sont bienvenues mais dans une portée limitée.

## Prérequis

- PHP 8.2+ avec Composer
- Node.js 20+
- SQLite (fourni avec PHP)

## Installation locale

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

## Lancer les tests

```bash
php artisan test --coverage --min=80   # tests + couverture
```

## Workflow

1. Forker le dépôt et créer une branche à partir de `main`.
2. Faire la modification, avec des tests qui la couvrent.
3. Commiter en suivant la convention ci-dessous.
4. Pousser et ouvrir une pull request vers `main`.
5. La CI doit passer avant la revue.

## Nommage des branches

| Type | Format |
|---|---|
| Fonctionnalité | `feat/description-courte` |
| Correction | `fix/description-courte` |
| Refactoring | `refactor/description-courte` |
| Tests | `test/description-courte` |
| Documentation | `docs/description-courte` |
| Maintenance | `chore/description-courte` |

Minuscules, kebab-case, sans caractères spéciaux.

## Convention de commit

Suivre [Conventional Commits](https://www.conventionalcommits.org/), impératif, minuscules, sans point final, avec une section `Modified files:` obligatoire :

```
<type>(<scope optionnel>): <résumé court>

Modified files:
- chemin/vers/fichier-a.ext - ce qui a été modifié
- chemin/vers/fichier-b.ext - ce qui a été modifié
```

Types : `feat`, `fix`, `chore`, `docs`, `refactor`, `test`, `style`, `perf`, `ci`.

## Style de code

```bash
vendor/bin/pint --test   # Style PHP (PSR-12)
npm run lint             # ESLint
npm run format           # Prettier
```

La CI rejettera toute pull request qui ne passe pas ces vérifications. PHPDoc obligatoire sur toutes les méthodes PHP publiques, JSDoc sur les composants Vue et les fonctions JS exportées.

## Code de conduite

Ce projet suit un [Code de conduite](CODE_OF_CONDUCT.fr.md). En participant, vous vous engagez à le respecter.

## Licence

En contribuant, vous acceptez que vos contributions soient distribuées sous la licence du projet (voir [LICENSE](LICENSE)).

---

## Pull requests

- Une fonctionnalité ou correction par PR
- Tout nouveau code doit être testé
