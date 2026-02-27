# Captain Shop (`captain-shop`)

## Project Overview
`captain-shop` is the Bedrock/WordPress + WooCommerce frontend.

It includes a custom plugin: `web/app/plugins/captain-sync`.

The plugin is responsible for:
- fetching product pricing data from `captain-api`
- updating WooCommerce prices from Laravel averages
- launching Laravel scrapers from WP admin
- showing live scraper status/logs and latest price rows
- rendering historical charts via REST proxy route

Important rule: WordPress is an API consumer only. It does not write to Laravel DB directly.

---

## Architecture

### High-level Flow
```mermaid
flowchart LR
    ADMIN[WP Admin\nCaptain Sync] --> AJAX[admin-ajax.php\ncaptain_scrapers_run\ncaptain_scrapers_status\ncaptain_sync_now]
    AJAX --> SERVICE[Captain_Sync_Api_Service]
    SERVICE -->|X-CAPTAIN-TOKEN / Bearer capable API| LARAVEL[captain-api]

    CRON[WP-Cron\ncaptain_sync_cron] --> SYNC[captain_sync()]
    SYNC --> WOO[WooCommerce Product Prices]
    SYNC --> LOGDB[(wp_*captain_sync_logs)]

    CHART[History Chart UI] --> REST[/wp-json/captain/v1/history/{slug}] --> SERVICE

    SERVICE --> CACHE[Transient cache + stale fallback]
```

### Integration Boundaries
- WordPress uses HTTP only (`wp_remote_request`) against Laravel.
- API credentials come from Bedrock env (`CAPTAIN_API_URL`, `CAPTAIN_API_TOKEN`).
- Token is not exposed to frontend JS; requests are server-side.

---

## Setup

## 1) Prerequisites
- PHP 8.2+
- Composer 2+
- MySQL/MariaDB
- Laravel API running (`captain-api`)

## 2) Install
```bash
cd /test/captain-shop
composer install
```

## 3) Configure `.env`
Minimum keys:
```dotenv
WP_ENV=development
WP_HOME=http://localhost:8000
WP_SITEURL=${WP_HOME}/wp

DB_NAME=captain_db
DB_USER=root
DB_PASSWORD=
DB_HOST=localhost
DB_PREFIX=wp_captain

CAPTAIN_API_URL=http://127.0.0.1:8001
CAPTAIN_API_TOKEN=your-secure-token
```

## 4) Start local WordPress
```bash
php -S localhost:8000 -t web
```

## 5) Ensure plugin is active
```bash
wp plugin activate captain-sync
```

## 6) Flush cache after config changes
```bash
wp cache flush
```

---

## Plugin Configuration

Settings are stored in option: `captain_sync_options`.

Read effective options:
```bash
wp option get captain_sync_options --format=json
wp eval "print_r(captain_opts());"
```

Expected with env-backed config:
- `api_url_locked = 1`
- `api_token_locked = 1`

`captain_opts()` merges option values with env constants:
- `CAPTAIN_API_URL`
- `CAPTAIN_API_TOKEN`

---

## Admin UI Usage (Captain Sync)

Menu: **Captain Sync** in WP admin.

### Dashboard Actions
- **Queue Sync**: schedules WP sync job (`captain_sync_run_once`).
- **Run Sync Now**: immediate Woo price sync via AJAX (`captain_sync_now`).
- **Run Laravel Scrapers**: triggers `/api/v1/scrapers/run` via AJAX (`captain_scrapers_run`).

### Scraper Control Card
Shows live (auto-refresh every 5s):
- queue pending jobs
- failed jobs
- last dispatch
- latest scrape timestamp
- recent Laravel scraper logs
- latest scraped prices table

### Price History Card
- Select mapped product slug
- Select range (`24h`, `7d`, `30d`, `all`)
- Chart pulls data from `/wp-json/captain/v1/history/{slug}`

### Settings Tab
- API URL/token (read-only when env-locked)
- request timeout, cache TTL, SSL verify
- product mapping table (Woo product ID -> Laravel product slug)

---

## Running Sync and Scrapers

## A) From WP admin
- Click **Run Laravel Scrapers**.
- Process Laravel queue worker.
- Click **Run Sync Now**.
- Woo prices update immediately.

## B) Via WP CLI
Trigger sync directly:
```bash
wp eval "captain_sync(); echo get_option('captain_last_sync');"
```

Trigger cron event manually:
```bash
wp cron event run captain_sync_cron
```

Check cron schedule:
```bash
wp cron event list --fields=hook,next_run_relative,schedule --format=table
```

## C) Via AJAX handlers (security checks)
Valid admin + nonce:
```bash
wp eval 'wp_set_current_user(1); $_POST = ["nonce" => wp_create_nonce("captain_admin_nonce")]; $_REQUEST = $_POST; do_action("wp_ajax_captain_scrapers_status");'
```

Invalid nonce:
```bash
wp eval 'wp_set_current_user(1); $_POST = ["nonce" => "invalid"]; $_REQUEST = $_POST; do_action("wp_ajax_captain_scrapers_status");'
```
Expected: `-1`

Non-admin user:
```bash
wp eval 'wp_set_current_user(0); $_POST = ["nonce" => wp_create_nonce("captain_admin_nonce")]; $_REQUEST = $_POST; do_action("wp_ajax_captain_scrapers_status");'
```
Expected: JSON unauthorized response.

