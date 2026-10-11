<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoadoutVote extends Model
{
    protected $fillable = ['user_id', 'value'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }

    public function loadout(): BelongsTo
    {
        return $this->belongsTo(UserLoadout::class, 'user_loadout_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
