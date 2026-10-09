# AGENTS.md

## Project state

Laravel 12.68 REST API (Docker uses PHP 8.4; `composer.json` allows ^8.2), MySQL for dev, Sanctum for auth.

**The README is stale and wrong**: it describes a POS/inventory/sales app and badges PHP 8.6 / Laravel 13 — neither matches reality. The real domain is a per-customer subscription/service business for El Salvador: customers, plants, services, subscription plans, quotes/appointments/inspections, wallets + loyalty points, DTE invoices, payments, ledger entries, audit log. Trust `database/migrations` and `app/Models`, not `README.md`.

30 migrations / 25 Eloquent models. Live API modules: **auth (login/logout)**, **customers**, **subscription plans**, **employees**, and **roles** under `/api/v1` (`routes/api.php`).

## Architecture (thin controllers -> services)

- `routes/api.php`: `POST /api/login` is public; everything else is behind `auth:sanctum` under `/api/v1`. There is no default `GET /user` route.
- Controllers: `app/Http/Controllers/Api/AuthController` and `app/Http/Controllers/Api/V1/*`. They validate via FormRequests, delegate to services, and return API Resources. They do **not** query Eloquent directly (only exception: `CustomerController::planHistory`).
- FormRequests: `app/Http/Requests/<Domain>/`. Services: `app/Services/` (+ `app/Services/Plans/`). Business logic + `DB::transaction` live in services; every write also calls a private `logAudit()` that inserts into `audit_log`. Audit is silently skipped when no `employees` row exists.
- Resources: `app/Http/Resources/<Domain>/` — map snake_case columns to camelCase JSON (`legal_name` -> `legalName`).
- Auth user is the skeleton `users` table, linked to `employees` via `users.employee_id` (unique, nullable). `employees.email/password` were dropped; credentials live on `users`. Seeded admin: `admin@vergel.com` / `password123` (`AuthTestUserSeeder`, run by `db:seed`).

## Setup & gotchas

- `composer setup`: install, copy `.env`, key:generate, migrate, npm install+build. `composer dev`: `serve` + `queue:listen --tries=1` + Vite concurrently.
- Docker: `app` (php:8.4-apache serving `public/`) + MySQL 8 + Redis; `docker compose up -d`.
- **`bootstrap/cache/` is gitignored and absent after a fresh clone** -> artisan dies with "bootstrap/cache directory must be present and writable". Create it first: `New-Item -ItemType Directory -Path bootstrap/cache -Force`.
- The committed `.env` has an **empty `APP_KEY`** -> run `php artisan key:generate`. Some tests fail without it (see Testing).
- Dev DB is MySQL `vergel_api`; tests use sqlite `:memory:` (phpunit.xml), so tests run without MySQL.
- Docker MySQL only applies `MYSQL_*` on first init of an empty volume; later `.env` changes do nothing (fix via `ALTER USER` or `docker compose down -v`). Never set `MYSQL_USER=root`.
- Migrations were renamed in git history (quotes/appointments/wallets/loyalty_points). An existing DB's `migrations` table may hold the old names, causing "Table ... already exists"; UPDATE those rows instead of renaming files.

## Commands

- Tests: `composer test` (config:clear + artisan test). Single: `php artisan test --filter=<name>`; suites `--testsuite=Unit|Feature`. Tests need `bootstrap/cache` + `APP_KEY`.
- Format: `./vendor/bin/pint` (default preset, no `pint.json`).
- Scribe docs: annotations are controller docblocks (`@group`, `@subgroup`, `@queryParam`, `@bodyParam`, `@response`); config `config/scribe.php` (static, `output_path: docs` at repo root). Regenerate: `php artisan scribe:generate`. Committed output is `docs/index.html` + `docs/openapi.yaml` + assets; the extra Postman/collection export it emits is untracked. Needs the bootstrap/cache + storage dirs above.
- `npm run build` / `npm run dev` only affect the blade skeleton (`resources/views/welcome.blade.php`); the API has no frontend deps. Root `/` is a real 200 and the Render health check.

## Testing

- Feature tests hit HTTP with `$this->actingAs(User::factory()->create(), 'sanctum')` and `RefreshDatabase` (see `SubscriptionPlanApiTest`).
- Service tests manually insert `roles` + `employees` rows because services write audit logs keyed to an employee (see `CustomerServiceTest`).
- **The committed suite is not green**: `php artisan test` reports 5 failures — 4 `Customers\Customer*Test` HTTP tests assert 200 but get 401 (they never authenticate; customer routes are protected) and `Tests\Feature\ExampleTest` throws `MissingAppKeyException` until `key:generate` runs. Plan tests + `CustomerServiceTest` pass.

## Deploy to Render.com

- Dockerfile: `php:8.4-apache`, installs pdo_mysql/mbstring/exif/pcntl/bcmath/gd/zip + redis (pecl), `composer install --no-dev --optimize-autoloader`, Apache vhost -> `/var/www/public`.
- `docker/entrypoint.sh` plus inline CMD rewrite Apache `Listen` / `<VirtualHost *:PORT>` from `$PORT` (defaults to 80 locally).
- `render.yaml` (Blueprint, `runtime: docker`): `APP_KEY`/`DB_PASSWORD` use `sync: false`; DB is Aiven MySQL with `MYSQL_ATTR_SSL_CA=docker/certs/aiven-ca.pem` (resolved relative to `base_path()` in `config/database.php`); session/queue/cache on `database`, `LOG_CHANNEL=stderr`, health check `/`.
- Storage is ephemeral; mount a Disk at `/var/www/storage` if uploading files.

## Conventions

- Models: `$fillable` + `$casts`; mostly `SoftDeletes`; explicit `$table` only for non-plural names (`audit_log`, `customer_plan_history`, `country_config`).
- Money is `decimal(10,2)`; statuses are enum columns; FKs use `foreignId()->constrained()` with explicit `onDelete`/`onUpdate`.
- Spanish is the convention for comments, docblocks, README, and commit messages (`feat:`, `fix:`).
- El Salvador domain concepts in the schema: DUI, NRC, DTE invoices, IVA.
