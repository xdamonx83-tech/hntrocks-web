<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestProgress extends Model
{
    protected $table = 'quest_user';

    protected $fillable = [
        'quest_id',
        'user_id',
        'progress_count',
        'completed_at',
        'reward_claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_count' => 'integer',
            'completed_at' => 'datetime',
            'reward_claimed_at' => 'datetime',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
