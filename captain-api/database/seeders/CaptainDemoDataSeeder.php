<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaptainDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = collect([
            ['name' => 'Aurum Market', 'slug' => 'aurum-market', 'website' => 'https://aurum.example', 'is_active' => true],
            ['name' => 'Bullion Direct', 'slug' => 'bullion-direct', 'website' => 'https://bullion.example', 'is_active' => true],
            ['name' => 'Metalis Exchange', 'slug' => 'metalis-exchange', 'website' => 'https://metalis.example', 'is_active' => true],
        ])->mapWithKeys(function (array $vendor) {
            $record = Vendor::query()->updateOrCreate(['slug' => $vendor['slug']], $vendor);
            return [$vendor['slug'] => $record];
        });

        $products = collect([
            ['name' => 'Gold Bar 1kg', 'slug' => 'gold-bar-1kg', 'metal' => 'gold', 'base_buy' => 64000.00, 'base_sell' => 67500.00],
            ['name' => 'Gold Coin Napoleon 20F', 'slug' => 'gold-coin-napoleon-20f', 'metal' => 'gold', 'base_buy' => 550.00, 'base_sell' => 610.00],
            ['name' => 'Silver Ounce', 'slug' => 'silver-ounce', 'metal' => 'silver', 'base_buy' => 24.00, 'base_sell' => 29.00],
            ['name' => 'Platinum Bar 100g', 'slug' => 'platinum-bar-100g', 'metal' => 'platinum', 'base_buy' => 3200.00, 'base_sell' => 3480.00],
            ['name' => 'Palladium Ounce', 'slug' => 'palladium-ounce', 'metal' => 'palladium', 'base_buy' => 980.00, 'base_sell' => 1110.00],
        ])->mapWithKeys(function (array $product) {
            $record = Product::query()->updateOrCreate(
                ['slug' => $product['slug']],
                ['name' => $product['name'], 'metal' => $product['metal']]
            );

            return [$product['slug'] => array_merge($product, ['id' => $record->id])];
        });

        $snapshots = collect(range(0, 11))
            ->map(fn (int $i) => now()->copy()->subMinutes((11 - $i) * 5)->startOfMinute())
            ->values();

        $rows = [];
        $createdAt = now();

        foreach ($snapshots as $snapshotIndex => $snapshotAt) {
            foreach (array_values($vendors->all()) as $vendorIndex => $vendor) {
                foreach (array_values($products->all()) as $productIndex => $product) {
                    $baseBuy = (float) $product['base_buy'];
                    $baseSell = (float) $product['base_sell'];

                    $trend = sin(($snapshotIndex + $productIndex + 1) / 3) * 0.004;
                    $vendorAdjustment = ($vendorIndex - 1) * 0.0025;
                    $buy = round($baseBuy * (1 + $trend + $vendorAdjustment), 2);

                    $spreadBase = max(1.00, $baseSell - $baseBuy);
                    $sell = round($buy + ($spreadBase * (1 + ($vendorAdjustment / 2))), 2);

                    $stockStatus = (($snapshotIndex + $vendorIndex + $productIndex) % 13 === 0) ? 'out_of_stock' : 'in_stock';
                    if (($snapshotIndex + $vendorIndex + $productIndex) % 29 === 0) {
                        $stockStatus = 'unknown';
                    }

                    $rows[] = [
                        'vendor_id' => $vendor->id,
                        'product_id' => $product['id'],
                        'buy_price' => $buy,
                        'sell_price' => $sell,
                        'stock_status' => $stockStatus,
                        'scraped_at' => $snapshotAt,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('prices')->insertOrIgnore($chunk);
        }
    }
}
