<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'visitor_hash',
        'session_hash',
        'ip_hash',
        'country_code',
        'country_name',
        'source',
        'referrer_host',
        'path',
        'route_name',
        'user_agent_hash',
        'is_first_visit',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_first_visit' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
