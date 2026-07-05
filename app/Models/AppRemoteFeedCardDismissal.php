<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppRemoteFeedCardDismissal extends Model
{
    protected $fillable = [
        'user_id',
        'remote_card_id',
        'remote_id',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return ['dismissed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(AppRemoteFeedCard::class, 'remote_card_id');
    }
}
