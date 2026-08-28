<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideReputationEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guide_id',
        'event_type',
        'event_key',
        'points',
        'description',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'reversed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
