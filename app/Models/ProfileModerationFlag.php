<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileModerationFlag extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED = 'actioned';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'user_id',
        'user_profile_id',
        'field',
        'category',
        'score',
        'reason',
        'excerpt',
        'fingerprint',
        'source',
        'status',
        'detected_at',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'detected_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
