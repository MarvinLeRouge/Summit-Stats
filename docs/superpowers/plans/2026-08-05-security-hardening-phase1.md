# Security Hardening (Phase 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the concrete security gaps identified in the Summit Stats codebase (no rate limiting on login, tokens that never expire, permissive CORS default, no security headers, no XXE hardening on GPX parsing, no logging of failed auth, no static analysis) before any wider/public exposure of the app, and land the supporting quality tooling (PHPStan, CI cleanup) and operational safety net (automated backups) that this phase's roadmap entry (`docs/ai/work-in-progress/v4-action-plan.md`, section 1) commits to.

**Architecture:** No new subsystem is introduced. Each task is a narrow, independently testable change to the existing Laravel 12 app (middleware, config, a service class) or to the surrounding tooling (Husky hook, CI workflow, a backup shell script). Tasks are ordered so that foundational config changes land first, followed by code changes, then tooling, then the operational backup script, closing with a full OWASP pass that reviews everything once it is in place.

**Tech Stack:** Laravel 12, Laravel Sanctum 4, Pest 3.8 (strict TDD), PHP 8.4. PostgreSQL 16 in Docker/production, SQLite `:memory:` in tests. Vue 3 frontend is untouched by this phase except where noted (CSP).

## Global Constraints

- Branch: single branch for the whole phase, `fix/security-hardening-phase1`, created from an up-to-date `main`.
- Commits: Conventional Commits, English, one commit per task (or per logical step inside a task where noted), each listing modified files per `CLAUDE.md`.
- Claude never creates, merges, or comments on a pull request. A title and description are provided at the end for the user to create manually.
- Claude never pushes to `main` (protected) and never connects to the production VPS over SSH. Any step that touches the VPS is handed off as copy-paste instructions for the user (Task 10).
- Backend: Pest tests, TDD (failing test before implementation), coverage must stay at 100% per the project's existing standard (`php artisan test --coverage --min=80` is the CI gate, but the project currently sits at 100%; do not introduce untested branches).
- No `Co-Authored-By: Claude` or any AI-attribution mention anywhere (commits, code comments, docs).
- No em dash (`—`) in commits, PR text, or any file under version control. Use a comma, colon, parentheses, or a plain hyphen instead.
- French is used for existing in-repo Blade/UI strings and PHPDoc bodies already written in French (e.g. `Détermine si...`); match the existing language of the file being edited. New PHPDoc/comments in files that are already French stay French; everything else (commit messages, this plan, new file-level content with no established language) is English.

---

