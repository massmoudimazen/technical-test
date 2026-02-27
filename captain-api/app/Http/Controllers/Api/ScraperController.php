<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Services\CaptainSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScraperController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor' => 'nullable|string|max:120',
            'sync' => 'nullable|boolean',
            'force' => 'nullable|boolean',
        ]);

        $vendor = isset($validated['vendor']) ? (string) $validated['vendor'] : null;
        $sync = (bool) ($validated['sync'] ?? false);
        $force = array_key_exists('force', $validated) ? (bool) $validated['force'] : true;

        $arguments = [
            '--force' => $force,
        ];

        if ($sync) {
            $arguments['--sync'] = true;
        }

        if ($vendor !== null && $vendor !== '') {
            $arguments['--vendor'] = $vendor;
        }

        try {
            $code = Artisan::call('run:scrapers', $arguments);
            $output = trim(Artisan::output());

            Log::info('api.scrapers.run.triggered', [
                'vendor' => $vendor,
                'sync' => $sync,
                'force' => $force,
                'code' => $code,
            ]);

            return response()->json([
                'success' => $code === 0,
                'data' => [
                    'exit_code' => $code,
                    'vendor' => $vendor,
                    'mode' => $sync ? 'sync' : 'queued',
                    'forced' => $force,
                    'output' => $output,
                    'triggered_at' => now()->toIso8601String(),
                ],
            ], $code === 0 ? 200 : 500);
        } catch (Throwable $e) {
            Log::error('api.scrapers.run.failed', [
                'vendor' => $vendor,
                'sync' => $sync,
                'force' => $force,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to trigger scrapers',
            ], 500);
        }
    }

    public function status(CaptainSettings $settings): JsonResponse
    {
        $queueName = (string) config('captain.scrape_queue_name', 'scrapers');
        $queueConnection = (string) config('captain.scrape_queue', config('queue.default', 'sync'));

        $pending = 0;
        $failed = 0;

        if ($this->tableExists('jobs')) {
            $pending = (int) DB::table('jobs')->where('queue', $queueName)->count();
        }

        if ($this->tableExists('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')->count();
        }

        $latestPrices = $this->latestPriceRows();

        return response()->json([
            'success' => true,
            'data' => [
                'queue' => [
                    'connection' => $queueConnection,
                    'name' => $queueName,
                    'pending_jobs' => $pending,
                    'failed_jobs' => $failed,
                ],
                'scrape_frequency_minutes' => $settings->scrapeFrequencyMinutes(),
                'last_dispatch_at' => optional($settings->lastDispatchAt())->toIso8601String(),
                'latest_scraped_at' => $this->latestScrapedAt(),
                'latest_prices' => $latestPrices,
                'recent_logs' => $this->recentScraperLogs(),
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function latestPriceRows(): array
    {
        $latestIds = Price::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id', 'vendor_id');

        return DB::query()
            ->fromSub(Price::query()->whereIn('id', $latestIds), 'prices')
            ->join('products', 'products.id', '=', 'prices.product_id')
            ->join('vendors', 'vendors.id', '=', 'prices.vendor_id')
            ->orderBy('products.name')
            ->orderBy('vendors.name')
            ->get([
                'products.name as product',
                'products.slug as product_slug',
                'vendors.name as vendor',
                'vendors.slug as vendor_slug',
                'prices.buy_price',
                'prices.sell_price',
                'prices.stock_status',
                'prices.scraped_at',
            ])
            ->map(function ($row) {
                return [
                    'product' => $row->product,
                    'product_slug' => $row->product_slug,
                    'vendor' => $row->vendor,
                    'vendor_slug' => $row->vendor_slug,
                    'buy_price' => (float) $row->buy_price,
                    'sell_price' => (float) $row->sell_price,
                    'stock_status' => (string) $row->stock_status,
                    'scraped_at' => $row->scraped_at ? \Illuminate\Support\Carbon::parse($row->scraped_at, 'UTC')->toIso8601String() : null,
                ];
            })
            ->values()
            ->all();
    }

    private function latestScrapedAt(): ?string
    {
        $latest = Price::query()
            ->selectRaw('MAX(COALESCE(scraped_at, created_at)) as latest_scraped_at')
            ->value('latest_scraped_at');

        if (!$latest) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($latest, 'UTC')->toIso8601String();
    }

    private function recentScraperLogs(int $limit = 30): array
    {
        $path = storage_path('logs/laravel.log');
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $lines = $this->tailLines($path, max($limit * 6, 120));
        if (empty($lines)) {
            return [];
        }

        return collect($lines)
            ->filter(function (string $line) {
                return str_contains($line, 'scrape.') || str_contains($line, 'scrapers.') || str_contains($line, 'api.scrapers.');
            })
            ->take(-$limit)
            ->values()
            ->all();
    }

    /**
     * Read the last N lines from a file without loading it fully into memory.
     *
     * @return array<int, string>
     */
    private function tailLines(string $path, int $maxLines): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $lines = [];
        $buffer = '';
        $cursor = -1;

        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);

        if ($fileSize === false || $fileSize <= 0) {
            fclose($handle);
            return [];
        }

        while (count($lines) < $maxLines && ($fileSize + $cursor) >= 0) {
            fseek($handle, $cursor, SEEK_END);
            $char = fgetc($handle);

            if ($char === "\n") {
                if ($buffer !== '') {
                    $lines[] = strrev($buffer);
                    $buffer = '';
                }
            } elseif ($char !== "\r" && $char !== false) {
                $buffer .= $char;
            }

            $cursor--;
        }

        if ($buffer !== '') {
            $lines[] = strrev($buffer);
        }

        fclose($handle);

        return array_reverse($lines);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
