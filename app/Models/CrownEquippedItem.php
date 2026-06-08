<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrownEquippedItem extends Model
{
    protected $fillable = [
        'user_id',
        'slot',
        'inventory_item_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(CrownInventoryItem::class, 'inventory_item_id');
    }
}
