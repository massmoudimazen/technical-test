<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Price;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductPriceController extends Controller
{
    protected const CACHE_TTL = 120;

    public function index(): JsonResponse
    {
        $products = Product::query()
            ->select('id', 'name', 'slug', 'metal')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => ['count' => $products->count()],
        ]);
    }

    public function average(string $slug): JsonResponse
    {
        return Cache::remember("product.average.{$slug}", self::CACHE_TTL, function () use ($slug) {
            $product = Product::query()->where('slug', $slug)->first();
            if (!$product) {
                return response()->json(['success' => false, 'error' => 'Product not found'], 404);
            }

            $latestRows = $this->latestVendorRowsQuery($product->id);

            $summary = DB::query()
                ->fromSub($latestRows, 'latest_prices')
                ->selectRaw('AVG(buy_price) as average_buy_price, AVG(sell_price) as average_sell_price, COUNT(*) as vendor_count')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product->name,
                    'slug' => $product->slug,
                    'metal' => $product->metal,
                    'average_buy_price' => round((float) ($summary->average_buy_price ?? 0), 2),
                    'average_sell_price' => round((float) ($summary->average_sell_price ?? 0), 2),
                    'vendor_count' => (int) ($summary->vendor_count ?? 0),
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    public function latest(): JsonResponse
    {
        return Cache::remember('products.latest', self::CACHE_TTL, function () {
            $latestIds = Price::query()
                ->selectRaw('MAX(id) as id')
                ->groupBy('product_id', 'vendor_id');

            $rows = DB::query()
                ->fromSub(
                    Price::query()
                        ->whereIn('id', $latestIds)
                        ->selectRaw('product_id, AVG(sell_price) as average_sell_price, AVG(buy_price) as average_buy_price, COUNT(*) as vendor_count, MAX(COALESCE(scraped_at, created_at)) as latest_scraped_at')
                        ->groupBy('product_id'),
                    'latest_prices'
                )
                ->join('products', 'products.id', '=', 'latest_prices.product_id')
                ->orderBy('products.name')
                ->get([
                    'products.name as product',
                    'products.slug',
                    'products.metal',
                    'latest_prices.average_sell_price',
                    'latest_prices.average_buy_price',
                    'latest_prices.vendor_count',
                    'latest_prices.latest_scraped_at',
                ])
                ->map(function ($row) {
                    return [
                        'product' => $row->product,
                        'slug' => $row->slug,
                        'metal' => $row->metal,
                        'average_sell_price' => round((float) $row->average_sell_price, 2),
                        'average_buy_price' => round((float) $row->average_buy_price, 2),
                        'vendor_count' => (int) $row->vendor_count,
                        'latest_scraped_at' => $row->latest_scraped_at ? Carbon::parse($row->latest_scraped_at, 'UTC')->toIso8601String() : null,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $rows,
                'meta' => [
                    'count' => $rows->count(),
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    public function history(string $slug): JsonResponse
    {
        return Cache::remember("product.history.{$slug}", self::CACHE_TTL, function () use ($slug) {
            $product = Product::query()->where('slug', $slug)->first();
            if (!$product) {
                return response()->json(['success' => false, 'error' => 'Product not found'], 404);
            }

            $history = Price::query()
                ->where('product_id', $product->id)
                ->selectRaw("DATE_FORMAT(COALESCE(scraped_at, created_at), '%Y-%m-%d %H:%i:00') as bucket_at")
                ->selectRaw('AVG(sell_price) as average_sell_price')
                ->selectRaw('AVG(buy_price) as average_buy_price')
                ->selectRaw('COUNT(DISTINCT vendor_id) as vendor_count')
                ->groupBy('bucket_at')
                ->orderBy('bucket_at')
                ->get()
                ->map(function ($row) {
                    return [
                        'timestamp' => Carbon::parse($row->bucket_at, 'UTC')->toIso8601String(),
                        'average_sell_price' => round((float) $row->average_sell_price, 2),
                        'average_buy_price' => round((float) $row->average_buy_price, 2),
                        'vendor_count' => (int) $row->vendor_count,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product->name,
                    'slug' => $product->slug,
                    'metal' => $product->metal,
                    'history' => $history,
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    public function vendors(string $slug): JsonResponse
    {
        return Cache::remember("product.vendors.{$slug}", self::CACHE_TTL, function () use ($slug) {
            $product = Product::query()->where('slug', $slug)->first();
            if (!$product) {
                return response()->json(['success' => false, 'error' => 'Product not found'], 404);
            }

            $vendors = Price::query()
                ->with('vendor:id,name,slug')
                ->whereIn('id', $this->latestVendorRowsQuery($product->id)->select('id'))
                ->orderBy('sell_price')
                ->get()
                ->map(function (Price $price) {
                    return [
                        'vendor_id' => $price->vendor_id,
                        'vendor_name' => $price->vendor?->name,
                        'vendor_slug' => $price->vendor?->slug,
                        'buy_price' => (float) $price->buy_price,
                        'sell_price' => (float) $price->sell_price,
                        'stock_status' => $price->stock_status,
                        'scraped_at' => ($price->scraped_at ?? $price->created_at)?->toIso8601String(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product->name,
                    'slug' => $product->slug,
                    'metal' => $product->metal,
                    'vendors' => $vendors,
                    'vendor_count' => $vendors->count(),
                    'lowest_buy' => $vendors->min('buy_price'),
                    'highest_buy' => $vendors->max('buy_price'),
                    'lowest_sell' => $vendors->min('sell_price'),
                    'highest_sell' => $vendors->max('sell_price'),
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    private function latestVendorRowsQuery(int $productId)
    {
        $latestByVendorTimestamp = Price::query()
            ->where('product_id', $productId)
            ->selectRaw('vendor_id, MAX(COALESCE(scraped_at, created_at)) as latest_scraped_at')
            ->groupBy('vendor_id');

        $latestIds = Price::query()
            ->from('prices as p')
            ->joinSub($latestByVendorTimestamp, 'latest', function ($join) {
                $join->on('p.vendor_id', '=', 'latest.vendor_id')
                    ->whereRaw('COALESCE(p.scraped_at, p.created_at) = latest.latest_scraped_at');
            })
            ->where('p.product_id', $productId)
            ->selectRaw('MAX(p.id) as id')
            ->groupBy('p.vendor_id');

        return Price::query()->whereIn('id', $latestIds);
    }
}
