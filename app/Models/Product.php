<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    /** At or below this many units the storefront says "Only N left". */
    public const LOW_STOCK_THRESHOLD = 5;

    private const EFFECTIVE_PRICE_SQL = 'CASE WHEN discount_price IS NOT NULL AND discount_price > 0 AND discount_price < price THEN discount_price ELSE price END';

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'ingredients', 'allergens', 'weight', 'price', 'discount_price',
        'stock_quantity', 'is_featured', 'is_bestseller', 'is_gift_box', 'is_active',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_gift_box' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Visible on the storefront: product and its category both active. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeOrderByEffectivePrice(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' '.($direction === 'desc' ? 'desc' : 'asc'));
    }

    public function scopeWhereEffectivePrice(Builder $query, string $operator, float $value): Builder
    {
        $operator = in_array($operator, ['>=', '<=', '>', '<', '='], true) ? $operator : '>=';

        // CAST keeps SQLite from comparing the bound value as text.
        return $query->whereRaw('('.self::EFFECTIVE_PRICE_SQL.') '.$operator.' CAST(? AS DECIMAL(10,2))', [$value]);
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && $this->stock_quantity > 0 && (bool) $this->category?->is_active;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null
            && (float) $this->discount_price > 0
            && (float) $this->discount_price < (float) $this->price;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->has_discount ? (float) $this->discount_price : (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        return $this->has_discount
            ? (int) round((1 - ((float) $this->discount_price / (float) $this->price)) * 100)
            : 0;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->in_stock && $this->stock_quantity <= self::LOW_STOCK_THRESHOLD;
    }

    public function getImageUrlAttribute(): string
    {
        $image = $this->relationLoaded('primaryImage')
            ? $this->primaryImage
            : ($this->relationLoaded('images') ? $this->images->first() : $this->primaryImage()->first());

        return media_url($image?->path);
    }

    public function getUrlAttribute(): string
    {
        return route('products.show', $this->slug);
    }
}
