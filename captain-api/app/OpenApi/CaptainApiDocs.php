<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Captain API",
 *     version="1.0.0",
 *     description="Captain Scrappin API for scraper orchestration, market averages, history, and operational status."
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Current environment"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="CaptainTokenHeader",
 *     type="apiKey",
 *     in="header",
 *     name="X-CAPTAIN-TOKEN",
 *     description="Primary token header required by captain.token middleware."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerToken",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Fallback bearer token. Example: Authorization: Bearer {token}"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="SanctumBearer",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Laravel Sanctum token for /api/user endpoint."
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     required={"success","error"},
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="error", type="string", example="Unauthorized")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     required={"message","errors"},
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="array", @OA\Items(type="string"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AuthenticatedUser",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Captain Admin"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProductListItem",
 *     type="object",
 *     required={"id","name","slug","metal"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Gold Bar 1kg"),
 *     @OA\Property(property="slug", type="string", example="gold-bar-1kg"),
 *     @OA\Property(property="metal", type="string", example="gold")
 * )
 *
 * @OA\Schema(
 *     schema="LatestProductAggregate",
 *     type="object",
 *     required={"product","slug","metal","average_sell_price","average_buy_price","vendor_count","latest_scraped_at"},
 *     @OA\Property(property="product", type="string", example="Gold Bar 1kg"),
 *     @OA\Property(property="slug", type="string", example="gold-bar-1kg"),
 *     @OA\Property(property="metal", type="string", example="gold"),
 *     @OA\Property(property="average_sell_price", type="number", format="float", example=67495.32),
 *     @OA\Property(property="average_buy_price", type="number", format="float", example=63985.14),
 *     @OA\Property(property="vendor_count", type="integer", example=3),
 *     @OA\Property(property="latest_scraped_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ProductAveragePayload",
 *     type="object",
 *     required={"product","slug","metal","average_buy_price","average_sell_price","vendor_count","calculated_at"},
 *     @OA\Property(property="product", type="string", example="Gold Bar 1kg"),
 *     @OA\Property(property="slug", type="string", example="gold-bar-1kg"),
 *     @OA\Property(property="metal", type="string", example="gold"),
 *     @OA\Property(property="average_buy_price", type="number", format="float", example=63985.14),
 *     @OA\Property(property="average_sell_price", type="number", format="float", example=67495.32),
 *     @OA\Property(property="vendor_count", type="integer", example=3),
 *     @OA\Property(property="calculated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ProductHistoryPoint",
 *     type="object",
 *     required={"timestamp","average_sell_price","average_buy_price","vendor_count"},
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="average_sell_price", type="number", format="float", example=67495.32),
 *     @OA\Property(property="average_buy_price", type="number", format="float", example=63985.14),
 *     @OA\Property(property="vendor_count", type="integer", example=3)
 * )
 *
 * @OA\Schema(
 *     schema="VendorLatestPrice",
 *     type="object",
 *     required={"vendor_id","vendor_name","vendor_slug","buy_price","sell_price","stock_status","scraped_at"},
 *     @OA\Property(property="vendor_id", type="integer", example=1),
 *     @OA\Property(property="vendor_name", type="string", example="Aurum Market"),
 *     @OA\Property(property="vendor_slug", type="string", example="aurum-market"),
 *     @OA\Property(property="buy_price", type="number", format="float", example=63980.2),
 *     @OA\Property(property="sell_price", type="number", format="float", example=67480.2),
 *     @OA\Property(property="stock_status", type="string", example="in_stock"),
 *     @OA\Property(property="scraped_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ScraperStatusLatestPrice",
 *     type="object",
 *     required={"product","product_slug","vendor","vendor_slug","buy_price","sell_price","stock_status","scraped_at"},
 *     @OA\Property(property="product", type="string", example="Gold Bar 1kg"),
 *     @OA\Property(property="product_slug", type="string", example="gold-bar-1kg"),
 *     @OA\Property(property="vendor", type="string", example="Aurum Market"),
 *     @OA\Property(property="vendor_slug", type="string", example="aurum-market"),
 *     @OA\Property(property="buy_price", type="number", format="float", example=63980.2),
 *     @OA\Property(property="sell_price", type="number", format="float", example=67480.2),
 *     @OA\Property(property="stock_status", type="string", example="in_stock"),
 *     @OA\Property(property="scraped_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ScraperRunRequest",
 *     type="object",
 *     @OA\Property(property="vendor", type="string", nullable=true, example="aurum-market"),
 *     @OA\Property(property="sync", type="boolean", nullable=true, example=false),
 *     @OA\Property(property="force", type="boolean", nullable=true, example=true)
 * )
 *
 * @OA\Schema(
 *     schema="ScrapeFrequencyUpdateRequest",
 *     type="object",
 *     required={"frequency"},
 *     @OA\Property(property="frequency", type="integer", minimum=1, maximum=1440, example=5)
 * )
 */
final class CaptainApiDocs
{
    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Auth"},
     *     summary="Get authenticated user (Sanctum)",
     *     security={{"SanctumBearer":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user payload",
     *         @OA\JsonContent(ref="#/components/schemas/AuthenticatedUser")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function sanctumUser(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/health",
     *     tags={"Health"},
     *     summary="Health status alias",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Health payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="service", type="string", example="captain-api"),
     *             @OA\Property(property="env", type="string", example="local"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="time", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="services",
     *                 type="object",
     *                 @OA\Property(property="api", type="string", example="ok"),
     *                 @OA\Property(property="database", type="string", example="ok"),
     *                 @OA\Property(property="queue", type="string", example="ok")
     *             ),
     *             @OA\Property(
     *                 property="metrics",
     *                 type="object",
     *                 @OA\Property(property="vendors_count", type="integer", nullable=true),
     *                 @OA\Property(property="products_count", type="integer", nullable=true),
     *                 @OA\Property(property="prices_count", type="integer", nullable=true),
     *                 @OA\Property(property="last_scrape", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="queue_connection", type="string", nullable=true),
     *                 @OA\Property(property="queue_name", type="string", nullable=true),
     *                 @OA\Property(property="jobs_pending", type="integer", nullable=true),
     *                 @OA\Property(property="failed_jobs", type="integer", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Health check failed")
     * )
     */
    public function healthAlias(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/health",
     *     tags={"Health"},
     *     summary="Health status",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Health payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="service", type="string", example="captain-api"),
     *             @OA\Property(property="env", type="string", example="local"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="time", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="services",
     *                 type="object",
     *                 @OA\Property(property="api", type="string", example="ok"),
     *                 @OA\Property(property="database", type="string", example="ok"),
     *                 @OA\Property(property="queue", type="string", example="ok")
     *             ),
     *             @OA\Property(
     *                 property="metrics",
     *                 type="object",
     *                 @OA\Property(property="vendors_count", type="integer", nullable=true),
     *                 @OA\Property(property="products_count", type="integer", nullable=true),
     *                 @OA\Property(property="prices_count", type="integer", nullable=true),
     *                 @OA\Property(property="last_scrape", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="queue_connection", type="string", nullable=true),
     *                 @OA\Property(property="queue_name", type="string", nullable=true),
     *                 @OA\Property(property="jobs_pending", type="integer", nullable=true),
     *                 @OA\Property(property="failed_jobs", type="integer", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Health check failed")
     * )
     */
    public function healthV1(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/openapi",
     *     tags={"Documentation"},
     *     summary="OpenAPI document download",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="OpenAPI YAML document",
     *         @OA\MediaType(
     *             mediaType="application/yaml",
     *             @OA\Schema(type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Document not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function openApiDownload(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="List products",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Products list",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductListItem")),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="count", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function listProducts(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/latest",
     *     tags={"Products"},
     *     summary="Latest aggregated prices for all products",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Latest product aggregate list",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LatestProductAggregate")),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="count", type="integer", example=5),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function latestProducts(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{slug}/average",
     *     tags={"Products"},
     *     summary="Get current market average for a product (latest snapshot per vendor)",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Product slug",
     *         @OA\Schema(type="string", example="gold-bar-1kg")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Average price payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductAveragePayload")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Product not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function productAverage(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{slug}/history",
     *     tags={"Products"},
     *     summary="Get historical average prices for charting",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Product slug",
     *         @OA\Schema(type="string", example="gold-bar-1kg")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historical average series",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="product", type="string", example="Gold Bar 1kg"),
     *                 @OA\Property(property="slug", type="string", example="gold-bar-1kg"),
     *                 @OA\Property(property="metal", type="string", example="gold"),
     *                 @OA\Property(property="history", type="array", @OA\Items(ref="#/components/schemas/ProductHistoryPoint")),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Product not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function productHistory(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{slug}/vendors",
     *     tags={"Products"},
     *     summary="Get latest vendor prices for a product",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Product slug",
     *         @OA\Schema(type="string", example="gold-bar-1kg")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vendor rows payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="product", type="string", example="Gold Bar 1kg"),
     *                 @OA\Property(property="slug", type="string", example="gold-bar-1kg"),
     *                 @OA\Property(property="metal", type="string", example="gold"),
     *                 @OA\Property(property="vendors", type="array", @OA\Items(ref="#/components/schemas/VendorLatestPrice")),
     *                 @OA\Property(property="vendor_count", type="integer", example=3),
     *                 @OA\Property(property="lowest_buy", type="number", format="float", nullable=true),
     *                 @OA\Property(property="highest_buy", type="number", format="float", nullable=true),
     *                 @OA\Property(property="lowest_sell", type="number", format="float", nullable=true),
     *                 @OA\Property(property="highest_sell", type="number", format="float", nullable=true),
     *                 @OA\Property(property="calculated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Product not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function productVendors(): void
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/scrapers/run",
     *     tags={"Scrapers"},
     *     summary="Trigger scraper run",
     *     description="Triggers run:scrapers command. By default this forces immediate dispatch unless force=false is passed.",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(ref="#/components/schemas/ScraperRunRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Scraper trigger accepted",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="exit_code", type="integer", example=0),
     *                 @OA\Property(property="vendor", type="string", nullable=true, example="aurum-market"),
     *                 @OA\Property(property="mode", type="string", example="queued"),
     *                 @OA\Property(property="forced", type="boolean", example=true),
     *                 @OA\Property(property="output", type="string", example="Dispatched: aurum-market"),
     *                 @OA\Property(property="triggered_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation failed", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=500, description="Unable to trigger scrapers", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function runScrapers(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/scrapers/status",
     *     tags={"Scrapers"},
     *     summary="Get queue and scraper execution status",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Scraper status payload",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="queue",
     *                     type="object",
     *                     @OA\Property(property="connection", type="string", example="database"),
     *                     @OA\Property(property="name", type="string", example="scrapers"),
     *                     @OA\Property(property="pending_jobs", type="integer", example=0),
     *                     @OA\Property(property="failed_jobs", type="integer", example=0)
     *                 ),
     *                 @OA\Property(property="scrape_frequency_minutes", type="integer", example=5),
     *                 @OA\Property(property="last_dispatch_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="latest_scraped_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="latest_prices", type="array", @OA\Items(ref="#/components/schemas/ScraperStatusLatestPrice")),
     *                 @OA\Property(property="recent_logs", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="checked_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function scraperStatus(): void
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/config",
     *     tags={"Config"},
     *     summary="Read scraper configuration",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Current scraper config",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="scrape_frequency",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=5),
     *                     @OA\Property(property="unit", type="string", example="minutes"),
     *                     @OA\Property(property="description", type="string", example="How often scrapers run (in minutes)")
     *                 ),
     *                 @OA\Property(property="queue_connection", type="string", example="database"),
     *                 @OA\Property(property="queue_name", type="string", example="scrapers"),
     *                 @OA\Property(property="request_timeout", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function readConfig(): void
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/config/scrape-frequency",
     *     tags={"Config"},
     *     summary="Update scraper frequency",
     *     description="Updates database-backed scrape frequency in minutes.",
     *     security={{"CaptainTokenHeader":{}},{"BearerToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ScrapeFrequencyUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Frequency updated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Scrape frequency updated to 5 minutes"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="scrape_frequency",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=5),
     *                     @OA\Property(property="unit", type="string", example="minutes")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation failed", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function updateScrapeFrequency(): void
    {
    }
}
