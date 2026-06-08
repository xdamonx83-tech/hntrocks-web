<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LfgApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'lfg_post_id',
        'user_id',
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
        return $this->belongsTo(LfgPost::class, 'lfg_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('ui.lfg_application_pending'),
            'accepted' => __('ui.lfg_application_accepted'),
            'rejected' => __('ui.lfg_application_rejected'),
            'cancelled' => __('ui.lfg_application_cancelled'),
            default => __('ui.lfg_application_unknown'),
        };
    }
}