---

## Manual Testing Commands (WP + Laravel)

## 1) API connectivity
```bash
wp eval "var_export(captain_api_get('/api/v1/products/gold-bar-1kg/average'));"
```
Expected: `success => true` and `average_sell_price` numeric.

## 2) Scraper status from WP service
```bash
wp eval "var_export(captain_api_get('/api/v1/scrapers/status'));"
```
Expected: queue fields + latest prices + recent logs.

## 3) Sync and verify Woo prices
```bash
wp eval "captain_sync();"
wp wc product get 13 --user=1 --field=price
wp wc product get 14 --user=1 --field=price
```
Expected: prices match Laravel averages for mapped slugs.

## 4) History REST proxy route
```bash
wp eval 'wp_set_current_user(1); $req = new WP_REST_Request("GET", "/captain/v1/history/gold-bar-1kg"); $res = rest_do_request($req); echo $res->get_status();'
```
Expected: `200`

## 5) Multi-product history verification
```bash
wp eval 'wp_set_current_user(1); $products = captain_api_get("/api/v1/products"); foreach ($products["data"] as $p) { $req = new WP_REST_Request("GET", "/captain/v1/history/" . $p["slug"]); $res = rest_do_request($req); $data = $res->get_data(); $count = isset($data["data"]["data"]["history"]) ? count($data["data"]["data"]["history"]) : 0; echo $p["slug"] . ":status=" . $res->get_status() . ";points=" . $count . PHP_EOL; }'
```
Expected: all products return `status=200` and `points>0`.

---

## Queue and Scheduler Management

## Laravel side (required for fresh data)
Run from `captain-api`:
```bash
php artisan queue:work --queue=scrapers --tries=5
php artisan schedule:work
```

## WordPress side
WP-Cron is traffic-dependent. For reliable execution, use real cron:
```bash
* * * * * cd /test/captain-shop && wp cron event run --due-now --quiet
```

Or run manually:
```bash
wp cron event run captain_sync_cron
```

---

## Data Seeding Verification (from WP perspective)

Laravel should be seeded and returning realistic data.

Quick checks:
```bash
wp eval "var_export(captain_api_get('/api/v1/products'));"
wp eval "var_export(captain_api_get('/api/v1/products/latest'));"
```

Expected:
- 5+ product slugs
- multiple vendors per product in vendor endpoint
- `stock_status` present in vendor/status payloads

---

## API Auth and Headers Used by Plugin

The plugin sends:
- `Accept: application/json`
- `X-CAPTAIN-TOKEN: <token>`

Laravel also accepts bearer token fallback.

Common auth outcomes:
- invalid/missing token -> HTTP `401`
- valid token -> JSON `success=true`

---

## Caching, Retry, and Fallback Behavior

`Captain_Sync_Api_Service` behavior:
- retries HTTP failures (`CAPTAIN_API_MAX_RETRIES`, backoff)
- optional SSL retry without verify when SSL issues detected
- transient cache for GET requests (`cache_ttl`)
- stale response fallback from option storage when live API fails
- sync-level fallback to `captain_last_prices` when average endpoint unavailable

Useful options:
- `captain_last_sync`
- `captain_last_sync_result`
- `captain_last_prices`

View recent plugin logs:
```bash
wp eval 'global $wpdb; $table = $wpdb->prefix . "captain_sync_logs"; print_r($wpdb->get_results("SELECT type,message,created_at FROM {$table} ORDER BY id DESC LIMIT 20", ARRAY_A));'
```

---

## Idempotency Notes

Duplicate snapshot prevention is enforced in Laravel (`prices.vendor_id + product_id + scraped_at`).

Impact in WP:
- repeated sync calls in the same minute do not create duplicate Laravel rows
- Woo prices are updated only if value changed

---

## Troubleshooting

## 1) `No price resolved` in logs
- Check product mapping (Woo ID -> Laravel slug).
- Confirm slug exists in `/api/v1/products`.

## 2) `Laravel API HTTP 401`
- Token mismatch between Bedrock `.env` and Laravel `.env`.
- Verify `CAPTAIN_API_TOKEN` and `CAPTAIN_TOKEN` values.

## 3) Connection refused / timeout
- `CAPTAIN_API_URL` incorrect or Laravel server down.
- Verify `http://127.0.0.1:8001` is reachable.

## 4) Data appears stale
- stale fallback may serve cached old response when API fails.
- inspect `captain_sync_logs` for `Using stale Laravel data`.
- restore API connectivity, then run scraper + sync again.

## 5) Pending jobs keep growing
- Laravel queue worker not running.
- Start worker on `scrapers` queue.

## 6) Cron sync does not execute automatically
- WP-Cron requires traffic.
- configure real cron or trigger via `wp cron event run`.

## 7) Chart empty
- product not mapped or no history points yet.
- verify `/wp-json/captain/v1/history/{slug}` returns `ok=true`.

---

## End-to-End Quick Start (Recommended)

1. Start Laravel API + queue + scheduler.
2. Start WordPress (`php -S localhost:8000 -t web`).
3. Open WP admin -> Captain Sync.
4. Click **Run Laravel Scrapers**.
5. Wait for queue completion (`pending_jobs=0`).
6. Click **Run Sync Now**.
7. Verify Woo prices and chart history.

This confirms the full Laravel -> WP/Woo pipeline in one pass.

