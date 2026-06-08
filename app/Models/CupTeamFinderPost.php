<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupTeamFinderPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'cup_id',
        'user_id',
        'platform',
        'status',
        'message',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function close(): void
    {
        if ($this->status !== 'closed') {
            $this->forceFill([
                'status' => 'closed',
                'closed_at' => now(),
            ])->save();
        }
    }
}
