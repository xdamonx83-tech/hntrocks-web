<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HntMapMarkerVote extends Model
{
    use HidesBlockedUsers;
    protected $fillable = [
        'user_id',
        'visitor_hash',
        'ip_hash',
        'user_agent_hash',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'value' => 'integer',
        ];
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(HntMapMarker::class, 'hnt_map_marker_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
