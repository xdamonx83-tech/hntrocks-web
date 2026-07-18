<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CupChatMessage extends Model
{
    use HasFactory;
    use HidesBlockedUsers;
    use SoftDeletes;

    protected $fillable = [
        'cup_id',
        'user_id',
        'body',
    ];

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
