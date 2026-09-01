<?php

namespace App\Models\Arcade;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArcadeGameRelease extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_RETIRED = 'retired';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'published_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo { return $this->belongsTo(ArcadeGame::class, 'game_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function launchTickets(): HasMany { return $this->hasMany(ArcadeLaunchTicket::class, 'release_id'); }
}
