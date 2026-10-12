<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLoadout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'primary_weapon', 'secondary_weapon', 'tools',
        'consumables', 'traits', 'note', 'visibility', 'sort_order', 'is_active',
        'is_published', 'primary_ammo', 'secondary_ammo',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'consumables' => 'array',
            'traits' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function votes(): HasMany
    {
        return $this->hasMany(UserLoadoutVote::class, 'user_loadout_id');
    }

    public function upVotes(): HasMany
    {
        return $this->votes()->where('value', 1);
    }

    public function downVotes(): HasMany
    {
        return $this->votes()->where('value', -1);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(UserLoadoutComment::class, 'user_loadout_id');
    }
}
