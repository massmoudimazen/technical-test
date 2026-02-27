<?php

use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProductPriceController;
use App\Http\Controllers\Api\ScraperController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('captain.token')->group(function () {
    Route::get('/health', [HealthController::class, 'index']);

    Route::get('/v1/openapi', function () {
        $generatedPath = storage_path('api-docs/api-docs.yaml');
        $fallbackPath = base_path('docs/openapi.yaml');
        $path = file_exists($generatedPath) ? $generatedPath : $fallbackPath;

        if (!file_exists($path)) {
            return response()->json(['success' => false, 'error' => 'OpenAPI document not found'], 404);
        }

        return response()->file($path, ['Content-Type' => 'application/yaml']);
    });

    Route::prefix('v1')->group(function () {
        Route::get('/health', [HealthController::class, 'index']);

        Route::get('/products', [ProductPriceController::class, 'index']);
        Route::get('/products/latest', [ProductPriceController::class, 'latest']);
        Route::get('/products/{slug}/average', [ProductPriceController::class, 'average']);
        Route::get('/products/{slug}/history', [ProductPriceController::class, 'history']);
        Route::get('/products/{slug}/vendors', [ProductPriceController::class, 'vendors']);

        Route::post('/scrapers/run', [ScraperController::class, 'run']);
        Route::get('/scrapers/status', [ScraperController::class, 'status']);

        Route::get('/config', [ConfigController::class, 'show']);
        Route::put('/config/scrape-frequency', [ConfigController::class, 'updateScrapeFrequency']);
    });
});
