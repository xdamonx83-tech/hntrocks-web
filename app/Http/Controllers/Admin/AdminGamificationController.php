<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminGamificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $badges = Badge::query()
            ->withCount('users')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $quests = Quest::query()
            ->withCount('progress')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->with('profile')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('q')).'%';

                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->limit(60)
            ->get(['id', 'name', 'username', 'email', 'avatar_path', 'level']);

        $latestAwardedBadges = Badge::query()
            ->with(['users' => fn ($query) => $query->latest('badge_user.awarded_at')->limit(6)])
            ->whereHas('users')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('admin.gamification.index', [
            'badges' => $badges,
            'quests' => $quests,
            'users' => $users,
            'latestAwardedBadges' => $latestAwardedBadges,
            'questActions' => $this->questActions(),
            'questPeriods' => $this->questPeriods(),
            'filters' => $request->only('q'),
        ]);
    }

    public function storeBadge(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateBadge($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_manual_only'] = $request->boolean('is_manual_only');
        $data['notify_on_award'] = $request->boolean('notify_on_award', true);

        if ($request->hasFile('icon_file')) {
            $data['icon_path'] = $this->storeBadgeIcon($request);
        }

        Badge::create($data);

        return back()->with('status', 'Badge wurde angelegt.');
    }

    public function updateBadge(Request $request, Badge $badge): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateBadge($request, $badge);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $badge);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_manual_only'] = $request->boolean('is_manual_only');
        $data['notify_on_award'] = $request->boolean('notify_on_award');

        if ($request->hasFile('icon_file')) {
            if ($badge->icon_path) {
                Storage::disk('public')->delete($badge->icon_path);
            }

            $data['icon_path'] = $this->storeBadgeIcon($request);
        }

        $badge->update($data);

        return back()->with('status', 'Badge wurde aktualisiert.');
    }

    public function destroyBadge(Request $request, Badge $badge): RedirectResponse
    {
        $this->guardAdmin($request);

        if ($badge->users()->exists()) {
            return back()->with('status', 'Badge ist bereits vergeben und wurde nicht gelöscht. Setze ihn stattdessen inaktiv.');
        }

        if ($badge->icon_path) {
            Storage::disk('public')->delete($badge->icon_path);
        }

        $badge->delete();

        return back()->with('status', 'Badge wurde gelöscht.');
    }

    public function awardBadge(Request $request, Badge $badge, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'award_reason' => ['nullable', 'string', 'max:500'],
            'notify_user' => ['nullable', 'boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->badges()->whereKey($badge->id)->exists()) {
            return back()->with('status', 'Dieser Nutzer hat dieses Badge bereits.');
        }

        $user->badges()->syncWithoutDetaching([
            $badge->id => [
                'awarded_by' => $request->user()->id,
                'award_reason' => $validated['award_reason'] ?? null,
                'awarded_at' => now(),
            ],
        ]);

        if ((int) $badge->xp_reward > 0) {
            $gamification->award(
                $user,
                'badge_awarded',
                (int) $badge->xp_reward,
                $badge,
                'Badge erhalten: '.$badge->name,
                ['badge_slug' => $badge->slug]
            );
        }

        if ($request->boolean('notify_user', true) && $badge->notify_on_award) {
            $notifications->send(
                $user,
                $request->user(),
                'badge_awarded',
                'Badge erhalten',
                $badge->name,
                route('gamification.index')
            );
        }

        return back()->with('status', 'Badge wurde vergeben.');
    }

    public function revokeBadge(Request $request, Badge $badge, User $user): RedirectResponse
    {
        $this->guardAdmin($request);

        $user->badges()->detach($badge->id);

        return back()->with('status', 'Badge wurde vom Nutzer entfernt.');
    }

    public function storeQuest(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateQuest($request);
        $data['slug'] = $this->uniqueQuestSlug($data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_repeatable'] = $request->boolean('is_repeatable');
        $data['notify_on_completion'] = $request->boolean('notify_on_completion', true);

        if ($request->hasFile('icon_file')) {
            $data['icon_path'] = $this->storeQuestIcon($request);
        }

        Quest::create($data);

        return back()->with('status', 'Quest wurde angelegt.');
    }

    public function updateQuest(Request $request, Quest $quest): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateQuest($request, $quest);
        $data['slug'] = $this->uniqueQuestSlug($data['slug'] ?: $data['name'], $quest);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_repeatable'] = $request->boolean('is_repeatable');
        $data['notify_on_completion'] = $request->boolean('notify_on_completion');

        if ($request->hasFile('icon_file')) {
            if ($quest->icon_path) {
                Storage::disk('public')->delete($quest->icon_path);
            }

            $data['icon_path'] = $this->storeQuestIcon($request);
        }

        $quest->update($data);

        return back()->with('status', 'Quest wurde aktualisiert.');
    }

    public function destroyQuest(Request $request, Quest $quest): RedirectResponse
    {
        $this->guardAdmin($request);

        if ($quest->progress()->exists()) {
            return back()->with('status', 'Quest hat bereits Fortschritte und wurde nicht gelöscht. Setze sie stattdessen inaktiv.');
        }

        if ($quest->icon_path) {
            Storage::disk('public')->delete($quest->icon_path);
        }

        $quest->delete();

        return back()->with('status', 'Quest wurde gelöscht.');
    }

    private function validateQuest(Request $request, ?Quest $quest = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'category' => ['required', 'string', 'max:80'],
            'action' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\-]+$/'],
            'target_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'badge_slug' => ['nullable', 'string', 'max:80', 'exists:badges,slug'],
            'icon' => ['nullable', 'string', 'max:40'],
            'icon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif,svg', 'max:'.config('hunthub.upload_limits.gamification_icon_kb', 2048)],
            'description' => ['nullable', 'string', 'max:2000'],
            'period' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $data['category'] = Str::slug($data['category']) ?: 'daily';
        $data['xp_reward'] = (int) ($data['xp_reward'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['target_count'] = (int) $data['target_count'];
        $data['badge_slug'] = $data['badge_slug'] ?: null;
        $data['period'] = $data['period'] ?: null;

        return $data;
    }

    private function validateBadge(Request $request, ?Badge $badge = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/'],
            'category' => ['required', 'string', 'max:80'],
            'rarity' => ['required', 'in:common,uncommon,rare,epic,legendary'],
            'icon' => ['nullable', 'string', 'max:40'],
            'icon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,gif,svg', 'max:'.config('hunthub.upload_limits.gamification_icon_kb', 2048)],
            'description' => ['nullable', 'string', 'max:2000'],
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $data['category'] = Str::slug($data['category']) ?: 'community';
        $data['xp_reward'] = (int) ($data['xp_reward'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function uniqueSlug(string $value, ?Badge $currentBadge = null): string
    {
        $base = Str::slug($value) ?: 'badge';
        $slug = $base;
        $counter = 2;

        while (Badge::query()
            ->where('slug', $slug)
            ->when($currentBadge, fn ($query) => $query->whereKeyNot($currentBadge->id))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function storeBadgeIcon(Request $request): string
    {
        $file = $request->file('icon_file');
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png'));

        return $file->storeAs('badges/icons', Str::uuid().'.'.$extension, 'public');
    }

    private function uniqueQuestSlug(string $value, ?Quest $currentQuest = null): string
    {
        $base = Str::slug($value) ?: 'quest';
        $slug = $base;
        $counter = 2;

        while (Quest::query()
            ->where('slug', $slug)
            ->when($currentQuest, fn ($query) => $query->whereKeyNot($currentQuest->id))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function storeQuestIcon(Request $request): string
    {
        $file = $request->file('icon_file');
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png'));

        return $file->storeAs('quests/icons', Str::uuid().'.'.$extension, 'public');
    }

    private function questActions(): array
    {
        $actions = array_keys(GamificationService::XP);
        $actions = array_filter($actions, fn (string $action): bool => ! in_array($action, ['quest_completed', 'badge_awarded'], true));
        sort($actions);

        return array_values($actions);
    }

    private function questPeriods(): array
    {
        return [
            '' => 'Einmalig / dauerhaft',
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
            'seasonal' => 'Saisonal',
        ];
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
