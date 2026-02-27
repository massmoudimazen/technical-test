<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * ScraperManager - Manages and auto-discovers scraper implementations
 *
 * Provides auto-discovery of scraper classes from the Vendors directory.
 * Scraper classes must implement ScraperInterface to be registered.
 */
class ScraperManager
{
    /**
     * Cache of discovered scrapers.
     */
    protected ?Collection $scrapers = null;

    /**
     * Get all registered scrapers.
     *
     * @return Collection<int, ScraperInterface>
     */
    public function all(): Collection
    {
        if ($this->scrapers === null) {
            $this->scrapers = $this->discover();
        }

        return $this->scrapers;
    }

    /**
     * Get a scraper by its vendor slug.
     */
    public function get(string $slug): ?ScraperInterface
    {
        return $this->all()->first(fn (ScraperInterface $scraper) => $scraper->vendorSlug() === $slug);
    }

    /**
     * Discover scrapers from the Vendors directory.
     *
     * @return Collection<int, ScraperInterface>
     */
    protected function discover(): Collection
    {
        $scrapers = new Collection();
        $path = base_path('app/Scrapers/Vendors');

        if (!File::exists($path)) {
            return $scrapers;
        }

        $files = File::files($path);

        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();

            // Skip non-PHP files
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Build the fully qualified class name
            $fqcn = "App\\Scrapers\\Vendors\\{$className}";

            // Skip if class doesn't exist
            if (!class_exists($fqcn)) {
                continue;
            }

            // Check if it implements ScraperInterface
            $reflection = new \ReflectionClass($fqcn);
            if ($reflection->implementsInterface(ScraperInterface::class)) {
                $scraper = $reflection->newInstance();

                if ($scraper instanceof ScraperInterface) {
                    $scrapers->push($scraper);
                }
            }
        }

        return $scrapers;
    }

    /**
     * Get all vendor slugs.
     *
     * @return Collection<int, string>
     */
    public function slugs(): Collection
    {
        return $this->all()->map(fn (ScraperInterface $scraper) => $scraper->vendorSlug());
    }

    /**
     * Check if a vendor scraper exists.
     */
    public function has(string $slug): bool
    {
        return $this->all()->contains(fn (ScraperInterface $scraper) => $scraper->vendorSlug() === $slug);
    }
}
