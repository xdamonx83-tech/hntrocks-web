<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Route;
use Throwable;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'category',
        'reason',
        'body',
        'status',
        'assigned_to',
        'resolved_by',
        'resolution_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }


    public function reportableUrl(): ?string
    {
        $target = $this->reportable;

        if (! $target) {
            return null;
        }

        try {
            return match (true) {
                $target instanceof User => Route::has('profile.public') ? route('profile.public', $target) : null,
                $target instanceof FeedPost => Route::has('feed.show') ? route('feed.show', $target) : null,
                $target instanceof FeedComment => $target->post && Route::has('feed.show') ? route('feed.show', $target->post).'#comment-'.$target->id : null,
                $target instanceof Guide => $target->isPublished() && Route::has('guides.show') ? route('guides.show', $target) : null,
                $target instanceof GuideComment => $target->guide?->isPublished() && Route::has('guides.show') ? route('guides.show', $target->guide).'#comment-'.$target->id : null,
                $target instanceof LfgPost => Route::has('lfg.show') ? route('lfg.show', $target) : null,
                $target instanceof Team => Route::has('teams.show') ? route('teams.show', $target) : null,
                $target instanceof TeamLfgPost => Route::has('team-lfg.show') ? route('team-lfg.show', $target) : null,
                $target instanceof MediaAsset => $target->url(),
                $target instanceof Moment => Route::has('moments.show') ? route('moments.show', $target) : null,
                $target instanceof MomentComment => $target->moment && Route::has('moments.show') ? route('moments.show', $target->moment).'#moment-comments' : null,
                $target instanceof Cup => Route::has('cups.show') ? route('cups.show', $target) : null,
                $target instanceof CupSubmission => $target->cup && Route::has('cups.show.section') ? route('cups.show.section', [$target->cup, 'submissions']) : null,
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    public function reporterLabel(): string
    {
        if (! $this->reporter) {
            return 'System';
        }

        return $this->reporter->username ? '@'.$this->reporter->username : ($this->reporter->name ?: 'Nutzer #'.$this->reporter->id);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Offen',
            'in_review' => 'In Prüfung',
            'resolved' => 'Erledigt',
            'rejected' => 'Abgelehnt',
            default => 'Unbekannt',
        };
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'spam' => 'Spam',
            'abuse' => 'Beleidigung / Belästigung',
            'hate' => 'Hassrede',
            'nsfw' => 'Nicht jugendfrei',
            'fraud' => 'Betrug / Scam',
            'cheating' => 'Cheating / Manipulation',
            'privacy' => 'Datenschutz / private Daten',
            default => 'Sonstiges',
        };
    }

    public function reportableLabel(): string
    {
        $model = class_basename((string) $this->reportable_type);

        return match ($model) {
            'User' => 'Nutzer',
            'FeedPost' => 'Feed-Beitrag',
            'FeedComment' => 'Feed-Kommentar',
            'Guide' => 'Guide',
            'GuideComment' => 'Guide-Kommentar',
            'Team' => 'Team',
            'LfgPost' => 'LFG',
            'TeamLfgPost' => 'Team-LFG',
            'MediaAsset' => 'Medium',
            'Moment' => 'Moment',
            'MomentComment' => 'Moment-Kommentar',
            'Cup' => 'Cup',
            'CupSubmission' => 'Cup-Einreichung',
            default => $model !== '' ? $model : 'Unbekannt',
        };
    }
}
