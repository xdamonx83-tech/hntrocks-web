<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupRandomizerDraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'cup_id',
        'cup_team_id',
        'drawn_by',
        'title',
        'prize_label',
        'eligible_team_ids',
        'eligible_team_count',
        'team_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'eligible_team_ids' => 'array',
            'eligible_team_count' => 'integer',
            'team_snapshot' => 'array',
        ];
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(CupTeam::class, 'cup_team_id');
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    public function winnerName(): string
    {
        $snapshotName = trim((string) data_get($this->team_snapshot, 'name', ''));

        if ($snapshotName !== '') {
            return $snapshotName;
        }

        return $this->team?->displayName() ?: __('ui.cup_randomizer_unknown_team');
    }
}
