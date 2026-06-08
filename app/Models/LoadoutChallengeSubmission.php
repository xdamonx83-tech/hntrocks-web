<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadoutChallengeSubmission extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'loadout_challenge_id',
        'user_id',
        'media_asset_id',
        'outcome',
        'body',
        'status',
        'reviewed_by',
        'reviewed_at',
        'xp_awarded_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'xp_awarded_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => __('ui.loadout_submission_status_pending'),
            self::STATUS_ACCEPTED => __('ui.loadout_submission_status_accepted'),
            self::STATUS_REJECTED => __('ui.loadout_submission_status_rejected'),
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(LoadoutChallenge::class, 'loadout_challenge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? self::statusOptions()[self::STATUS_PENDING];
    }
}
