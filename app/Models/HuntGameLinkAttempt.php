<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntGameLinkAttempt extends Model
{
    protected $fillable = ['user_id', 'state_hash', 'expires_at', 'consumed_at'];

    protected $hidden = ['state_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
