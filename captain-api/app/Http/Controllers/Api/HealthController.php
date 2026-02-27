<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * System health endpoint.
     */
    public function index()
    {
        $response = [
            'status'  => 'ok',
            'service' => 'captain-api',
            'env'     => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'time'    => now()->toIso8601String(),
            'services' => [
                'api'      => 'ok',
                'database' => 'unknown',
                'queue'    => 'unknown',
            ],
            'metrics' => [],
        ];

        try {

            /*
            |--------------------------------------------------------------------------
            | Database Check
            |--------------------------------------------------------------------------
            */
            DB::connection()->getPdo();
            $response['services']['database'] = 'ok';

            /*
            |--------------------------------------------------------------------------
            | Metrics (cached for performance)
            |--------------------------------------------------------------------------
            */
            $response['metrics'] = Cache::remember(
                'health.metrics',
                now()->addSeconds(30),
                function () {

                    $lastPrice = Price::latest('scraped_at')->first();
                    $jobsPending = DB::getSchemaBuilder()->hasTable('jobs')
                        ? (int) DB::table('jobs')->where('queue', config('captain.scrape_queue_name', 'scrapers'))->count()
                        : null;
                    $failedJobs = DB::getSchemaBuilder()->hasTable('failed_jobs')
                        ? (int) DB::table('failed_jobs')->count()
                        : null;

                    return [
                        'vendors_count'  => Vendor::count(),
                        'products_count' => Product::count(),
                        'prices_count'   => Price::count(),
                        'last_scrape'    => $lastPrice?->scraped_at?->toIso8601String(),
                        'queue_connection' => config('queue.default'),
                        'queue_name' => config('captain.scrape_queue_name', 'scrapers'),
                        'jobs_pending' => $jobsPending,
                        'failed_jobs' => $failedJobs,
                    ];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Queue Check
            |--------------------------------------------------------------------------
            */
            try {

                $queueDriver = config('queue.default');

                if ($queueDriver === 'redis') {

                    Cache::store('redis')->put('health_check', true, 1);
                    $response['services']['queue'] = 'ok';

                } elseif ($queueDriver === 'database') {

                    $response['services']['queue'] = DB::getSchemaBuilder()->hasTable('jobs') ? 'ok' : 'degraded';

                } else {

                    $response['services']['queue'] = 'unknown';

                }

            } catch (\Throwable $e) {

                $response['services']['queue'] = 'degraded';

                Log::warning('Queue health degraded', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json($response);

        } catch (\Throwable $e) {

            Log::error('Health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'service' => 'captain-api',
                'message' => 'Database connection failed',
                'time'    => now()->toIso8601String(),
            ], 500);
        }
    }
}
