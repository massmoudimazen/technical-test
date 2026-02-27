# Captain Shop (`captain-shop`)

Bedrock/WordPress + WooCommerce frontend for Captain Scrappin.  
This project consumes `captain-api` only over HTTP (no direct Laravel DB access).

## Architecture
- Custom plugin: `web/app/plugins/captain-sync`
- API service: `Captain_Sync_Api_Service` (uses `wp_remote_request`)
- Sync flow:
  - fetch Laravel averages by mapped slug
  - update WooCommerce product prices
- Admin controls:
  - run Laravel scrapers
  - run sync immediately
  - live scraper status/logs
  - latest scraped prices with DataTables filters
  - historical chart with Chart.js
- Sage theme: `web/app/themes/captain-sage`
  - responsive enterprise layout system
  - accessible mobile navigation toggle
  - componentized styling structure in `resources/css/base` and `resources/css/components`

## Sage Theme Build
```bash
cd /test/captain-shop/web/app/themes/captain-sage
npm install
npm run build
```

Notes:
- Vite base path targets `captain-sage` theme directory.
- Main assets are built from:
  - `resources/css/app.css`
  - `resources/js/app.js`

## Setup
```bash
cd /test/captain-shop
composer install
cp .env.example .env
```

Required `.env` values:
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

Run local WordPress:
```bash
php -S localhost:8000 -t web
```

Activate plugin:
```bash
wp plugin activate captain-sync
```

## Captain Sync Admin UI
Admin menu: `Captain Sync`

Dashboard capabilities:
- Queue sync
- Run sync now
- Run Laravel scrapers
- Live scraper queue/status
- Latest scraped prices table with:
  - product filter
  - vendor filter
  - date range filter (`24h`, `7d`, `30d`, custom)
  - pagination/sorting/search via DataTables
  - stock status color badges
- History chart:
  - product selector
  - date range + custom from/to
  - responsive Chart.js rendering
  - PNG export

Settings tab:
- API URL/token (env-locked when set in Bedrock env)
- timeout/cache/SSL options
- Woo product -> Laravel slug mapping

## Security and Auth
Plugin sends token server-side only:
- `X-CAPTAIN-TOKEN: <token>`

Laravel also accepts bearer fallback.

No token is exposed in frontend JS.

Admin endpoints enforce:
- `manage_options`
- nonce checks for AJAX/admin-post actions

## Manual Commands
Run immediate sync:
```bash
wp eval "captain_sync();"
```

Run WP cron hook:
```bash
wp cron event run captain_sync_cron
```

Check cron events:
```bash
wp cron event list --fields=hook,next_run_relative,schedule --format=table
```

Test Laravel call from WP context:
```bash
wp eval "var_export(captain_api_get('/api/v1/products/gold-bar-1kg/average'));"
```

## Caching, Retry, Fallback
`Captain_Sync_Api_Service` includes:
- retry with backoff for transient failures
- transient cache for GET calls
- stale fallback when live API fails
- sync fallback to `captain_last_prices` if average endpoint is unavailable

Useful options:
- `captain_last_sync`
- `captain_last_sync_result`
- `captain_last_prices`

## Large Dataset Validation
For stress testing DataTables/chart UX, seed large dataset in Laravel:
```bash
cd /test/captain-api
CAPTAIN_SEED_LARGE_DATASET=true php artisan db:seed
```

Then in WP admin:
1. Run Laravel scrapers
2. Run sync now
3. Validate filters, pagination, chart rendering, and WooCommerce price updates

## Troubleshooting
- `401` in plugin logs: token mismatch between WP and Laravel env
- connection timeout: wrong `CAPTAIN_API_URL` or Laravel not running
- stale data shown: API currently failing and stale fallback is active
- no new scraper rows: Laravel queue worker not running
- cron not firing: WP-Cron needs traffic; configure real server cron
