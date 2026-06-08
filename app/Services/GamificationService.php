<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Quest;
use App\Models\QuestProgress;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\Economy\CrownsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GamificationService
{
    public const XP = [
        'account_created' => 25,
        'profile_updated' => 15,
        'profile_completed' => 75,
        'feed_post_created' => 20,
        'feed_comment_created' => 8,
        'feed_comment_received' => 4,
        'feed_like_given' => 2,
        'feed_like_received' => 3,
        'lfg_post_created' => 15,
        'lfg_application_sent' => 10,
        'lfg_application_accepted' => 20,
        'team_created' => 30,
        'team_join_requested' => 5,
        'team_joined' => 20,
        'team_lfg_post_created' => 20,
        'team_lfg_application_sent' => 10,
        'team_lfg_application_accepted' => 25,
        'media_uploaded' => 10,
        'referral_signup' => 15,
        'referral_profile_completed' => 50,
        'moment_created' => 25,
        'moment_comment_created' => 8,
        'moment_like_given' => 2,
        'moment_like_received' => 3,
        'moment_saved' => 5,
        'cup_created' => 35,
        'cup_team_created' => 25,
        'cup_team_joined' => 15,
        'cup_submission_created' => 15,
        'cup_submission_approved' => 40,
        'cup_idea_submitted' => 10,
        'cup_idea_voted' => 2,
        'loadout_challenge_submission_created' => 10,
        'loadout_challenge_submission_accepted' => 40,
        'moment_of_week_selected' => 50,
        'quest_completed' => 0,
    ];

    public function award(User $user, string $action, ?int $points = null, ?Model $source = null, ?string $description = null, array $metadata = [], bool $oncePerSource = true): ?XpEvent
    {
        $points ??= self::XP[$action] ?? 0;

        if ($points <= 0) {
            $this->syncBadges($user->fresh() ?? $user);
            return null;
        }

        return DB::transaction(function () use ($user, $action, $points, $source, $description, $metadata, $oncePerSource): ?XpEvent {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($oncePerSource && $this->alreadyAwarded($lockedUser, $action, $source)) {
                $this->syncBadges($lockedUser);
                return null;
            }

            $event = XpEvent::create([
                'user_id' => $lockedUser->id,
                'action' => $action,
                'points' => $points,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'description' => $description ?? $this->defaultDescription($action),
                'metadata' => $metadata ?: null,
            ]);

            $lockedUser->xp_total = max(0, (int) $lockedUser->xp_total + $points);
            $lockedUser->level = $this->levelForXp((int) $lockedUser->xp_total);
            $lockedUser->trust_score = max(0, (int) $lockedUser->trust_score + max(1, (int) floor($points / 10)));
            $lockedUser->last_xp_at = now();
            $lockedUser->save();

            try {
                app(CrownsService::class)->reward(
                    $lockedUser,
                    $action,
                    $source,
                    description: $description ?? $this->defaultDescription($action),
                    metadata: ['xp_event_id' => $event->id]
                );
            } catch (\Throwable $exception) {
                report($exception);
            }

            $this->updateQuestProgress($lockedUser, $action);
            $this->syncBadges($lockedUser->fresh() ?? $lockedUser);

            return $event;
        });
    }

    public function evaluateProfile(User $user): void
    {
        $freshUser = $user->fresh(['profile']) ?? $user;

        $this->award($freshUser, 'profile_updated', source: $freshUser, description: 'Profil gepflegt');

        if (\App\Support\ProfileCompletion::score($freshUser) >= 100) {
            $this->award($freshUser, 'profile_completed', source: $freshUser, description: 'Profil zu 100% vervollständigt');
        }

        $this->syncBadges($freshUser->fresh() ?? $freshUser);
    }

    public function levelForXp(int $xpTotal): int
    {
        return max(1, min(100, intdiv(max(0, $xpTotal), 250) + 1));
    }

    public function xpForCurrentLevel(int $level): int
    {
        return max(0, ($level - 1) * 250);
    }

    public function xpForNextLevel(int $level): int
    {
        return max(250, $level * 250);
    }

    public function progressPercent(User $user): int
    {
        $level = (int) ($user->level ?: 1);
        $current = $this->xpForCurrentLevel($level);
        $next = $this->xpForNextLevel($level);
        $span = max(1, $next - $current);

        return (int) min(100, max(0, round((((int) $user->xp_total - $current) / $span) * 100)));
    }

    public function syncBadges(User $user): void
    {
        $user->loadMissing(['profile']);

        $rules = [
            'early-hunter' => fn (User $candidate): bool => (int) $candidate->xp_total >= 25,
            'profile-scout' => fn (User $candidate): bool => \App\Support\ProfileCompletion::score($candidate) >= 50,
            'profile-complete' => fn (User $candidate): bool => \App\Support\ProfileCompletion::score($candidate) >= 100,
            'wall-starter' => fn (User $candidate): bool => $candidate->feedPosts()->exists(),
            'conversation-starter' => fn (User $candidate): bool => $candidate->feedComments()->exists(),
            'team-founder' => fn (User $candidate): bool => $candidate->ownedTeams()->exists(),
            'lfg-hunter' => fn (User $candidate): bool => $candidate->lfgPosts()->exists(),
            'team-recruiter' => fn (User $candidate): bool => $candidate->teamLfgPosts()->exists(),
            'media-scout' => fn (User $candidate): bool => $candidate->mediaAssets()->exists(),
            'moment-maker' => fn (User $candidate): bool => $candidate->moments()->exists(),
            'cup-organizer' => fn (User $candidate): bool => $candidate->ownedCups()->exists(),
            'cup-contender' => fn (User $candidate): bool => $candidate->cupTeamMemberships()->where('status', 'active')->exists(),
            'level-5' => fn (User $candidate): bool => (int) $candidate->level >= 5,
        ];

        foreach ($rules as $slug => $passes) {
            if (! $passes($user)) {
                continue;
            }

            $badge = Badge::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->where('is_manual_only', false)
                ->first();
            if (! $badge) {
                continue;
            }

            if ($user->badges()->whereKey($badge->id)->exists()) {
                continue;
            }

            $user->badges()->syncWithoutDetaching([
                $badge->id => ['awarded_at' => now()],
            ]);

            $this->queueBadgeAchievement($user, $badge);

            if ($badge->notify_on_award) {
                $this->notifyBadgeAward($user, $badge);
            }
        }
    }

    private function alreadyAwarded(User $user, string $action, ?Model $source): bool
    {
        $query = XpEvent::query()
            ->where('user_id', $user->id)
            ->where('action', $action);

        if ($source) {
            $query->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->getKey());
        } else {
            $query->whereNull('source_type')->whereNull('source_id');
        }

        return $query->exists();
    }

    private function updateQuestProgress(User $user, string $action): void
    {
        $quests = Quest::query()
            ->where('is_active', true)
            ->where('action', $action)
            ->get();

        foreach ($quests as $quest) {
            $progress = QuestProgress::firstOrCreate(
                ['quest_id' => $quest->id, 'user_id' => $user->id],
                ['progress_count' => 0]
            );

            if ($progress->completed_at && ! $quest->is_repeatable) {
                continue;
            }

            $progress->progress_count = min((int) $quest->target_count, (int) $progress->progress_count + 1);

            if ($progress->progress_count >= (int) $quest->target_count && ! $progress->completed_at) {
                $progress->completed_at = now();
                $progress->reward_claimed_at = now();
                $progress->save();

                if ((int) $quest->xp_reward > 0) {
                    $this->award($user, 'quest_completed', (int) $quest->xp_reward, $quest, 'Quest abgeschlossen: '.$quest->name, [
                        'quest_slug' => $quest->slug,
                    ]);
                }

                $this->queueQuestAchievement($user, $quest);

                if ($quest->badge_slug) {
                    $badge = Badge::where('slug', $quest->badge_slug)->where('is_active', true)->first();
                    if ($badge) {
                        $alreadyHadBadge = $user->badges()->whereKey($badge->id)->exists();

                        $user->badges()->syncWithoutDetaching([
                            $badge->id => ['awarded_at' => now()],
                        ]);

                        if (! $alreadyHadBadge) {
                            $this->queueBadgeAchievement($user, $badge);
                        }

                        if ($badge->notify_on_award) {
                            $this->notifyBadgeAward($user, $badge);
                        }
                    }
                }

                if ($quest->notify_on_completion) {
                    app(NotificationService::class)->send(
                        $user,
                        null,
                        'quest_completed',
                        'Quest abgeschlossen',
                        $quest->name.((int) $quest->xp_reward > 0 ? ' · +'.(int) $quest->xp_reward.' XP' : ''),
                        route('gamification.index')
                    );
                }

                continue;
            }

            $progress->save();
        }
    }

    private function queueBadgeAchievement(User $user, Badge $badge): void
    {
        $this->queueAchievementToast($user, [
            'type' => 'badge',
            'eyebrow' => __('ui.achievement_badge_unlocked'),
            'title' => $badge->name,
            'body' => $badge->description ?: __('ui.achievement_badge_body'),
            'icon' => $this->modelIconUrl($badge),
            'xp' => max(0, (int) $badge->xp_reward),
            'url' => route('gamification.index'),
        ]);
    }

    private function queueQuestAchievement(User $user, Quest $quest): void
    {
        $this->queueAchievementToast($user, [
            'type' => 'quest',
            'eyebrow' => __('ui.achievement_quest_completed'),
            'title' => $quest->name,
            'body' => $quest->description ?: __('ui.achievement_quest_body'),
            'icon' => $this->modelIconUrl($quest),
            'xp' => max(0, (int) $quest->xp_reward),
            'url' => route('gamification.index'),
        ]);
    }

    private function queueAchievementToast(User $user, array $payload): void
    {
        if (! $this->shouldQueueAchievementToast($user)) {
            return;
        }

        session()->push('hunthub_achievement_toasts', array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'achievement',
            'eyebrow' => __('ui.achievement_unlocked'),
            'title' => __('ui.gamification'),
            'body' => null,
            'icon' => null,
            'xp' => 0,
            'url' => route('gamification.index'),
        ], $payload));
    }

    private function shouldQueueAchievementToast(User $user): bool
    {
        if (! auth()->check() || (int) auth()->id() !== (int) $user->id) {
            return false;
        }

        if (app()->runningInConsole()) {
            return false;
        }

        if (! app()->bound('request')) {
            return false;
        }

        $request = request();

        return method_exists($request, 'hasSession') && $request->hasSession();
    }

    private function modelIconUrl(Badge|Quest $model): ?string
    {
        if (method_exists($model, 'iconUrl')) {
            return $model->iconUrl();
        }

        return null;
    }

    private function notifyBadgeAward(User $user, Badge $badge): void
    {
        app(NotificationService::class)->send(
            $user,
            null,
            'badge_awarded',
            'Badge freigeschaltet',
            $badge->name,
            route('gamification.index')
        );
    }

    private function defaultDescription(string $action): string
    {
        return match ($action) {
            'account_created' => 'Account erstellt',
            'profile_updated' => 'Profil gepflegt',
            'profile_completed' => 'Profil vervollständigt',
            'feed_post_created' => 'Feed-Beitrag erstellt',
            'feed_comment_created' => 'Kommentar geschrieben',
            'feed_comment_received' => 'Kommentar erhalten',
            'feed_like_given' => 'Beitrag geliked',
            'feed_like_received' => 'Like erhalten',
            'lfg_post_created' => 'LFG erstellt',
            'lfg_application_sent' => 'LFG-Bewerbung gesendet',
            'lfg_application_accepted' => 'LFG-Bewerbung angenommen',
            'team_created' => 'Team erstellt',
            'team_join_requested' => 'Team-Beitritt angefragt',
            'team_joined' => 'Team beigetreten',
            'team_lfg_post_created' => 'Team-LFG erstellt',
            'team_lfg_application_sent' => 'Team-LFG-Bewerbung gesendet',
            'team_lfg_application_accepted' => 'Team-LFG angenommen',
            'media_uploaded' => 'Medium hochgeladen',
            'referral_signup' => 'Referral-Registrierung erhalten',
            'referral_profile_completed' => 'Referral-Profil vervollständigt',
            'moment_created' => 'Moment veröffentlicht',
            'moment_comment_created' => 'Moment kommentiert',
            'moment_like_given' => 'Moment geliked',
            'moment_like_received' => 'Like auf Moment erhalten',
            'moment_saved' => 'Moment gespeichert',
            'cup_created' => 'Cup erstellt',
            'cup_team_created' => 'Cup-Team erstellt',
            'cup_team_joined' => 'Cup-Team beigetreten',
            'cup_submission_created' => 'Cup-Ergebnis eingereicht',
            'cup_submission_approved' => 'Cup-Ergebnis angenommen',
            'cup_idea_submitted' => 'Cup-Idee eingereicht',
            'cup_idea_voted' => 'Für Cup-Idee abgestimmt',
            'loadout_challenge_submission_created' => 'Loadout-Challenge eingereicht',
            'loadout_challenge_submission_accepted' => 'Loadout-Challenge angenommen',
            'moment_of_week_selected' => 'Moment der Woche ausgewählt',
            'quest_completed' => 'Quest abgeschlossen',
            'badge_awarded' => 'Badge erhalten',
            default => 'XP erhalten',
        };
    }
}
