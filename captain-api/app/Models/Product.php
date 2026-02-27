<?php

namespace App\Models;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product Model - Represents a precious metal product
 *
 * @property int $id
 * @property string $name
 * @property string $metal
 * @property string $slug
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|Price[] $prices
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'metal',
        'slug',
    ];

    /**
     * Get the prices for the product.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    /**
     * Scope to filter by metal type.
     */
    public function scopeOfMetal($query, string $metal)
    {
        return $query->where('metal', strtolower($metal));
    }

    /**
     * Get the latest price for this product.
     */
    public function latestPrice()
    {
        return $this->hasOne(Price::class)->latestOfMany('scraped_at');
    }
}
