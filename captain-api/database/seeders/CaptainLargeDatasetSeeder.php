<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaptainLargeDatasetSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = collect(range(1, 10))
            ->map(function (int $index) {
                $name = "Vendor {$index}";
                $slug = Str::slug($name);

                return Vendor::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'website' => "https://{$slug}.example",
                        'is_active' => true,
                    ]
                );
            })
            ->values();

        $metals = ['gold', 'silver', 'platinum', 'palladium'];

        $products = collect(range(1, 50))
            ->map(function (int $index) use ($metals) {
                $metal = $metals[$index % count($metals)];
                $name = sprintf('%s Product %02d', ucfirst($metal), $index);
                $slug = Str::slug($name);

                $product = Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'metal' => $metal,
                    ]
                );

                $base = match ($metal) {
                    'gold' => 60000,
                    'silver' => 28,
                    'platinum' => 3200,
                    default => 1200,
                };

                return [
                    'id' => $product->id,
                    'metal' => $metal,
                    'base_buy' => $base + ($index * 12.5),
                ];
            })
            ->values();

        $snapshots = collect(range(0, 23))
            ->map(fn (int $offset) => now()->copy()->subMinutes((23 - $offset) * 5)->startOfMinute())
            ->values();

        $rows = [];
        $createdAt = now();

        foreach ($snapshots as $snapshotIndex => $snapshotAt) {
            foreach ($vendors as $vendorIndex => $vendor) {
                foreach ($products as $productIndex => $product) {
                    $baseBuy = (float) $product['base_buy'];
                    $trend = sin(($snapshotIndex + $productIndex + 3) / 4) * 0.006;
                    $vendorAdjustment = ($vendorIndex - 4.5) * 0.0014;

                    $buy = round($baseBuy * (1 + $trend + $vendorAdjustment), 2);
                    $spread = max(1.0, $baseBuy * 0.03);
                    $sell = round($buy + $spread, 2);

                    $stockStatus = 'in_stock';
                    if (($snapshotIndex + $vendorIndex + $productIndex) % 17 === 0) {
                        $stockStatus = 'out_of_stock';
                    } elseif (($snapshotIndex + $vendorIndex + $productIndex) % 29 === 0) {
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

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('prices')->insertOrIgnore($chunk);
        }
    }
}
