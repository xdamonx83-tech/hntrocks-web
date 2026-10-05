<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntGameConnection extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'provider_user_id', 'provider_name',
        'hunt_stats', 'sync_status', 'sync_error', 'last_synced_at',
    ];

    protected $casts = [
        'hunt_stats' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
