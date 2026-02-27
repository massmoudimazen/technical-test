<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeVendorJob;
use App\Scrapers\ScraperManager;
use App\Services\CaptainSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunScrapers extends Command
{
    protected $signature = 'run:scrapers
                                {--sync : Run synchronously instead of queuing}
                                {--vendor= : Run only for a specific vendor slug}
                                {--force : Ignore frequency guard and run now}';

    protected $description = 'Dispatch all vendor scraper jobs to the queue';

    public function handle(CaptainSettings $settings): int
    {
        $lock = Cache::lock('scrapers.dispatch.lock', 55);

        if (!$lock->get()) {
            $this->info('Scraper dispatch skipped: lock already held.');
            return Command::SUCCESS;
        }

        try {
            if (!$this->option('force') && !$this->isDue($settings)) {
                $this->info('Scraper dispatch skipped: frequency guard not due yet.');
                return Command::SUCCESS;
            }

            $manager = app(ScraperManager::class);
            $sync = (bool) $this->option('sync');
            $specificVendor = $this->option('vendor');

            if ($specificVendor) {
                $scrapers = collect([$manager->get((string) $specificVendor)])->filter();
            } else {
                $scrapers = $manager->all();
            }

            if ($scrapers->isEmpty()) {
                $this->warn('No scrapers found to run.');
                return Command::SUCCESS;
            }

            $settings->setLastDispatchAt(now()->startOfMinute());

            $dispatchedCount = 0;

            foreach ($scrapers as $scraper) {
                $vendorSlug = $scraper->vendorSlug();

                try {
                    if ($sync) {
                        (new ScrapeVendorJob($scraper))->handle();
                        $this->info("Completed: {$vendorSlug}");
                    } else {
                        ScrapeVendorJob::dispatch($scraper)
                            ->onQueue(config('captain.scrape_queue_name', 'scrapers'))
                            ->onConnection(config('captain.scrape_queue', 'database'));

                        $this->info("Dispatched: {$vendorSlug}");
                    }

                    $dispatchedCount++;
                } catch (\Throwable $e) {
                    Log::error('scrapers.dispatch.vendor_failed', [
                        'vendor' => $vendorSlug,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('scrapers.dispatch.completed', [
                'dispatched_count' => $dispatchedCount,
                'found_count' => $scrapers->count(),
                'mode' => $sync ? 'sync' : 'queued',
            ]);

            return Command::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function isDue(CaptainSettings $settings): bool
    {
        $frequency = $settings->scrapeFrequencyMinutes();
        $last = $settings->lastDispatchAt();

        if ($last === null) {
            return true;
        }

        return $last->diffInMinutes(now()) >= $frequency;
    }
}
