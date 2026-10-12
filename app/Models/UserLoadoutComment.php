<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoadoutComment extends Model
{
    protected $fillable = ['user_id', 'body'];

    public function loadout(): BelongsTo
    {
        return $this->belongsTo(UserLoadout::class, 'user_loadout_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
