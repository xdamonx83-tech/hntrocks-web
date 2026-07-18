<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupIdeaVote extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    protected $fillable = [
        'cup_idea_id',
        'user_id',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(CupIdea::class, 'cup_idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
