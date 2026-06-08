<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CrownInventoryItem extends Model
{
    protected $fillable = [
        'user_id',
        'shop_item_id',
        'purchased_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(CrownShopItem::class, 'shop_item_id');
    }

    public function equippedItem(): HasOne
    {
        return $this->hasOne(CrownEquippedItem::class, 'inventory_item_id');
    }

    public function isEquipped(): bool
    {
        return $this->relationLoaded('equippedItem') ? $this->equippedItem !== null : $this->equippedItem()->exists();
    }
}
