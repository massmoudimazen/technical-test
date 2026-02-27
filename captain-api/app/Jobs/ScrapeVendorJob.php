<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Vendor;
use App\Scrapers\Contracts\ScraperInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeVendorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public int $maxExceptions = 3;

    protected ScraperInterface $scraper;

    public function __construct(ScraperInterface $scraper)
    {
        $this->scraper = $scraper;
        $this->onQueue(config('captain.scrape_queue_name', 'scrapers'));
    }

    public function handle(): void
    {
        $vendorSlug = $this->scraper->vendorSlug();
        $timestamp = now()->startOfMinute();

        Log::info('scrape.vendor.started', ['vendor' => $vendorSlug, 'scraped_at' => $timestamp->toIso8601String()]);

        try {
            $vendor = Vendor::firstOrCreate(
                ['slug' => $vendorSlug],
                [
                    'name' => ucfirst($vendorSlug),
                    'is_active' => true,
                ]
            );

            if ($vendor->is_active === false) {
                Log::info('scrape.vendor.skipped_inactive', ['vendor' => $vendorSlug]);
                return;
            }

            $rows = $this->scraper->scrape();

            DB::transaction(function () use ($vendor, $rows, $timestamp, $vendorSlug): void {
                foreach ($rows as $row) {
                    $productSlug = (string) ($row['product_slug'] ?? '');
                    if ($productSlug === '') {
                        continue;
                    }

                    $buy = isset($row['buy_price']) ? (float) $row['buy_price'] : null;
                    $sell = isset($row['sell_price']) ? (float) $row['sell_price'] : null;
                    if ($buy === null || $sell === null) {
                        continue;
                    }

                    $product = Product::firstOrCreate(
                        ['slug' => $productSlug],
                        [
                            'name' => $row['product_name'] ?? str_replace('-', ' ', $productSlug),
                            'metal' => $this->detectMetal($productSlug, $row['metal'] ?? null),
                        ]
                    );

                    DB::table('prices')->insertOrIgnore([
                        'vendor_id' => $vendor->id,
                        'product_id' => $product->id,
                        'buy_price' => $buy,
                        'sell_price' => $sell,
                        'stock_status' => $this->normalizeStockStatus($row['stock_status'] ?? null),
                        'scraped_at' => $timestamp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info('scrape.vendor.persisted', [
                    'vendor' => $vendorSlug,
                    'rows_received' => count($rows),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('scrape.vendor.failed', [
                'vendor' => $vendorSlug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('scrape.vendor.job_failed', [
            'vendor' => $this->scraper->vendorSlug(),
            'error' => $exception?->getMessage(),
        ]);
    }

    public function backoff(): array
    {
        return [60, 180, 600, 1200, 1800];
    }

    protected function detectMetal(string $slug, ?string $explicitMetal): string
    {
        if ($explicitMetal) {
            return strtolower($explicitMetal);
        }

        $slug = strtolower($slug);

        if (str_contains($slug, 'silver')) {
            return 'silver';
        }
        if (str_contains($slug, 'gold')) {
            return 'gold';
        }
        if (str_contains($slug, 'platinum')) {
            return 'platinum';
        }
        if (str_contains($slug, 'palladium')) {
            return 'palladium';
        }

        return 'unknown';
    }

    protected function normalizeStockStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'in_stock', 'available', 'instock', '1', 'true' => 'in_stock',
            'out_of_stock', 'unavailable', 'outofstock', '0', 'false' => 'out_of_stock',
            default => 'unknown',
        };
    }
}
