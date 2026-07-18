<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MomentReaction extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    protected $fillable = [
        'moment_id',
        'user_id',
        'type',
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
