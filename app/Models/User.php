<?php

namespace App\Models;

use App\Notifications\HntPasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Support\ProfileCompletion;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const ONLINE_WINDOW_SECONDS = 90;

    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'password',
        'avatar_path',
        'cover_path',
        'xp_total',
        'level',
        'trust_score',
        'last_xp_at',
        'is_admin',
        'status',
        'suspended_at',
        'last_login_at',
        'last_login_ip',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_xp_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function sendPasswordResetNotification($token): void
    {
        $profileLanguage = $this->profile?->language;
        $locale = in_array($profileLanguage, ['de', 'en'], true) ? $profileLanguage : app()->getLocale();

        $this->notify((new HntPasswordResetNotification($token))->locale($locale));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(FeedPost::class);
    }

    public function feedComments(): HasMany
    {
        return $this->hasMany(FeedComment::class);
    }

    public function cupChatMessages(): HasMany
    {
        return $this->hasMany(CupChatMessage::class);
    }

    public function feedReactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class);
    }

    public function feedBookmarks(): HasMany
    {
        return $this->hasMany(FeedBookmark::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(XpEvent::class);
    }


    public function crownWallet(): HasOne
    {
        return $this->hasOne(CrownWallet::class);
    }

    public function crownTransactions(): HasMany
    {
        return $this->hasMany(CrownTransaction::class);
    }

    public function crownRewardClaims(): HasMany
    {
        return $this->hasMany(CrownRewardClaim::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)
            ->withPivot(['awarded_at', 'awarded_by', 'award_reason'])
            ->withTimestamps();
    }

    public function questProgress(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function completedQuestProgress(): HasMany
    {
        return $this->hasMany(QuestProgress::class)->whereNotNull('completed_at');
    }

    public function xpToNextLevel(): int
    {
        $level = max(1, (int) ($this->level ?: 1));
        $nextLevelXp = $level * 250;

        return max(0, $nextLevelXp - (int) $this->xp_total);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function notificationItems(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function pushDevices(): HasMany
    {
        return $this->hasMany(UserPushDevice::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Compatibility layer: Hunthub uses user_notifications, not Laravel's default notifications table.
     * This prevents accidental calls to $user->notifications() or $user->unreadNotifications()
     * from querying a missing notifications table.
     */
    public function notifications(): HasMany
    {
        return $this->notificationItems();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notificationItems()
            ->whereNotIn('type', UserNotification::STANDARD_EXCLUDED_TYPES)
            ->whereNull('read_at');
    }

    public function referralLink(): HasOne
    {
        return $this->hasOne(ReferralLink::class);
    }

    public function referredSignups(): HasMany
    {
        return $this->hasMany(ReferralSignup::class, 'referrer_id');
    }

    public function referralSignup(): HasOne
    {
        return $this->hasOne(ReferralSignup::class, 'referred_user_id');
    }

    public function giveawayEntries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    public function actorNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'actor_id');
    }

    public function unreadMessagesCount(?array $conversationTypes = null): int
    {
        return Message::query()
            ->where('messages.user_id', '!=', $this->id)
            ->whereHas('conversation', function ($query) use ($conversationTypes): void {
                $query->whereHas('users', fn ($users) => $users->where('users.id', $this->id));

                if ($conversationTypes) {
                    $query->whereIn('type', $conversationTypes);
                }
            })
            ->where(function ($query): void {
                $query->whereExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('conversation_participants')
                        ->whereColumn('conversation_participants.conversation_id', 'messages.conversation_id')
                        ->where('conversation_participants.user_id', $this->id)
                        ->whereNotNull('conversation_participants.last_read_at')
                        ->whereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
                })->orWhereExists(function ($subQuery): void {
                    $subQuery->selectRaw('1')
                        ->from('conversation_participants')
                        ->whereColumn('conversation_participants.conversation_id', 'messages.conversation_id')
                        ->where('conversation_participants.user_id', $this->id)
                        ->whereNull('conversation_participants.last_read_at');
                });
            })
            ->count();
    }

    public function lfgPosts(): HasMany
    {
        return $this->hasMany(LfgPost::class);
    }

    public function lfgApplications(): HasMany
    {
        return $this->hasMany(LfgApplication::class);
    }

    public function liveLobbies(): HasMany
    {
        return $this->hasMany(LiveLobby::class, 'creator_id');
    }

    public function liveLobbyMemberships(): HasMany
    {
        return $this->hasMany(LiveLobbyMember::class);
    }

    public function receivedLiveLobbyFeedback(): HasMany
    {
        return $this->hasMany(LiveLobbyFeedback::class, 'target_user_id');
    }

    public function givenLiveLobbyFeedback(): HasMany
    {
        return $this->hasMany(LiveLobbyFeedback::class, 'reviewer_id');
    }

    public function teamLfgPosts(): HasMany
    {
        return $this->hasMany(TeamLfgPost::class);
    }

    public function teamLfgApplications(): HasMany
    {
        return $this->hasMany(TeamLfgApplication::class);
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'status', 'joined_at'])
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    public function sentFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'recipient_id');
    }

    public function friendshipsAsUserOne(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_one_id');
    }

    public function friendshipsAsUserTwo(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_two_id');
    }

    public function friendshipWith(User $user): ?Friendship
    {
        return Friendship::query()->between($this, $user)->first();
    }

    public function friendsCount(): int
    {
        return Friendship::query()
            ->forUser($this)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->count();
    }

    public function moments(): HasMany
    {
        return $this->hasMany(Moment::class);
    }

    public function momentComments(): HasMany
    {
        return $this->hasMany(MomentComment::class);
    }

    public function momentReactions(): HasMany
    {
        return $this->hasMany(MomentReaction::class);
    }

    public function momentBookmarks(): HasMany
    {
        return $this->hasMany(MomentBookmark::class);
    }

    public function ownedCups(): HasMany
    {
        return $this->hasMany(Cup::class, 'owner_id');
    }

    public function cupTeams(): HasMany
    {
        return $this->hasMany(CupTeam::class, 'owner_id');
    }

    public function cupTeamMemberships(): HasMany
    {
        return $this->hasMany(CupTeamMember::class);
    }

    public function cupSubmissions(): HasMany
    {
        return $this->hasMany(CupSubmission::class, 'submitted_by');
    }

    public function loadoutChallenges(): HasMany
    {
        return $this->hasMany(LoadoutChallenge::class, 'created_by');
    }

    public function loadoutChallengeSubmissions(): HasMany
    {
        return $this->hasMany(LoadoutChallengeSubmission::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'assigned_to');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->greaterThanOrEqualTo(now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
    }

    public function allowsOnlineStatusVisibility(?User $viewer = null): bool
    {
        $this->loadMissing('privacySettings');

        return $this->privacySettings?->show_online_status !== false;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function privacySettings(): HasOne
    {
        return $this->hasOne(UserPrivacySetting::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class);
    }

    public function blockedByUsers(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_user_id');
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(UserSecurityEvent::class);
    }

    public function accountDeletionRequest(): HasOne
    {
        return $this->hasOne(AccountDeletionRequest::class);
    }

    public function twoFactorChallenges(): HasMany
    {
        return $this->hasMany(UserTwoFactorChallenge::class);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_user_id', $user->id)->exists();
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return asset('assets/vikinger/img/default-avatar.svg');
    }

    public function coverUrl(): string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        return asset('assets/vikinger/img/default-cover.svg');
    }

    /**
     * Core profile completion checks.
     *
     * Social/stream links, Discord, profile media, headline and Hunt role are optional.
     * A user can reach 100% with the essential profile/gameplay fields only.
     *
     * @return array<string, bool>
     */
    public function profileCompletionChecks(): array
    {
        return ProfileCompletion::checks($this);
    }

    public function profileCompletionScore(): int
    {
        return ProfileCompletion::score($this);
    }
}
