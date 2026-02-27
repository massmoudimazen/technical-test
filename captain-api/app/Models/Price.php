<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'buy_price',
        'sell_price',
        'stock_status',
        'scraped_at',
    ];

    protected $casts = [
        'buy_price' => 'float',
        'sell_price' => 'float',
        'scraped_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Price snapshots are immutable.');
        });
    }

    public function vendor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
