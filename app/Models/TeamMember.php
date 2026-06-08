<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'status',
        'message',
        'accepted_by',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'owner' => __('ui.role_owner'),
            'officer' => __('ui.role_officer'),
            default => __('ui.role_member'),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('ui.role_status_pending'),
            'declined' => __('ui.role_status_declined'),
            default => __('ui.role_status_active'),
        };
    }
}
