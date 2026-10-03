# AGENTS.md

## Project state

Laravel 12 (installed v12.68.0) REST API, PHP 8.4 (`composer.json` allows ^8.2), MySQL for dev, Sanctum for API auth. **Early stage**: 28 migrations + ~25 Eloquent models exist, but there are **no API routes or controllers yet** — `routes/api.php` only has the default `GET /user` (auth:sanctum). Don't expect endpoints where features don't exist yet.

**The README is stale.** It describes a POS/inventory/sales app that does not match the schema. The real domain is a per-customer subscription/service business aimed at El Salvador: customers, plants, services, subscription plans, quotes/appointments/inspections, wallets + loyalty points, DTE invoices, payments, ledger entries, audit log. Trust `database/migrations` and `app/Models`, not `README.md`.

## Setup & required order

- `composer setup` is the documented bootstrap (install, copy `.env`, `key:generate`, `migrate --force`, npm install+build).
- Local dev: `composer dev` (runs `php artisan serve` + `queue:listen --tries=1` + Vite concurrently).
- Docker stack (`docker compose up -d`): `app` (php:8.4-apache with mod_rewrite, serving `public/` on :80, Apache vhost in `docker/apache/default.conf`), MySQL 8, Redis (phpredis).

### Verified gotchas

- **`bootstrap/cache/` (and storage/framework subdirs) are gitignored and absent after a fresh clone** → `artisan` fails with "bootstrap/cache directory must be present and writable". Create the dirs before running any artisan command: `New-Item -ItemType Directory -Path bootstrap/cache -Force`.
- **The committed local `.env` has no `APP_KEY`** → `php artisan key:generate` is required before app/tests will run.
- Dev DB is MySQL (`DB_DATABASE=vergel_api`); **tests run against sqlite `:memory:`** (phpunit.xml). The full migration suite booted cleanly on sqlite, so you can develop against the default test DB without MySQL.
- **Docker MySQL gotchas**: the official `mysql:8.0` image applies `MYSQL_*` env vars only on **first init of an empty volume** — changing `.env` later does nothing to an existing `db-data` volume (fix with `ALTER USER ... IDENTIFIED BY` inside the container, or `docker compose down -v`). Never set `MYSQL_USER=root` (entrypoint aborts and the container crash-loops); keep it decoupled from `.env` `DB_USERNAME`.
- **Migrations were renamed in git history** (`2026_08_28_014400 quotes`, `014500 appointments`, `022000 wallets`, `040000 loyalty_points`) — an existing dev DB's `migrations` table may still hold the old names, making `migrate` try to re-create existing tables ("Table ... already exists"). If you hit that, UPDATE the `migrations` rows to the current filenames. Don't rename migrations that have already run.

## Commands

- Tests: `composer test` (runs `artisan config:clear` then `artisan test`). Single test: `php artisan test --filter=<name>`; suites: `--testsuite=Unit|Feature`. Tests need an `APP_KEY` and the bootstrap/cache dir (see above).
- Formatting: `laravel/pint` installed, default preset, no `pint.json`. Run `./vendor/bin/pint`.
- **Scribe API docs** (`knuckleswtf/scribe`): annotated via docblocks (`@group`, `@subgroup`, `@queryParam`, `@bodyParam`, `@response`) in controllers; config in `config/scribe.php` (auth = Sanctum bearer, `type = static`). Regenerate with `php artisan scribe:generate` → writes static HTML to repo-root `docs/` (commit it; `.nojekyll` included so GitHub Pages serves it raw). No `/docs` web route in `static` mode. Requires the `bootstrap/cache` + `storage/framework/*` dirs (above).
- `npm run build` / `npm run dev` for Vite/Tailwind; only needed for the default blade skeleton — the API itself has no frontend deps.
- No CI workflows, no codegen, no other task runner.

## Code conventions (already established)

- Models consistently use `$fillable` + `$casts`; most use `SoftDeletes`; explicit `protected $table` only for non-plural names (`audit_log`, `customer_plan_history`, `country_config`).
- Money/amounts are `decimal(10, 2)` columns; statuses are `enum` columns; FKs use `foreignId()->constrained()` with explicit `onDelete`/`onUpdate`.
- Domain user is the `employees` table (referenced as `employee_id` on quotes/invoices, seeded with DUI/birth_date/phone); the `users` table is the untouched Laravel skeleton user.
- **Spanish is the convention** for README, comments, and commit messages (e.g. `feat:`, `fix:`). El-Salvador-specific concepts appear in the schema: DUI, NRC, DTE invoices, IVA (VAT).