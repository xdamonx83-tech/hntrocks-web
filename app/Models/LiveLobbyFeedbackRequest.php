<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LiveLobbyFeedbackRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'completed', 'dismissed', 'expired'];

    protected $fillable = [
        'public_id', 'live_lobby_id', 'reviewer_id', 'target_user_id', 'status',
        'available_at', 'expires_at', 'notified_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (LiveLobbyFeedbackRequest $request): void {
            $request->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'expires_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(LiveLobby::class, 'live_lobby_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(LiveLobbyFeedback::class, 'feedback_request_id');
    }
}
