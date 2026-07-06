<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrownShopItem extends Model
{
    protected $fillable = [
        'key',
        'type',
        'slot',
        'name_de',
        'name_en',
        'description_de',
        'description_en',
        'price',
        'sale_price',
        'rarity',
        'icon',
        'preview_class',
        'image_path',
        'is_active',
        'is_limited',
        'available_from',
        'available_until',
        'offer_starts_at',
        'offer_ends_at',
        'badge',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sale_price' => 'integer',
            'is_active' => 'boolean',
            'is_limited' => 'boolean',
            'available_from' => 'datetime',
            'available_until' => 'datetime',
            'offer_starts_at' => 'datetime',
            'offer_ends_at' => 'datetime',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(CrownInventoryItem::class, 'shop_item_id');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $availability): void {
                $availability->whereNull('available_from')->orWhere('available_from', '<=', now());
            })
            ->where(function (Builder $availability): void {
                $availability->whereNull('available_until')->orWhere('available_until', '>=', now());
            });
    }

    public function displayName(): string
    {
        $locale = app()->getLocale();

        return (string) ($locale === 'en' ? ($this->name_en ?: $this->name_de) : ($this->name_de ?: $this->name_en));
    }

    public function displayDescription(): string
    {
        $locale = app()->getLocale();

        return (string) ($locale === 'en' ? ($this->description_en ?: $this->description_de) : ($this->description_de ?: $this->description_en));
    }

    public function isActivatable(): bool
    {
        return filled($this->slot);
    }

    public function offerActive(): bool
    {
        $originalPrice = (int) $this->price;
        $salePrice = $this->sale_price;

        if ($salePrice === null || (int) $salePrice <= 0 || (int) $salePrice >= $originalPrice) {
            return false;
        }

        if ($this->offer_starts_at && $this->offer_starts_at->isFuture()) {
            return false;
        }

        if ($this->offer_ends_at && $this->offer_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function effectivePrice(): int
    {
        return $this->offerActive() ? (int) $this->sale_price : (int) $this->price;
    }
}
