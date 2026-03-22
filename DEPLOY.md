# Deployment Guide

🇫🇷 [Version française](#version-française)

---

## 🇬🇧 English version

Self-hosting Summit Stats on a Linux server (nginx + PHP-FPM + SQLite).

### Prerequisites

- PHP >= 8.3 with extensions: `pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `curl`
- Composer >= 2
- Node.js >= 18 + npm (build only — not needed at runtime)
- nginx + PHP-FPM

### 1. Clone and install

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git /var/www/summit-stats
cd /var/www/summit-stats

composer install --no-dev --optimize-autoloader
npm ci && npm run build
rm -rf node_modules
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` for production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example.com

APP_USER_NAME=Your Name
APP_USER_EMAIL=you@example.com
APP_USER_PASSWORD=a-strong-password

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/summit-stats/database/database.sqlite

LOG_CHANNEL=single
LOG_LEVEL=error
```

Then generate the application key:

```bash
php artisan key:generate
```

### 3. Database setup

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=UserSeeder
```

The seeder prints your **Sanctum API token** — copy it immediately. It is shown only once.
Store it securely (e.g. a local password manager or `docs/work-in-progress/Authentication_token.txt`, which is gitignored).

### 4. File permissions

```bash
chown -R www-data:www-data /var/www/summit-stats
chmod -R 755 /var/www/summit-stats
chmod -R 775 /var/www/summit-stats/storage
chmod -R 775 /var/www/summit-stats/bootstrap/cache
```

### 5. nginx configuration

```nginx
server {
    listen 80;
    server_name your-domain.example.com;
    root /var/www/summit-stats/public;
    index index.php;

    # Laravel SPA — all routes go through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Block access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }

    # Increase timeout for SSE upload + elevation enrichment (long-running requests)
    fastcgi_read_timeout 120;
}
```

Reload nginx after editing:

```bash
nginx -t && systemctl reload nginx
```

### 6. Laravel optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Re-run these commands after each deployment.

### Updating

```bash
cd /var/www/summit-stats
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🇫🇷 Version française

Auto-hébergement de Summit Stats sur un serveur Linux (nginx + PHP-FPM + SQLite).

### Prérequis

- PHP >= 8.3 avec les extensions : `pdo_sqlite`, `sqlite3`, `xml`, `mbstring`, `fileinfo`, `curl`
- Composer >= 2
- Node.js >= 18 + npm (build uniquement — pas nécessaire à l'exécution)
- nginx + PHP-FPM

### 1. Cloner et installer

```bash
git clone https://github.com/MarvinLeRouge/Summit-Stats.git /var/www/summit-stats
cd /var/www/summit-stats

composer install --no-dev --optimize-autoloader
npm ci && npm run build
rm -rf node_modules
```

### 2. Configurer l'environnement

```bash
cp .env.example .env
```

Éditer `.env` pour la production :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.exemple.fr

APP_USER_NAME=Votre Nom
APP_USER_EMAIL=vous@exemple.fr
APP_USER_PASSWORD=un-mot-de-passe-fort

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/summit-stats/database/database.sqlite

LOG_CHANNEL=single
LOG_LEVEL=error
```

Puis générer la clé applicative :

```bash
php artisan key:generate
```

### 3. Base de données

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --class=UserSeeder
```

Le seeder affiche le **token API Sanctum** — le copier immédiatement, il n'est affiché qu'une seule fois.
Le stocker en lieu sûr (gestionnaire de mots de passe ou `docs/work-in-progress/Authentication_token.txt`, qui est gitignored).

### 4. Permissions

```bash
chown -R www-data:www-data /var/www/summit-stats
chmod -R 755 /var/www/summit-stats
chmod -R 775 /var/www/summit-stats/storage
chmod -R 775 /var/www/summit-stats/bootstrap/cache
```

### 5. Configuration nginx

```nginx
server {
    listen 80;
    server_name votre-domaine.exemple.fr;
    root /var/www/summit-stats/public;
    index index.php;

    # SPA Laravel — toutes les routes passent par index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Bloquer l'accès aux fichiers sensibles
    location ~ /\.(env|git) {
        deny all;
    }

    # Augmenter le timeout pour le SSE upload + enrichissement altimétrique (requêtes longues)
    fastcgi_read_timeout 120;
}
```

Recharger nginx après modification :

```bash
nginx -t && systemctl reload nginx
```

### 6. Optimisations Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

À relancer après chaque déploiement.

### Mise à jour

```bash
cd /var/www/summit-stats
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
