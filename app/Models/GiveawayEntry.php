<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiveawayEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'giveaway_id',
        'user_id',
        'source',
        'entries',
        'reason',
        'source_type',
        'source_id',
        'awarded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function giveaway(): BelongsTo
    {
        return $this->belongsTo(Giveaway::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
