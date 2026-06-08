<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CupIdea extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const CATEGORY_SOLO = 'solo';
    public const CATEGORY_DUO = 'duo';
    public const CATEGORY_TRIO = 'trio';
    public const CATEGORY_TEAM = 'team';
    public const CATEGORY_FUN = 'fun_challenge';
    public const CATEGORY_LOADOUT = 'loadout_challenge';
    public const CATEGORY_SCORING = 'scoring';
    public const CATEGORY_PRIZES = 'prizes';
    public const CATEGORY_RULES = 'rules';
    public const CATEGORY_OTHER = 'other';

    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_COMING_SOON = 'coming_soon';
    public const STATUS_IMPLEMENTED = 'implemented';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'cup_id',
        'title',
        'category',
        'description',
        'status',
        'is_featured',
        'votes_count',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'votes_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_SOLO => __('ui.cup_ideas_category_solo'),
            self::CATEGORY_DUO => __('ui.cup_ideas_category_duo'),
            self::CATEGORY_TRIO => __('ui.cup_ideas_category_trio'),
            self::CATEGORY_TEAM => __('ui.cup_ideas_category_team'),
            self::CATEGORY_FUN => __('ui.cup_ideas_category_fun_challenge'),
            self::CATEGORY_LOADOUT => __('ui.cup_ideas_category_loadout_challenge'),
            self::CATEGORY_SCORING => __('ui.cup_ideas_category_scoring'),
            self::CATEGORY_PRIZES => __('ui.cup_ideas_category_prizes'),
            self::CATEGORY_RULES => __('ui.cup_ideas_category_rules'),
            self::CATEGORY_OTHER => __('ui.cup_ideas_category_other'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => __('ui.cup_ideas_status_new'),
            self::STATUS_REVIEWING => __('ui.cup_ideas_status_reviewing'),
            self::STATUS_PLANNED => __('ui.cup_ideas_status_planned'),
            self::STATUS_COMING_SOON => __('ui.cup_ideas_status_coming_soon'),
            self::STATUS_IMPLEMENTED => __('ui.cup_ideas_status_implemented'),
            self::STATUS_REJECTED => __('ui.cup_ideas_status_rejected'),
            self::STATUS_ARCHIVED => __('ui.cup_ideas_status_archived'),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CupIdeaVote::class);
    }

    public function viewerVote(): HasOne
    {
        return $this->hasOne(CupIdeaVote::class)->where('user_id', auth()->id());
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? self::categoryOptions()[self::CATEGORY_OTHER];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? self::statusOptions()[self::STATUS_NEW];
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_ARCHIVED);
    }
}
