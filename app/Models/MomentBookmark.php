<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MomentBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'moment_id',
        'user_id',
    ];

    public function moment(): BelongsTo
    {
        return $this->belongsTo(Moment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
