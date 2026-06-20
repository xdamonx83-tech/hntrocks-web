<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HntMapCashSpotSubmission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'hnt_map_id',
        'hnt_map_marker_id',
        'user_id',
        'reviewed_by',
        'x',
        'y',
        'status',
        'disk',
        'path',
        'public_path',
        'original_name',
        'mime_type',
        'size',
        'submitter_name',
        'submitter_email',
        'ip_hash',
        'user_agent_hash',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(HntMap::class, 'hnt_map_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(HntMapMarker::class, 'hnt_map_marker_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
