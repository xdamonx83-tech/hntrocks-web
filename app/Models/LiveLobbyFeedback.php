<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveLobbyFeedback extends Model
{
    use HasFactory;

    protected $table = 'live_lobby_feedback';

    protected $fillable = [
        'feedback_request_id', 'live_lobby_id', 'reviewer_id', 'target_user_id',
        'positive_tags', 'private_flags', 'comment',
    ];

    protected function casts(): array
    {
        return [
            'positive_tags' => 'array',
            'private_flags' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(LiveLobbyFeedbackRequest::class, 'feedback_request_id');
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
}
