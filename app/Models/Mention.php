<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Mention extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentioner_id',
        'mentioned_user_id',
        'mentionable_type',
        'mentionable_id',
        'context',
    ];

    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function mentioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioner_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }
}
