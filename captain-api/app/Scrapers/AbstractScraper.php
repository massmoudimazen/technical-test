<?php

namespace App\Scrapers;

use App\Scrapers\Contracts\ScraperInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractScraper implements ScraperInterface
{
    protected function get(string $url): ?string
    {
        $timeout = (int) config('captain.request_timeout', 15);
        $connectTimeout = (int) config('captain.connect_timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->retry(3, 250)
                ->userAgent('Captain Scrappin/1.0')
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('scrape.http_failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        } catch (ConnectionException $e) {
            Log::error('scrape.connection_error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('scrape.unexpected_http_error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function post(string $url, array $data = []): ?string
    {
        $timeout = (int) config('captain.request_timeout', 15);
        $connectTimeout = (int) config('captain.connect_timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->retry(3, 250)
                ->userAgent('Captain Scrappin/1.0')
                ->acceptJson()
                ->post($url, $data);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('scrape.http_failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('scrape.unexpected_http_error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractByPattern(string $html, string $pattern): array
    {
        preg_match_all($pattern, $html, $matches);

        return $matches[1] ?? [];
    }
}