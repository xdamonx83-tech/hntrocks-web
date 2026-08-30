<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'streamer_user_id',
        'user_id',
        'stream_key',
        'body',
    ];

    public function streamer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'streamer_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
