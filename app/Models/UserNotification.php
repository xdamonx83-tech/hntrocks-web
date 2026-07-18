<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

class UserNotification extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    public const STANDARD_EXCLUDED_TYPES = ['friend_request'];
    public const SYSTEM_AVATAR_PATH = 'assets/hnt/system/system-notification-avatar.webp';

    private const TITLE_TRANSLATION_KEYS = [
        'feed_comment' => 'ui.comment_new_title',
        'team_feed_comment' => 'ui.comment_new_title',
        'feed_comment_reply' => 'ui.comment_reply_title',
        'team_feed_comment_reply' => 'ui.comment_reply_title',
        'feed_like' => 'ui.reaction_new_title',
        'team_feed_like' => 'ui.reaction_new_title',
        'feed_reaction' => 'ui.reaction_new_title',
        'team_feed_reaction' => 'ui.reaction_new_title',
        'feed_comment_reaction' => 'ui.comment_reaction_new_title',
        'team_feed_comment_reaction' => 'ui.comment_reaction_new_title',
        'feed_mention' => 'ui.notification_mention_title',
        'feed_comment_mention' => 'ui.notification_mention_title',
        'team_feed_mention' => 'ui.notification_mention_title',
        'team_feed_comment_mention' => 'ui.notification_mention_title',
        'lfg_mention' => 'ui.notification_mention_title',
        'team_lfg_mention' => 'ui.notification_mention_title',
        'friend_request_accepted' => 'ui.friend_request_accepted_title',
        'lfg_application' => 'ui.lfg_notification_application_title',
        'lfg_application_accepted' => 'ui.lfg_notification_accepted_title',
        'lfg_application_rejected' => 'ui.lfg_notification_rejected_title',
        'team_lfg_application' => 'ui.notification_team_lfg_application_title',
        'team_lfg_application_accepted' => 'ui.notification_team_lfg_application_accepted_title',
        'team_lfg_application_rejected' => 'ui.notification_team_lfg_application_rejected_title',
        'team_join_request' => 'ui.team_join_request_title',
        'team_join_accepted' => 'ui.team_join_accepted_title',
        'team_join_rejected' => 'ui.team_join_rejected_title',
        'badge_awarded' => 'ui.notification_badge_awarded_title',
        'quest_completed' => 'ui.notification_quest_completed_title',
        'moment_comment_new' => 'ui.notification_moment_comment_title',
        'moment_like_new' => 'ui.notification_moment_like_title',
        'cup_submission_review_required' => 'ui.cup_submission_notification_review_title',
        'cup_submission_processed' => 'ui.cup_submission_notification_processed_title',
        'cup_submission_approved' => 'ui.cup_submission_gamification_approved',
        'cup_submission_rejected' => 'ui.cup_submission_notification_rejected_title',
        'cup_team_created' => 'ui.cup_team_notification_created_title',
        'cup_participant_registered' => 'ui.cup_participant_notification_created_title',
        'cup_team_joined' => 'ui.cup_team_notification_joined_title',
        'referral_signup' => 'ui.notification_referral_signup_title',
        'referral_profile_completed' => 'ui.notification_referral_profile_completed_title',
    ];

    private const BODY_TRANSLATION_KEYS = [
        'feed_comment' => 'ui.comment_new_body',
        'team_feed_comment' => 'ui.notification_team_feed_comment_body',
        'feed_comment_reply' => 'ui.comment_reply_body',
        'team_feed_comment_reply' => 'ui.notification_team_feed_comment_reply_body',
        'feed_like' => 'ui.feed_reaction_notification_body',
        'team_feed_like' => 'ui.notification_team_feed_reaction_body',
        'feed_reaction' => 'ui.feed_reaction_notification_body',
        'team_feed_reaction' => 'ui.notification_team_feed_reaction_body',
        'feed_comment_reaction' => 'ui.comment_reaction_notification_body',
        'team_feed_comment_reaction' => 'ui.notification_team_feed_comment_reaction_body',
        'feed_mention' => 'ui.notification_feed_mention_body',
        'feed_comment_mention' => 'ui.notification_feed_comment_mention_body',
        'team_feed_mention' => 'ui.notification_team_feed_mention_body',
        'team_feed_comment_mention' => 'ui.notification_team_feed_comment_mention_body',
        'lfg_mention' => 'ui.notification_lfg_mention_body',
        'team_lfg_mention' => 'ui.notification_team_lfg_mention_body',
        'friend_request_accepted' => 'ui.friend_request_accepted_body',
        'lfg_application' => 'ui.lfg_notification_application_body',
        'lfg_application_accepted' => 'ui.lfg_notification_accepted_body',
        'lfg_application_rejected' => 'ui.lfg_notification_rejected_body',
        'team_lfg_application' => 'ui.notification_team_lfg_application_body',
        'team_lfg_application_accepted' => 'ui.notification_team_lfg_application_accepted_body',
        'team_lfg_application_rejected' => 'ui.notification_team_lfg_application_rejected_body',
        'team_join_request' => 'ui.team_join_request_body',
        'team_join_accepted' => 'ui.notification_team_join_accepted_body',
        'team_join_rejected' => 'ui.notification_team_join_rejected_body',
        'moment_comment_new' => 'ui.notification_moment_comment_body',
        'moment_like_new' => 'ui.notification_moment_like_body',
        'cup_submission_review_required' => 'ui.notification_cup_submission_review_body',
        'cup_submission_processed' => 'ui.notification_cup_submission_processed_body',
        'cup_submission_approved' => 'ui.notification_cup_submission_approved_body',
        'cup_submission_rejected' => 'ui.notification_cup_submission_rejected_body',
        'cup_team_created' => 'ui.notification_cup_team_created_body',
        'cup_participant_registered' => 'ui.notification_cup_participant_registered_body',
        'cup_team_joined' => 'ui.cup_team_notification_joined_body',
        'referral_signup' => 'ui.notification_referral_signup_body',
        'referral_profile_completed' => 'ui.notification_referral_profile_completed_body',
    ];

    protected $fillable = ['user_id', 'actor_id', 'type', 'title', 'body', 'action_url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->whereNotIn('type', self::STANDARD_EXCLUDED_TYPES);
    }


    public function isSystemNotification(): bool
    {
        return $this->actor_id === null;
    }

    public function displayActorName(): string
    {
        return $this->actor?->name ?: $this->actor?->username ?: __('ui.hnt_rocks_system');
    }

    public function displayActorAvatarUrl(): string
    {
        if ($this->actor) {
            return $this->actor->avatarUrl();
        }

        return asset(self::SYSTEM_AVATAR_PATH);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function actionUrl(): ?string
    {
        return self::normalizeActionUrl($this->action_url);
    }

    public static function normalizeActionUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $currentHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $localHosts = array_filter([
            'hnt.rocks',
            'www.hnt.rocks',
            'v2.hnt.rocks',
            'www.v2.hnt.rocks',
            $currentHost ? strtolower($currentHost) : null,
        ]);

        $host = parse_url($url, PHP_URL_HOST);

        if ($host && in_array(strtolower($host), array_unique($localHosts), true)) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            $fragment = parse_url($url, PHP_URL_FRAGMENT);

            if ($query !== null && $query !== '') {
                $path .= '?' . $query;
            }

            if ($fragment !== null && $fragment !== '') {
                $path .= '#' . $fragment;
            }

            return $path;
        }

        return $url;
    }

    public function displayTitle(): string
    {
        $key = self::TITLE_TRANSLATION_KEYS[$this->normalizedType()] ?? null;

        if ($key && Lang::has($key)) {
            return __($key);
        }

        return $this->translateStoredValue($this->title) ?: __('ui.notifications');
    }

    public function displayBody(): ?string
    {
        $key = self::BODY_TRANSLATION_KEYS[$this->normalizedType()] ?? null;

        if ($key && Lang::has($key)) {
            return __($key, $this->translationReplacements());
        }

        $body = $this->translateStoredValue($this->body);

        return $body !== '' ? $body : null;
    }

    private function normalizedType(): string
    {
        return strtolower((string) $this->type);
    }

    private function translateStoredValue(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (Lang::has($value)) {
            return __($value);
        }

        return $value;
    }

    private function translationReplacements(): array
    {
        $actorName = $this->actor?->name ?: $this->actor?->username ?: __('ui.hnt_rocks_system');
        $storedBody = trim((string) $this->body);

        return [
            'name' => $actorName,
            'user' => $actorName,
            'player' => $actorName,
            'team' => $storedBody !== '' ? $storedBody : 'Team',
            'cup' => $storedBody !== '' ? $storedBody : __('ui.cups'),
        ];
    }

    protected function blockedAuthorColumn(): string
    {
        return 'actor_id';
    }
}
