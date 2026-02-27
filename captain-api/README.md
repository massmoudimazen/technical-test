# Captain API (`captain-api`)

Laravel backend for Captain Scrappin. It scrapes vendor prices, stores immutable snapshots, and exposes token-protected APIs consumed by `captain-shop` (WordPress/WooCommerce).

## Architecture
- Scraper command: `php artisan run:scrapers`
- Queue job per vendor: `ScrapeVendorJob`
- Scheduler: Laravel scheduler triggers `run:scrapers` every minute, with DB-backed frequency guard
- Storage:
  - `vendors`
  - `products`
  - `prices` (historical immutable snapshots)
  - `settings` (frequency/dispatch state)
- API auth middleware: `captain.token` (`X-CAPTAIN-TOKEN`, Bearer fallback)

## Setup
```bash
cd /test/captain-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Configure `.env`:
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

## Run
Terminal 1:
```bash
php artisan serve --host=127.0.0.1 --port=8001
```

Terminal 2:
```bash
php artisan queue:work --queue=scrapers --tries=5
```

Terminal 3:
```bash
php artisan schedule:work
```

## Swagger / OpenAPI
L5 Swagger is installed and configured.

Generate docs:
```bash
php artisan l5-swagger:generate
```

Interactive UI:
- `GET /api/documentation`

Raw generated docs:
- `GET /docs` (JSON)
- `storage/api-docs/api-docs.yaml`

Token-protected YAML download endpoint:
- `GET /api/v1/openapi`

## API Authentication
Accepted auth formats:

1. Header token
```http
X-CAPTAIN-TOKEN: change-me
```

2. Bearer fallback
```http
Authorization: Bearer change-me
```

Sanctum-protected route:
- `GET /api/user` (expects Sanctum bearer token)

## Main API Endpoints
| Method | Path | Purpose |
|---|---|---|
| GET | `/api/user` | authenticated user (Sanctum) |
| GET | `/api/health` | health alias |
| GET | `/api/v1/health` | detailed health/metrics |
| GET | `/api/v1/products` | product list |
| GET | `/api/v1/products/latest` | latest aggregated snapshot per product |
| GET | `/api/v1/products/{slug}/average` | market average (latest per vendor) |
| GET | `/api/v1/products/{slug}/vendors` | latest per-vendor rows |
| GET | `/api/v1/products/{slug}/history` | historical average series |
| GET | `/api/v1/config` | scrape config |
| PUT | `/api/v1/config/scrape-frequency` | update frequency (minutes) |
| POST | `/api/v1/scrapers/run` | trigger scrape dispatch |
| GET | `/api/v1/scrapers/status` | queue + recent scrape status |

## Manual API Tests
```bash
curl -H "X-CAPTAIN-TOKEN: change-me" \
  http://127.0.0.1:8001/api/v1/products/gold-bar-1kg/average

curl -H "Authorization: Bearer change-me" \
  http://127.0.0.1:8001/api/v1/products/gold-bar-1kg/history

curl -X POST http://127.0.0.1:8001/api/v1/scrapers/run \
  -H "X-CAPTAIN-TOKEN: change-me" \
  -H "Content-Type: application/json" \
  -d '{"force":true}'
```

## Seeder Data
Default seeder inserts realistic demo data (5 products, multiple vendors, historical rows).

Optional large dataset for UI/perf testing (50 products, 10 vendors):
```bash
CAPTAIN_SEED_LARGE_DATASET=true php artisan db:seed
```

## Idempotency
Snapshot duplicates are prevented by unique key on:
- `vendor_id + product_id + scraped_at`

Insertion uses `insertOrIgnore`, so repeated same-minute runs do not create duplicate rows.

## Troubleshooting
- `401 Unauthorized`: token mismatch or missing header
- queue not progressing: start `queue:work --queue=scrapers`
- scheduler not triggering: start `schedule:work`
- stale/old docs: run `php artisan l5-swagger:generate`
