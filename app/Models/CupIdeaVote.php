<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupIdeaVote extends Model
{
    use HasFactory;

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
