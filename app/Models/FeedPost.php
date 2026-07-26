<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FeedPost extends Model
{
    use HasFactory;
    use HidesBlockedUsers;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'cup_team_id',
        'shared_post_id',
        'body',
        'background_style',
        'feeling_key',
        'gif_provider',
        'gif_id',
        'gif_url',
        'gif_preview_url',
        'gif_title',
        'gif_source_url',
        'source_language',
        'ai_user_declared',
        'ai_detected_possible',
        'ai_detection_confidence',
        'ai_detection_reason',
        'ai_detection_source',
        'ai_detection_model',
        'ai_detection_error',
        'ai_detection_checked_at',
        'admin_confirmed_ai',
        'admin_ai_reviewed_at',
        'admin_ai_reviewed_by_user_id',
        'visibility',
        'status',
        'is_pinned',
        'pinned_at',
        'pinned_by_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'ai_user_declared' => 'boolean',
        'ai_detected_possible' => 'boolean',
        'ai_detection_confidence' => 'float',
        'ai_detection_checked_at' => 'datetime',
        'admin_confirmed_ai' => 'boolean',
        'admin_ai_reviewed_at' => 'datetime',
        'pinned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function cupTeam(): BelongsTo
    {
        return $this->belongsTo(CupTeam::class);
    }

    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by_user_id');
    }

    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'shared_post_id');
    }

    public function sharedByPosts(): HasMany
    {
        return $this->hasMany(self::class, 'shared_post_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(FeedPostMedia::class)->orderBy('sort_order');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(FeedPostPoll::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedComment::class)->latest();
    }

    public function previewComments(): HasMany
    {
        return $this->hasMany(FeedComment::class)
            ->whereNull('parent_id')
            ->latest()
            ->limit(2);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class);
    }

    public function previewReactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class)
            ->latest()
            ->limit(3);
    }


    public function translations(): HasMany
    {
        return $this->hasMany(FeedPostTranslation::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(FeedBookmark::class);
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    public function viewerReaction(): HasOne
    {
        return $this->hasOne(FeedReaction::class)->where('user_id', auth()->id());
    }

    public function viewerBookmark(): HasOne
    {
        return $this->hasOne(FeedBookmark::class)->where('user_id', auth()->id());
    }

    public function isTeamPost(): bool
    {
        return $this->team_id !== null;
    }

    public function isSharedPost(): bool
    {
        return $this->shared_post_id !== null;
    }

    public function canBeViewedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isSharedPost()) {
            $this->loadMissing('sharedPost');

            if (! $this->sharedPost || ! $this->sharedPost->canBeViewedBy($user)) {
                return false;
            }
        }

        if ($this->isTeamPost()) {
            $this->loadMissing('team.members');

            if (! $this->team) {
                return false;
            }

            if ($this->team->visibility !== 'private') {
                return true;
            }

            return $this->team->isActiveMember($user);
        }

        if ($this->visibility === 'private') {
            return (int) $this->user_id === (int) $user->id;
        }

        return true;
    }

    public function permalink(?FeedComment $comment = null): string
    {
        $url = route('feed.show', $this);

        if ($comment) {
            $url .= '#comment-' . $comment->id;
        }

        return $url;
    }

    public function redirectRoute(): string
    {
        $this->loadMissing('team');

        if ($this->team) {
            return route('teams.show', $this->team);
        }

        return route('feed.index');
    }

    public function excerpt(int $limit = 140): string
    {
        return Str::limit(trim(strip_tags((string) $this->body)), $limit);
    }

    public function visibilityLabel(): string
    {
        $label = match ($this->visibility) {
            'team' => __('ui.visibility_team'),
            'followers' => __('ui.visibility_followers'),
            'private' => __('ui.visibility_private'),
            default => __('ui.visibility_public'),
        };

        if (! request()->boolean('data')) {
            return $label;
        }

        $feeling = $this->feelingMeta();

        if (! $feeling) {
            return $label;
        }

        $verb = app()->getLocale() === 'de' ? 'ist' : 'is';

        return trim($label.' · '.$verb.' '.$feeling['label'].' '.$feeling['emoji']);
    }


    public static function allowedFeelings(): array
    {
        return [
            'happy' => ['emoji' => '😊', 'label_de' => 'glücklich', 'label_en' => 'happy'],
            'excited' => ['emoji' => '🔥', 'label_de' => 'motiviert', 'label_en' => 'excited'],
            'focused' => ['emoji' => '🎯', 'label_de' => 'fokussiert', 'label_en' => 'focused'],
            'chill' => ['emoji' => '🌙', 'label_de' => 'entspannt', 'label_en' => 'chill'],
            'tired' => ['emoji' => '😴', 'label_de' => 'müde', 'label_en' => 'tired'],
            'salty' => ['emoji' => '🧂', 'label_de' => 'salzig', 'label_en' => 'salty'],
        ];
    }

    public static function normalizeFeelingKey(?string $feeling): ?string
    {
        $feeling = trim((string) $feeling);

        if ($feeling === '' || $feeling === 'none') {
            return null;
        }

        return array_key_exists($feeling, self::allowedFeelings()) ? $feeling : null;
    }

    public function feelingMeta(): ?array
    {
        $key = self::normalizeFeelingKey($this->feeling_key);

        if (! $key) {
            return null;
        }

        $meta = self::allowedFeelings()[$key] ?? null;

        if (! $meta) {
            return null;
        }

        $locale = app()->getLocale();

        return [
            'key' => $key,
            'emoji' => $meta['emoji'],
            'label' => $locale === 'de' ? $meta['label_de'] : $meta['label_en'],
            'label_de' => $meta['label_de'],
            'label_en' => $meta['label_en'],
        ];
    }

    public static function allowedBackgroundStyles(): array
    {
        return ['bayou', 'blood', 'gold', 'night'];
    }

    public static function normalizeBackgroundStyle(?string $style): ?string
    {
        $style = trim((string) $style);

        if ($style === '' || $style === 'none') {
            return null;
        }

        return in_array($style, self::allowedBackgroundStyles(), true) ? $style : null;
    }

    public function backgroundStyleClass(): ?string
    {
        $style = self::normalizeBackgroundStyle($this->background_style);

        return $style ? 'hh-feed-bg-' . $style : null;
    }

    public function gifPayload(): ?array
    {
        if (! $this->gif_url || ! $this->gif_provider) {
            return null;
        }

        return [
            'provider' => $this->gif_provider,
            'id' => $this->gif_id,
            'title' => $this->gif_title,
            'gif_url' => $this->gif_url,
            'preview_url' => $this->gif_preview_url ?: $this->gif_url,
            'source_url' => $this->gif_source_url,
        ];
    }

    public function hasVisibleAiContentLabel(): bool
    {
        return (bool) ($this->ai_user_declared || $this->admin_confirmed_ai);
    }

    public function aiAdminStatusLabel(): ?string
    {
        if ($this->ai_user_declared) {
            return __('ui.ai_content_user_declared_admin');
        }

        if ($this->admin_confirmed_ai) {
            return __('ui.ai_content_admin_confirmed');
        }

        if ($this->ai_detected_possible) {
            $confidence = $this->ai_detection_confidence !== null
                ? (int) round(((float) $this->ai_detection_confidence) * 100)
                : null;

            return $confidence !== null
                ? __('ui.ai_content_possible_admin_with_confidence', ['confidence' => $confidence])
                : __('ui.ai_content_possible_admin');
        }

        return null;
    }
}
