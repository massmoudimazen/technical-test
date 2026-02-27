# Captain Project

Developer manual for the Captain platform.

- `captain-api`: Laravel backend (scrapers, jobs, API, operational scripts)
- `captain-shop`: Bedrock/WordPress + WooCommerce frontend/shop interface

---

## 1) Project Overview

`Captain Project` is split into two applications:

- `captain-api`
  - Laravel service for market ingestion and pricing.
  - Contains vendor scrapers, queue jobs, scheduler logic, and API endpoints.
  - Provides admin/ops-style commands through Artisan (scraper dispatch, queue, config).
- `captain-shop`
  - WordPress (Bedrock) + WooCommerce storefront.
  - Contains the public shop UI and Captain Sync integration with `captain-api`.
  - Includes Sage theme frontend (`web/app/themes/captain-sage`).

The two apps communicate over HTTP using:
- `CAPTAIN_API_URL`
- `CAPTAIN_API_TOKEN`

---

## 2) Prerequisites

- Git
- PHP `>= 8.2` (project files allow 8.1 in places, but use 8.2+ for consistency)
- Composer
- Node.js `>= 18` (recommended: Node 20+ for Sage theme build)
- MySQL `>= 8`
- Redis (optional)
- Docker + Docker Compose (optional)

---

## 3) Installation

### 3.1 Clone repository

```bash
git clone <your-repo-url> captain-project
cd captain-project
```

### 3.2 Install dependencies

#### API (`captain-api`)

```bash
cd captain-api
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` (minimum):

```dotenv
APP_URL=http://127.0.0.1:8001

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=captain_api
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CAPTAIN_TOKEN=change-me
CAPTAIN_TOKEN_HEADER=X-CAPTAIN-TOKEN
SCRAPE_INTERVAL=5
SCRAPE_QUEUE_CONNECTION=database
SCRAPE_QUEUE_NAME=scrapers
CAPTAIN_SEED_LARGE_DATASET=false
```

Run migrations + seeders:

```bash
php artisan migrate --seed
```

Optional large dataset:

```bash
CAPTAIN_SEED_LARGE_DATASET=true php artisan db:seed
```

#### Shop (`captain-shop`)

```bash
cd ../captain-shop
composer install
cp .env.example .env
```

Edit `.env` (minimum):

```dotenv
WP_ENV=development
WP_HOME=http://localhost:8000
WP_SITEURL=${WP_HOME}/wp

DB_NAME=captain_shop
DB_USER=root
DB_PASSWORD=
DB_HOST=127.0.0.1
DB_PREFIX=wp_

CAPTAIN_API_URL=http://127.0.0.1:8001
CAPTAIN_API_TOKEN=change-me
```

Theme frontend dependencies:

```bash
cd web/app/themes/captain-sage
npm install
```

If required in your environment, generate WordPress salts and place them in `captain-shop/.env`.

---

## 4) Running the Project

### 4.1 Start API

From `captain-api`:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

### 4.2 Start queue worker (API)

```bash
php artisan queue:work --queue=scrapers --tries=5
```

### 4.3 Start scheduler worker (API)

```bash
php artisan schedule:work
```

### 4.4 Start shop (WordPress)

From `captain-shop`:

```bash
php -S localhost:8000 -t web
```

### 4.5 Start theme dev server (optional, for live frontend changes)

From `captain-shop/web/app/themes/captain-sage`:

```bash
npm run dev
```

### 4.6 Port configuration

- API: set in `captain-api/.env` (`APP_URL`, serve `--port`)
- Shop: set in `captain-shop/.env` (`WP_HOME`)
- Integration: set `CAPTAIN_API_URL` in `captain-shop/.env` to API host/port

---

## 5) Running Scrapers and Bash Scripts

### 5.1 Run a single scraper (specific vendor)

From `captain-api`:

```bash
php artisan run:scrapers --vendor=aurum-market --force
php artisan run:scrapers --vendor=bullion-direct --force
php artisan run:scrapers --vendor=metalis-exchange --force
```

Run synchronously (without queue):

```bash
php artisan run:scrapers --vendor=aurum-market --sync --force
```

### 5.2 Run all scrapers

```bash
php artisan run:scrapers --force
```

### 5.3 Queue-based run

```bash
php artisan run:scrapers --force
php artisan queue:work --queue=scrapers --tries=5
```

### 5.4 Execute custom bash scripts (example)

Create `scripts/run-scrapers.sh` at project root:

```bash
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../captain-api"
php artisan run:scrapers --force
php artisan queue:work --queue=scrapers --stop-when-empty
```

Run:

```bash
bash scripts/run-scrapers.sh
```

Windows batch equivalent (example `scripts/run-scrapers.bat`):

