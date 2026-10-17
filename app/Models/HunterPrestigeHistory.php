<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HunterPrestigeHistory extends Model
{
    protected $table = 'hunter_prestige_history';

    protected $fillable = [
        'user_id',
        'prestige_number',
        'reward_type',
        'shop_item_id',
        'career_xp_at_prestige',
        'cycle_xp_at_prestige',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(CrownShopItem::class, 'shop_item_id');
    }
}