## Task 1: Rate limiting on `POST /api/login`

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/AuthTest.php`

**Interfaces:**
- Consumes: nothing new, uses Laravel's built-in `throttle` middleware alias and `RateLimiter` facade.
- Produces: a named rate limiter `login` that later auth-related routes can reuse the same way.

- [ ] **Step 1: Create the branch**

```bash
git checkout main
git pull origin main
git checkout -b fix/security-hardening-phase1
```

- [ ] **Step 2: Write the failing test**

Add to `tests/Feature/Api/AuthTest.php` (append at the end of the file):

```php
it('throttles login after too many attempts', function () {
    User::factory()->create(['password' => bcrypt('secret123')]);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', ['password' => 'wrong']);
    }

    $this->postJson('/api/login', ['password' => 'wrong'])
        ->assertStatus(429);
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --filter="throttles login after too many attempts"`
Expected: FAIL, response status is 401 instead of 429 (no rate limiting yet).

- [ ] **Step 4: Register the `login` rate limiter**

In `app/Providers/AppServiceProvider.php`, add the imports and register the limiter in `boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
```

```php
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
```

- [ ] **Step 5: Apply the middleware to the login route**

In `routes/api.php`, change:

```php
Route::post('login', [LoginController::class, 'login']);
```

to:

```php
Route::post('login', [LoginController::class, 'login'])->middleware('throttle:login');
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter="throttles login after too many attempts"`
Expected: PASS

- [ ] **Step 7: Run the full AuthTest suite to check for regressions**

Run: `php artisan test tests/Feature/Api/AuthTest.php`
Expected: all tests PASS (the existing "returns 401 on wrong password" test only sends one request, so it stays under the limit).

- [ ] **Step 8: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Api/AuthTest.php
git commit -m "$(cat <<'EOF'
fix(auth): rate limit the login endpoint

Modified files:
- app/Providers/AppServiceProvider.php - register a "login" rate limiter, 5 attempts per minute per IP
- routes/api.php - apply throttle:login middleware to POST /api/login
- tests/Feature/Api/AuthTest.php - add a test asserting 429 after 5 failed attempts
EOF
)"
```

---

## Task 2: Sanctum token expiration policy

**Files:**
- Modify: `config/sanctum.php`
- Modify: `.env.example`
- Modify: `.env.prod.example`
- Test: `tests/Feature/Api/AuthTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: a `SANCTUM_TOKEN_EXPIRATION` env var (minutes) consumed by `config/sanctum.php`, defaulting to 30 days.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Api/AuthTest.php`:

```php
it('rejects a token older than the configured expiration', function () {
    config(['sanctum.expiration' => 43200]); // 30 days
    $user = User::factory()->create();
    $token = $user->createToken('web')->plainTextToken;

    $this->travel(31)->days();

    $this->withToken($token)
        ->getJson('/api/stats')
        ->assertUnauthorized();

    $this->travelBack();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="rejects a token older than the configured expiration"`
Expected: FAIL, request returns 200 instead of 401 (no expiration enforced yet since the config override in the test has no effect on already-null production default, and the real fix below hasn't landed).

- [ ] **Step 3: Set the default expiration**

In `config/sanctum.php`, change:

```php
    'expiration' => null,
```

to:

```php
    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 43200), // 30 days, in minutes
```

- [ ] **Step 4: Document the new env var**

In `.env.example`, add after the `ELEVATION_ENABLED=true` line:

```dotenv
SANCTUM_TOKEN_EXPIRATION=43200
```

In `.env.prod.example`, add after the `ELEVATION_ENABLED=true` line:

```dotenv
SANCTUM_TOKEN_EXPIRATION=43200
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter="rejects a token older than the configured expiration"`
Expected: PASS

- [ ] **Step 6: Run the full AuthTest suite to check for regressions**

Run: `php artisan test tests/Feature/Api/AuthTest.php`
Expected: all tests PASS

- [ ] **Step 7: Commit**

```bash
git add config/sanctum.php .env.example .env.prod.example tests/Feature/Api/AuthTest.php
git commit -m "$(cat <<'EOF'
fix(auth): expire Sanctum tokens after 30 days by default

Modified files:
- config/sanctum.php - default token expiration set to 43200 minutes (30 days), overridable via SANCTUM_TOKEN_EXPIRATION
- .env.example, .env.prod.example - document the new SANCTUM_TOKEN_EXPIRATION variable
- tests/Feature/Api/AuthTest.php - add a test asserting an expired token is rejected
EOF
)"
```

---

## Task 3: Explicit CORS configuration

**Files:**
- Create: `config/cors.php`
- Test: `tests/Feature/Api/AuthTest.php`

**Interfaces:**
- Consumes: `config('app.url')` (already set via `APP_URL`).
- Produces: nothing consumed by later tasks; this is a standalone Laravel config file picked up automatically by the framework's built-in `HandleCors` middleware.

**Context:** No `config/cors.php` exists today, so Laravel falls back to its framework default (`allowed_origins => ['*']` on `api/*` and `sanctum/csrf-cookie`). Publishing an explicit config restricted to the app's own origin closes this gap. This app's frontend is same-origin (served by the same Laravel app), so the browser never actually needs cross-origin CORS to function; this change only removes an unnecessary permissive default, it does not change any legitimate request path.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Api/AuthTest.php`:

```php
it('does not reflect an unexpected origin in CORS headers', function () {
    $response = $this->getJson('/api/stats', ['Origin' => 'https://evil.example.com']);

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example.com');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="does not reflect an unexpected origin in CORS headers"`
Expected: FAIL, `Access-Control-Allow-Origin` is `*` (or reflects the origin), matching `https://evil.example.com` under the framework default.

- [ ] **Step 3: Create `config/cors.php`**

```php
<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter="does not reflect an unexpected origin in CORS headers"`
Expected: PASS

- [ ] **Step 5: Run the full AuthTest suite to check for regressions**

Run: `php artisan test tests/Feature/Api/AuthTest.php`
Expected: all tests PASS

- [ ] **Step 6: Commit**

```bash
git add config/cors.php tests/Feature/Api/AuthTest.php
git commit -m "$(cat <<'EOF'
fix(api): restrict CORS to the app's own origin

Modified files:
- config/cors.php - new file, replaces the framework's permissive wildcard default with allowed_origins scoped to APP_URL
- tests/Feature/Api/AuthTest.php - add a test asserting an unexpected Origin is not reflected
EOF
)"
```

---

## Task 4: Security headers middleware

**Files:**
- Create: `app/Http/Middleware/SecurityHeaders.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/SecurityHeadersTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: nothing consumed by later tasks; this is a global HTTP middleware, order-independent from the other tasks.

**Context:** Applied as a global (`append`) middleware so it covers both the SPA entry point (`/`, served by `routes/web.php`) and every `/api/*` JSON response. The test below only exercises an API route to avoid depending on `public/build/manifest.json` being present (see Task 9, which confirms the test suite does not need built frontend assets); the middleware itself is registered globally, so this is representative of behavior on every route, including `/`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SecurityHeadersTest.php`:

```php
<?php

it('sends security headers on API responses', function () {
    $response = $this->getJson('/api/stats');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="sends security headers on API responses"`
Expected: FAIL, headers are missing.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adds baseline security headers (CSP, clickjacking, MIME sniffing, referrer policy) to every response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "connect-src 'self'",
            "font-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
        ]));

        return $response;
    }
}
```

- [ ] **Step 4: Register the middleware globally**

In `bootstrap/app.php`, change:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
```

to:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter="sends security headers on API responses"`
Expected: PASS

- [ ] **Step 6: Run the full test suite to check for regressions**

Run: `php artisan test`
Expected: all tests PASS (this is a global middleware, so this full run is the real regression check).

- [ ] **Step 7: Manually verify the SPA entry point in the browser**

This step needs the dev stack running, since `/` requires `public/build/manifest.json` (see Task 9's context):

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
```

Open `http://summit-stats.marvinlerouge.local`, open DevTools > Network, reload, click the document request, and confirm `content-security-policy`, `x-frame-options`, `referrer-policy` and `x-content-type-options` are present in the response headers. Confirm the dashboard still renders (Chart.js charts, Leaflet map) without CSP violation errors in the console; the `style-src 'unsafe-inline'` directive above is intentionally permissive to avoid breaking these libraries' inline style usage.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/SecurityHeaders.php bootstrap/app.php tests/Feature/SecurityHeadersTest.php
git commit -m "$(cat <<'EOF'
fix(security): add baseline security headers to every response

Modified files:
- app/Http/Middleware/SecurityHeaders.php - new global middleware setting CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Strict-Transport-Security
- bootstrap/app.php - register the middleware globally
- tests/Feature/SecurityHeadersTest.php - new test asserting the headers are present on an API response
EOF
)"
```

---

## Task 5: Log failed login attempts

**Files:**
- Modify: `app/Http/Controllers/Api/LoginController.php`
- Modify: `.env.prod.example`
- Modify: `DEPLOY.md`
- Test: `tests/Feature/Api/AuthTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing consumed by later tasks.

**Context:** `LoginController::login()` currently does not log anything on a failed attempt, so failed logins are invisible even to someone tailing production logs. Since production's `LOG_LEVEL` is `error`, a `warning`-level log entry would be silently dropped; this task logs at `warning` and lowers `LOG_LEVEL` to `warning` in `.env.prod.example` so both application errors and this security-relevant signal are captured, without changing local dev logging (`.env.example` stays at `debug`).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Api/AuthTest.php`, and add the `Log` facade import at the top of the file (after the existing `use App\Models\User;` line):

```php
use Illuminate\Support\Facades\Log;
```

```php
it('logs a warning on failed login attempts', function () {
    Log::spy();

    User::factory()->create(['password' => bcrypt('secret123')]);

    $this->postJson('/api/login', ['password' => 'wrong']);

    Log::shouldHaveReceived('warning')->once();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="logs a warning on failed login attempts"`
Expected: FAIL, `Log::warning` was never called.

- [ ] **Step 3: Add the log call**

In `app/Http/Controllers/Api/LoginController.php`, add the import:

```php
use Illuminate\Support\Facades\Log;
```

Change:

```php
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Mot de passe incorrect.', 401);
        }
```

to:

```php
        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('Failed login attempt', ['ip' => $request->ip()]);

            return $this->error('Mot de passe incorrect.', 401);
        }
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter="logs a warning on failed login attempts"`
Expected: PASS

- [ ] **Step 5: Run the full AuthTest suite to check for regressions**

Run: `php artisan test tests/Feature/Api/AuthTest.php`
Expected: all tests PASS

- [ ] **Step 6: Lower the production log level so the warning is not dropped**

In `.env.prod.example`, change:

```dotenv
LOG_LEVEL=error
```

to:

```dotenv
LOG_LEVEL=warning
```

- [ ] **Step 7: Document the change**

In `DEPLOY.md`, in the English section under the `.env` configuration block, add a short note directly under the `LOG_LEVEL=error` example line (update that example line to `LOG_LEVEL=warning` as well, to stay consistent), for example:

```markdown
> `LOG_LEVEL=warning` (not `error`): failed login attempts are logged at `warning` level so they remain visible in production logs. A stricter `error` level would silently drop this signal.
```

Mirror the same note in the French section of `DEPLOY.md` (update `LOG_LEVEL=error` to `LOG_LEVEL=warning` there too, with the French equivalent sentence).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/LoginController.php .env.prod.example DEPLOY.md tests/Feature/Api/AuthTest.php
git commit -m "$(cat <<'EOF'
fix(auth): log failed login attempts and keep them visible in production

Modified files:
- app/Http/Controllers/Api/LoginController.php - log a warning with the request IP on failed login
- .env.prod.example - LOG_LEVEL lowered from error to warning so the warning is not dropped
- DEPLOY.md - document the LOG_LEVEL choice in both language sections
- tests/Feature/Api/AuthTest.php - add a test asserting the warning is logged
EOF
)"
```

---

## Task 6: Harden GPX parsing against XXE

**Files:**
- Modify: `app/Services/Gpx/GpxParserService.php`
- Test: `tests/Unit/Services/Gpx/GpxParserServiceTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing consumed by later tasks.

**Context:** `GpxParserService::parse()` calls `simplexml_load_file()` without any flags. Modern PHP/libxml (8.4, bundled libxml2 >= 2.9) disables external entity substitution by default, so classic XXE file-disclosure via entity expansion is not currently exploitable, but the code does not say so explicitly and relies entirely on an implicit default. Two other things were checked and found already safe, no change needed:
- The stored file path (`gpx/<random 40 chars>.gpx` in `app/Services/ActivityService.php:26`) is generated with `Str::random(40)`, never derived from the uploaded filename, so there is no path traversal vector on write or read.
- `StoreActivityRequest`/`UpdateActivityRequest` already validate `mimes:gpx,xml` and `max:20480` (20 MB), which is adequate.

This task adds two explicit, defense-in-depth measures: reject any file containing a `<!DOCTYPE` declaration outright (legitimate GPX files never have one, so this is a safe, simple, deterministic rejection), and pass `LIBXML_NONET` to block any network-based entity resolution as a second layer.

- [ ] **Step 1: Write the failing test**

Append to `tests/Unit/Services/Gpx/GpxParserServiceTest.php`:

```php
it('rejects a GPX file containing a DOCTYPE declaration', function () {
    $gpx = <<<'XML'
    <?xml version="1.0"?>
    <!DOCTYPE gpx [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
    <gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">
        <trk><trkseg><trkpt lat="45.83" lon="6.86"></trkpt></trkseg></trk>
    </gpx>
    XML;
    file_put_contents(base_path('tests/Fixtures/gpx/xxe_attempt.gpx'), $gpx);

    expect(fn () => $this->parser->parse(base_path('tests/Fixtures/gpx/xxe_attempt.gpx')))
        ->toThrow(GpxParseException::class);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="rejects a GPX file containing a DOCTYPE declaration"`
Expected: FAIL, the file currently parses without throwing (the single `trkpt` has no `<ele>`/`<time>`, but it does have valid `lat`/`lon`, so `parse()` would either succeed with one point then fail later on the "at least 2 points" check, which throws `GpxParseException` too, but for the wrong reason and only by accident; make the fixture have 2 valid trackpoints instead so the test's failure clearly shows the DOCTYPE is currently accepted). Use this corrected fixture instead:

```php
$gpx = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE gpx [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">
    <trk><trkseg>
        <trkpt lat="45.83" lon="6.86"></trkpt>
        <trkpt lat="45.84" lon="6.87"></trkpt>
    </trkseg></trk>
</gpx>
XML;
```

Re-run: `php artisan test --filter="rejects a GPX file containing a DOCTYPE declaration"`
Expected: FAIL, `parse()` returns 2 points successfully instead of throwing.

- [ ] **Step 3: Add the DOCTYPE rejection and `LIBXML_NONET`**

In `app/Services/Gpx/GpxParserService.php`, change:

```php
        if (! file_exists($filePath)) {
            throw new GpxParseException("Fichier GPX introuvable : {$filePath}");
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($filePath);
```

to:

```php
        if (! file_exists($filePath)) {
            throw new GpxParseException("Fichier GPX introuvable : {$filePath}");
        }

        $rawContent = file_get_contents($filePath);
        if ($rawContent !== false && (str_contains($rawContent, '<!DOCTYPE') || str_contains($rawContent, '<!ENTITY'))) {
            throw new GpxParseException('Fichier GPX invalide ou mal formé.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NONET);
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter="rejects a GPX file containing a DOCTYPE declaration"`
Expected: PASS

- [ ] **Step 5: Run the full GpxParserServiceTest suite to check for regressions**

Run: `php artisan test tests/Unit/Services/Gpx/GpxParserServiceTest.php`
Expected: all tests PASS (existing fixtures have no DOCTYPE, unaffected).

- [ ] **Step 6: Run the full test suite to check for regressions in the pipeline tests that depend on the parser**

Run: `php artisan test tests/Unit/Services/Gpx/`
Expected: all tests PASS (`GpxAnalysisOrchestratorTest`, `RegressionTest` etc. use real fixture files with no DOCTYPE).

- [ ] **Step 7: Commit**

```bash
git add app/Services/Gpx/GpxParserService.php tests/Unit/Services/Gpx/GpxParserServiceTest.php
git commit -m "$(cat <<'EOF'
fix(gpx): harden XML parsing against XXE

Modified files:
- app/Services/Gpx/GpxParserService.php - reject any GPX file containing a DOCTYPE/ENTITY declaration, pass LIBXML_NONET to simplexml_load_file as defense in depth
- tests/Unit/Services/Gpx/GpxParserServiceTest.php - add a test asserting a DOCTYPE-bearing file is rejected
EOF
)"
```

---

## Task 7: Document secrets handling and Dependabot triage

**Files:**
- Modify: `DEPLOY.md`

**Interfaces:**
- Consumes: nothing.
- Produces: nothing.

This is a documentation-only task, no test applies.

- [ ] **Step 1: Add a "Secrets and key rotation" subsection to `DEPLOY.md`**

In the English section, after the existing "3. Database setup" section and before "4. File permissions" (for the nginx+PHP-FPM deployment guide), insert:

```markdown
### Secrets and key rotation

- `.env.prod` must not be world-readable: `chmod 600 .env.prod` on the VPS after editing it.
- `APP_KEY` encrypts session data and other Laravel-internal payloads. Rotating it invalidates all existing sessions and any data encrypted with the old key (Sanctum plaintext tokens are hashed, not encrypted with `APP_KEY`, so they are unaffected). Rotate only if the key is suspected to have leaked: generate a new one with `php artisan key:generate --force` and restart the app.
- `DB_PASSWORD` should be generated with `openssl rand -base64 32` or similar, never a memorable password, since it is never typed by a human.
- Dependabot is enabled (`.github/dependabot.yml`, weekly npm + Composer checks). Review and merge or dismiss each alert within a week of it opening; do not let alerts accumulate unreviewed.
```

Mirror the same subsection in French, placed after the French "3. Base de données" section and before "4. Permissions":

```markdown
### Secrets et rotation des clés

- `.env.prod` ne doit pas être lisible par tous : `chmod 600 .env.prod` sur le VPS après édition.
- `APP_KEY` chiffre les données de session et d'autres payloads internes à Laravel. La faire tourner invalide toutes les sessions existantes et toute donnée chiffrée avec l'ancienne clé (les tokens Sanctum sont hashés, pas chiffrés avec `APP_KEY`, donc non affectés). Ne la faire tourner qu'en cas de suspicion de fuite : générer une nouvelle clé avec `php artisan key:generate --force` puis redémarrer l'app.
- `DB_PASSWORD` doit être généré avec `openssl rand -base64 32` ou équivalent, jamais un mot de passe mémorisable puisqu'il n'est jamais saisi par un humain.
- Dependabot est activé (`.github/dependabot.yml`, vérifications hebdomadaires npm + Composer). Traiter chaque alerte (merge ou dismiss) sous une semaine, ne pas les laisser s'accumuler sans revue.
```

- [ ] **Step 2: Commit**

```bash
git add DEPLOY.md
git commit -m "$(cat <<'EOF'
docs(deploy): document secrets handling and Dependabot triage

Modified files:
- DEPLOY.md - add a "Secrets and key rotation" subsection (env file permissions, APP_KEY rotation policy, DB_PASSWORD generation, Dependabot triage cadence) in both language sections
EOF
)"
```

---

## Task 8: PHPStan (Larastan) static analysis

**Files:**
- Modify: `composer.json`
- Create: `phpstan.neon`
- Modify: `.husky/pre-commit`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: nothing.
- Produces: a `composer phpstan` script other tasks/developers can run; a `vendor/bin/phpstan` binary available after `composer install`.

**Context:** No static analysis exists today. Larastan is Laravel-aware PHPStan (understands Eloquent magic, facades, etc.), the direct equivalent of mypy for this codebase. The project is small (~2,000 PHP lines), so running it on the full codebase (not just staged files) in both the pre-commit hook and CI is fast enough; there is no need for the staged-file-only approach `lint-staged` uses for Pint/ESLint.

- [ ] **Step 1: Require Larastan**

Run: `composer require --dev larastan/larastan`
Expected: `composer.json`'s `require-dev` gains a `larastan/larastan` entry, `composer.lock` updates.

- [ ] **Step 2: Create `phpstan.neon`**

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app

    level: 5

    tmpDir: storage/phpstan
```

- [ ] **Step 3: Run the analysis and check the noise level**

Run: `vendor/bin/phpstan analyse`
Expected: some number of errors on a first run against a never-analyzed codebase (this is expected, not a regression).

- [ ] **Step 4: Generate a baseline for existing findings**

Run: `vendor/bin/phpstan analyse --generate-baseline`
Expected: creates `phpstan-baseline.neon` listing every current error by file and line, so the gate only blocks *new* issues from here on. Add it to `phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - app

    level: 5

    tmpDir: storage/phpstan
```

- [ ] **Step 5: Confirm a clean run**

Run: `vendor/bin/phpstan analyse`
Expected: `[OK] No errors` (everything pre-existing is now in the baseline).

- [ ] **Step 6: Add a Composer script**

In `composer.json`, inside the `"scripts"` block, add:

```json
        "phpstan": "phpstan analyse --memory-limit=512M"
```

- [ ] **Step 7: Add PHPStan to the pre-commit hook**

In `.husky/pre-commit`, change:

```bash
npx lint-staged
```

to:

```bash
npx lint-staged
composer phpstan
```

- [ ] **Step 8: Verify the hook catches a real error**

Temporarily introduce a type error, e.g. in `app/Http/Controllers/Api/StatsController.php` add a line that passes a string where an `int` is expected, then run `git add -A && git commit -m "test"` to confirm the hook blocks the commit with a PHPStan error, then `git reset` and revert the temporary change (do not keep this test commit).

- [ ] **Step 9: Add PHPStan to CI**

In `.github/workflows/ci.yml`, in the `tests` job, add a new step right after `Lint PHP`:

```yaml
      - name: Static analysis (PHPStan)
        run: vendor/bin/phpstan analyse --error-format=github
```

- [ ] **Step 10: Run the full backend test suite to confirm nothing else broke**

Run: `php artisan test`
Expected: all tests PASS (PHPStan is a separate static check, does not affect runtime behavior).

- [ ] **Step 11: Commit**

```bash
git add composer.json composer.lock phpstan.neon phpstan-baseline.neon .husky/pre-commit .github/workflows/ci.yml
git commit -m "$(cat <<'EOF'
chore(quality): add PHPStan (Larastan) static analysis

Modified files:
- composer.json, composer.lock - add larastan/larastan to require-dev, add a "phpstan" composer script
- phpstan.neon - new config, level 5, includes a baseline for pre-existing findings
- phpstan-baseline.neon - new file, snapshot of pre-existing findings so the gate only blocks new issues
- .husky/pre-commit - run "composer phpstan" after lint-staged
- .github/workflows/ci.yml - add a "Static analysis (PHPStan)" step to the tests job
EOF
)"
```

---

## Task 9: CI pipeline audit and cleanup

**Files:**
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: nothing.
- Produces: nothing.

**Context:** The `tests` job in `ci.yml` currently runs `Setup Node`, `Install JS dependencies` and `Build assets` (`npm run build`) before running PHP tests. This was verified unnecessary: no test under `tests/Feature` or `tests/Unit` hits a non-API route (confirmed by grepping the test suite for `view('app')` / requests to `/`), so nothing exercises the `@vite()` Blade directive in `resources/views/app.blade.php`, which is the only place `public/build/manifest.json` is read. The project's own `composer test` script (`composer.json`) does not build assets either, confirming this was CI-only overhead.

- [ ] **Step 1: Confirm no test needs built assets**

Run: `grep -rn "get('/')\|view('app')" tests/`
Expected: no matches (already verified while writing this plan; re-verify before editing in case tests changed since).

- [ ] **Step 2: Remove the unnecessary steps**

In `.github/workflows/ci.yml`, in the `tests` job, remove these three steps entirely:

```yaml
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Install JS dependencies
        run: npm ci

      - name: Build assets
        run: npm run build
```

The job should now go directly from `Install PHP dependencies` to `Copy .env`.

- [ ] **Step 3: Verify locally that the test suite does not need built assets**

Run:

```bash
mv public/build public/build.bak 2>/dev/null || true
php artisan test
mv public/build.bak public/build 2>/dev/null || true
```

Expected: full suite PASSES with `public/build` absent, confirming the removed CI steps were indeed dead weight.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "$(cat <<'EOF'
chore(ci): remove unnecessary asset build from the backend test job

Modified files:
- .github/workflows/ci.yml - drop Setup Node / Install JS dependencies / Build assets from the "tests" job; no backend test exercises a route that reads public/build/manifest.json, verified by grepping the suite and by running the tests locally with public/build removed
EOF
)"
```

---

## Task 10: Automated PostgreSQL backup

**Files:**
- Create: `docker/scripts/backup-postgres.sh`
- Modify: `DEPLOY.md`

**Interfaces:**
- Consumes: `docker-compose.prod.yml` / `docker-compose.yml`, `.env.prod` / `.env` (`DB_USERNAME`, `DB_DATABASE`).
- Produces: gzip-compressed SQL dump files in a `backups/` directory, named `summit-stats-<timestamp>.sql.gz`.

**Context:** This script is verified locally against the dev stack in this task (fully within Claude's reach). Installing it as a cron job on the production VPS is a copy-paste handoff to the user in Step 4, since Claude never connects to the VPS.

- [ ] **Step 1: Create the backup script**

Create `docker/scripts/backup-postgres.sh`:

```bash
#!/usr/bin/env bash
# Dumps the PostgreSQL database from a running compose stack and rotates old backups.
# Run from the directory containing the target compose file (dev or prod).
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.prod}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
DUMP_FILE="$BACKUP_DIR/summit-stats-${TIMESTAMP}.sql.gz"

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec -T postgres \
    pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$DUMP_FILE"

echo "Backup written to $DUMP_FILE"

find "$BACKUP_DIR" -name 'summit-stats-*.sql.gz' -mtime +"$RETENTION_DAYS" -delete
```

Make it executable:

```bash
chmod +x docker/scripts/backup-postgres.sh
```

- [ ] **Step 2: Verify it locally against the dev stack**

```bash
docker compose up -d postgres
COMPOSE_FILE=docker-compose.yml ENV_FILE=.env BACKUP_DIR=/tmp/ss-backup-test ./docker/scripts/backup-postgres.sh
ls -la /tmp/ss-backup-test
```

Expected: a `summit-stats-<timestamp>.sql.gz` file exists and is non-empty.

- [ ] **Step 3: Verify the dump restores cleanly**

```bash
docker compose exec postgres createdb -U summit_stats summit_stats_restore_test
gunzip -c /tmp/ss-backup-test/summit-stats-*.sql.gz | docker compose exec -T postgres psql -U summit_stats -d summit_stats_restore_test
docker compose exec postgres psql -U summit_stats -d summit_stats_restore_test -c "SELECT count(*) FROM activities;"
docker compose exec postgres dropdb -U summit_stats summit_stats_restore_test
rm -rf /tmp/ss-backup-test
```

Expected: the `SELECT count(*)` runs without error and returns the same activity count as the source database, confirming the dump is valid and restorable.

- [ ] **Step 4: Document VPS installation as a handoff for the user**

In `DEPLOY.md`, add a new section (English, near the end, before "Updating"; mirror in French):

```markdown
### Automated backups

`docker/scripts/backup-postgres.sh` dumps the database and keeps the last 14 days of backups. Install it as a daily cron job on the VPS (commands below are for you to run over SSH, Claude never executes these):

```bash
crontab -e
# Add this line (adjust the path to where docker-compose.prod.yml lives):
0 3 * * * cd /home/mlr/marvinlerouge.dev/summit-stats/compose && BACKUP_DIR=/home/mlr/marvinlerouge.dev/summit-stats/backups ./docker/scripts/backup-postgres.sh >> /var/log/summit-stats-backup.log 2>&1
```

To restore from a backup:

```bash
gunzip -c /home/mlr/marvinlerouge.dev/summit-stats/backups/summit-stats-<timestamp>.sql.gz | \
  docker compose -f docker-compose.prod.yml --env-file .env.prod exec -T postgres psql -U "$DB_USERNAME" "$DB_DATABASE"
```
```

- [ ] **Step 5: Commit**

```bash
git add docker/scripts/backup-postgres.sh DEPLOY.md
git commit -m "$(cat <<'EOF'
feat(ops): add automated PostgreSQL backup script

Modified files:
- docker/scripts/backup-postgres.sh - new script, pg_dump + gzip + 14-day rotation, verified locally against the dev stack including a full restore
- DEPLOY.md - document cron installation and the restore procedure in both language sections (VPS install is left as a manual step for the user)
EOF
)"
```

---

## Task 11: Full OWASP Top 10:2025 + ASVS 5.0 pass

**Files:** none pre-determined; depends on findings.

**Interfaces:**
- Consumes: the state of the codebase after Tasks 1 to 10.
- Produces: a short audit note plus any additional fix commits, following the exact TDD pattern used in Tasks 1-6 (failing test, fix, passing test, commit) for each finding.

**Context:** This closing task runs the project's `owasp-security` skill against the codebase now that the concrete gaps identified during planning are closed, to catch anything that was missed. Findings are unknown until the skill runs, so this task is a procedure, not a fixed diff.

- [ ] **Step 1: Run the audit**

Invoke the `owasp-security` skill against the current state of the `app/`, `routes/`, `config/`, and `docker/` directories.

- [ ] **Step 2: Triage findings**

For each finding, decide fix-now (clear, small, in scope for a personal-project security baseline) vs defer (log it as a new item in `docs/ai/work-in-progress/v4-action-plan.md` under a "Deferred from Phase 1 audit" note, with the reasoning, for a later phase). Discuss anything ambiguous with the user before fixing, per this project's standing rule to stop on ambiguity rather than assume.

- [ ] **Step 3: Fix each "fix-now" finding using the same TDD pattern as the earlier tasks**

For each: write a failing Pest test, confirm it fails, implement the minimal fix, confirm the test passes, run the relevant test file(s) for regressions, commit with a `fix(security): ...` Conventional Commit message listing modified files, same format as Tasks 1-6.

- [ ] **Step 4: Write the audit note**

Append a short section to `docs/ai/work-in-progress/v4-action-plan.md` under Phase 1, section "1.5 Audit results" (new), summarizing: date run, number of findings, how many fixed vs deferred, and linking to the deferred items list from Step 2.

- [ ] **Step 5: Run the full test suite one final time**

Run: `php artisan test --coverage --min=80`
Expected: all tests PASS, coverage stays at (or returns to) 100% per the project's existing standard.

- [ ] **Step 6: Commit the audit note**

```bash
git add docs/ai/work-in-progress/v4-action-plan.md
git commit -m "$(cat <<'EOF'
docs(security): record Phase 1 OWASP audit results

Modified files:
- docs/ai/work-in-progress/v4-action-plan.md - add section 1.5 summarizing the closing OWASP Top 10:2025 + ASVS 5.0 pass, fixed vs deferred findings
EOF
)"
```

Note: `docs/ai/` is gitignored (confirmed via `.gitignore` line 42), so this commit will not actually pick up the file unless that changes; if `git add` reports nothing to stage, skip the commit and just keep the note locally, then mention its content directly in the PR description instead (Step 7 below).

- [ ] **Step 7: Push and prepare the PR**

```bash
git push -u origin fix/security-hardening-phase1
```

Provide the user with a PR title and description (Claude never creates the PR):

**Title:** `fix(security): Phase 1 security hardening (rate limiting, token expiration, CORS, headers, XXE, backups, static analysis)`

**Description:** summarize each of the 11 tasks as a bullet, list the target tag (`v3.1.0`, per `docs/ai/work-in-progress/v4-action-plan.md`), and note that the automated backup cron job still needs to be installed on the VPS by the user per the `DEPLOY.md` instructions added in Task 10.

---

## Self-Review Notes

- **Spec coverage:** all items from `docs/ai/work-in-progress/v4-action-plan.md` section 1.2 are covered: rate limiting (Task 1), token expiration (Task 2), CORS (Task 3), security headers (Task 4), GPX validation audit (Task 6, plus the note that storage path and mimes/size validation were already safe), secrets handling and Dependabot (Task 7), LOG_LEVEL audit (Task 5), backup (Task 10), OWASP/ASVS pass (Task 11). Section 1.2b is covered: PHPStan (Task 8), CI audit (Task 9).
- **Type/signature consistency:** `SecurityHeaders` middleware is referenced identically in Task 4 Steps 3 and 4. `RateLimiter::for('login', ...)` name matches `throttle:login` in Task 1 Steps 4 and 5. `SANCTUM_TOKEN_EXPIRATION` env var name is consistent across `config/sanctum.php`, `.env.example`, `.env.prod.example` in Task 2.
- **No placeholders:** every step has literal code, exact file paths, and exact commands, except Task 11 which is explicitly a bounded procedure (its findings are unknowable before running the audit), not a vague deferral.
