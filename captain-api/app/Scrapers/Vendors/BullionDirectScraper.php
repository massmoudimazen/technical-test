<?php

namespace App\Scrapers\Vendors;

use App\Scrapers\AbstractScraper;

class BullionDirectScraper extends AbstractScraper
{
    public function vendorSlug(): string
    {
        return 'bullion-direct';
    }

    public function scrape(): array
    {
        return $this->quotesFor(0.0);
    }

    private function quotesFor(float $vendorAdjustment): array
    {
        $catalog = [
            ['slug' => 'gold-bar-1kg', 'name' => 'Gold Bar 1kg', 'metal' => 'gold', 'buy' => 64000, 'sell' => 67500],
            ['slug' => 'gold-coin-napoleon-20f', 'name' => 'Gold Coin Napoleon 20F', 'metal' => 'gold', 'buy' => 550, 'sell' => 610],
            ['slug' => 'silver-ounce', 'name' => 'Silver Ounce', 'metal' => 'silver', 'buy' => 24, 'sell' => 29],
            ['slug' => 'platinum-bar-100g', 'name' => 'Platinum Bar 100g', 'metal' => 'platinum', 'buy' => 3200, 'sell' => 3480],
            ['slug' => 'palladium-ounce', 'name' => 'Palladium Ounce', 'metal' => 'palladium', 'buy' => 980, 'sell' => 1110],
        ];

        return collect($catalog)->map(function (array $product, int $index) use ($vendorAdjustment) {
            $buy = $this->price((float) $product['buy'], $vendorAdjustment, $index + 1);
            $sell = $this->price((float) $product['sell'], $vendorAdjustment, $index + 3);

            return [
                'product_slug' => $product['slug'],
                'product_name' => $product['name'],
                'metal' => $product['metal'],
                'buy_price' => $buy,
                'sell_price' => max($sell, $buy + 0.5),
                'stock_status' => $this->stockStatus($index),
            ];
        })->values()->all();
    }

    private function price(float $base, float $vendorAdjustment, int $seed): float
    {
        $minute = (int) now()->format('i');
        $wave = sin(($minute + $seed) / 5) * 0.0035;

        return round($base * (1 + $vendorAdjustment + $wave), 2);
    }

    private function stockStatus(int $seed): string
    {
        $minute = (int) now()->format('i');

        if (($minute + $seed) % 17 === 0) {
            return 'unknown';
        }

        return (($minute + $seed) % 13 === 0) ? 'out_of_stock' : 'in_stock';
    }
}
