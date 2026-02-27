<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CaptainSettings
{
    private const CACHE_PREFIX = 'captain.settings.';
    public const KEY_SCRAPE_FREQUENCY = 'scrape_frequency_minutes';
    public const KEY_LAST_DISPATCH_AT = 'last_scrape_dispatch_at';

    public function scrapeFrequencyMinutes(): int
    {
        return max(1, min(1440, (int) $this->get(self::KEY_SCRAPE_FREQUENCY, (string) config('captain.scrape_interval', 5))));
    }

    public function setScrapeFrequencyMinutes(int $minutes): void
    {
        $minutes = max(1, min(1440, $minutes));
        $this->set(self::KEY_SCRAPE_FREQUENCY, (string) $minutes);
    }

    public function lastDispatchAt(): ?Carbon
    {
        $value = $this->get(self::KEY_LAST_DISPATCH_AT);
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setLastDispatchAt(CarbonInterface $time): void
    {
        $this->set(self::KEY_LAST_DISPATCH_AT, $time->toIso8601String());
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember(self::CACHE_PREFIX . $key, 60, function () use ($key, $default) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public function set(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}
