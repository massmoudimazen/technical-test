<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CaptainSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    public function show(CaptainSettings $settings): JsonResponse
    {
        $frequency = $settings->scrapeFrequencyMinutes();

        return response()->json([
            'success' => true,
            'data' => [
                'scrape_frequency' => [
                    'value' => $frequency,
                    'unit' => 'minutes',
                    'description' => 'How often scrapers run (in minutes)',
                ],
                'queue_connection' => config('captain.scrape_queue'),
                'queue_name' => config('captain.scrape_queue_name'),
                'request_timeout' => config('captain.request_timeout'),
            ],
        ]);
    }

    public function updateScrapeFrequency(Request $request, CaptainSettings $settings): JsonResponse
    {
        $validated = $request->validate([
            'frequency' => 'required|integer|min:1|max:1440',
        ]);

        $previous = $settings->scrapeFrequencyMinutes();
        $frequency = (int) $validated['frequency'];

        $settings->setScrapeFrequencyMinutes($frequency);

        Log::info('config.scrape_frequency.updated', [
            'old_frequency' => $previous,
            'new_frequency' => $frequency,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Scrape frequency updated to {$frequency} minutes",
            'data' => [
                'scrape_frequency' => [
                    'value' => $frequency,
                    'unit' => 'minutes',
                ],
            ],
        ]);
    }
}
