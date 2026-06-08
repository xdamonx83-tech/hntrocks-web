<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MomentStudioProject extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'moment_id',
        'status',
        'visibility',
        'caption',
        'description',
        'timeline',
        'source_media_asset_ids',
        'output_media_asset_id',
        'total_duration_seconds',
        'error_message',
        'queued_at',
        'started_at',
        'finished_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'timeline' => 'array',
            'source_media_asset_ids' => 'array',
            'total_duration_seconds' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moment(): BelongsTo
    {
        return $this->belongsTo(Moment::class);
    }

    public function outputMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'output_media_asset_id');
    }
}
