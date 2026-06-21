<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HntMapMarkerComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hnt_map_marker_id',
        'user_id',
        'body',
    ];

    public function marker(): BelongsTo
    {
        return $this->belongsTo(HntMapMarker::class, 'hnt_map_marker_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
