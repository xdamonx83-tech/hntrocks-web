<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamLfgApplication extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    protected $fillable = [
        'team_lfg_post_id',
        'user_id',
        'team_id',
        'message',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(TeamLfgPost::class, 'team_lfg_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('ui.team_lfg_application_pending'),
            'accepted' => __('ui.team_lfg_application_accepted'),
            'rejected' => __('ui.team_lfg_application_rejected'),
            'cancelled' => __('ui.team_lfg_application_cancelled'),
            default => __('ui.team_lfg_application_unknown'),
        };
    }
}