```bat
@echo off
cd /d %~dp0\..\captain-api
php artisan run:scrapers --force
php artisan queue:work --queue=scrapers --stop-when-empty
```

---

## 6) Admin Usage

This project can be run locally without login for public API/shop testing.
Admin login is only needed for WordPress admin actions.

### 6.1 WordPress admin

- URL: `http://localhost:8000/wp/wp-admin`
- Captain Sync screen: `Captain Sync` menu in WordPress admin

### 6.2 Default credentials

- No default admin credentials are committed in this repository.
- If you imported a DB dump that includes users, use credentials from that dump.

### 6.3 Create admin user (WP-CLI)

From `captain-shop`:

```bash
wp user create admin admin@example.com --role=administrator --user_pass='ChangeMe123!'
```

Activate plugin if needed:

```bash
wp plugin activate captain-sync
```

---

## 7) Queue and Job Management

### 7.1 Start queues

From `captain-api`:

```bash
php artisan queue:work --queue=scrapers --tries=5
```

### 7.2 Run jobs in background

Linux example:

```bash
nohup php artisan queue:work --queue=scrapers --tries=5 > storage/logs/queue-worker.log 2>&1 &
```

### 7.3 Scheduler and cron

Run scheduler worker in dev:

```bash
php artisan schedule:work
```

Production cron example:

```cron
* * * * * cd /path/to/captain-project/captain-api && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8) Docker (Optional)

No root-level `docker-compose.yml` is committed by default.
For API, use Laravel Sail to generate one quickly.

### 8.1 Generate and run Sail (API)

From `captain-api`:

```bash
php artisan sail:install
./vendor/bin/sail up -d
```

### 8.2 Run commands inside container

```bash
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan run:scrapers --force
./vendor/bin/sail artisan queue:work --queue=scrapers --tries=5
```

If you maintain your own Docker Compose at project root:

```bash
docker compose up -d
docker compose exec captain-api php artisan migrate --seed
```

---

## 9) Project Structure

```text
captain-project/
|- captain-api/
|  |- app/
|  |  |- Console/Commands/RunScrapers.php
|  |  |- Jobs/ScrapeVendorJob.php
|  |  |- Scrapers/
|  |  `- Http/Controllers/Api/
|  |- database/
|  |  |- migrations/
|  |  `- seeders/
|  |- routes/api.php
|  |- config/
|  `- README.md
`- captain-shop/
   |- web/
   |  |- app/plugins/captain-sync/
   |  `- app/themes/captain-sage/
   |- config/
   |- wp-cli.yml
   `- README.md
```

---

## 10) Common Commands

### API (`captain-api`)

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan queue:restart
php artisan migrate:fresh --seed
php artisan l5-swagger:generate
```

### Shop (`captain-shop`)

```bash
wp cache flush
wp cron event run captain_sync_cron
wp cron event list --fields=hook,next_run_relative,schedule --format=table
wp eval "captain_sync();"
```

### Theme frontend (`captain-shop/web/app/themes/captain-sage`)

```bash
npm run dev
npm run build
```

---

## 11) Notes

### Best practices

- Keep `CAPTAIN_API_TOKEN` synchronized between API and shop.
- Never commit `.env` files.
- Use separate DBs for API and shop (`captain_api`, `captain_shop`).
- Keep queue worker and scheduler running during scraper development.

### Logs location

- API logs: `captain-api/storage/logs/laravel.log`
- Queue logs (if redirected): `captain-api/storage/logs/queue-worker.log`
- Shop debug log (if enabled): path from `WP_DEBUG_LOG` (or default WP debug location)
- Captain Sync operational logs: WordPress DB table `wp_captain_sync_logs` and admin UI

### Troubleshooting

- `401 Unauthorized` from API:
  - check `CAPTAIN_API_TOKEN` and request header (`X-CAPTAIN-TOKEN`)
- Scrapers dispatch but no new data:
  - ensure `queue:work` is running
- Scheduler not triggering:
  - run `schedule:work` in dev or configure cron in prod
- Shop cannot reach API:
  - verify `CAPTAIN_API_URL` and API server port
- `/shop` not found in WordPress:
  - assign WooCommerce Shop page and re-save permalinks
- Blade/template odd behavior:
  - clear Acorn view cache under `captain-shop/web/app/cache/acorn/framework/views`

---

## Quick Start (Minimal)

```bash
# 1) API
cd captain-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8001
```

```bash
# 2) API worker + scheduler (new terminals)
cd captain-api
php artisan queue:work --queue=scrapers --tries=5
php artisan schedule:work
```

```bash
# 3) Shop
cd captain-shop
composer install
cp .env.example .env
php -S localhost:8000 -t web
```

```bash
# 4) Theme assets (optional but recommended)
cd captain-shop/web/app/themes/captain-sage
npm install
npm run dev
```

