<?php

namespace App\Http\Controllers\LFG;

use App\Http\Controllers\Controller;
use App\Support\HntTheme;
use App\Models\LfgPost;
use App\Models\Team;
use App\Models\Cup;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\MentionService;
use App\Services\NotificationService;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LfgController extends Controller
{
    public function index(Request $request): View
    {
        $query = LfgPost::query()
            ->with(['user.profile'])
            ->withCount([
                'applications as applications_count',
                'pendingApplications as pending_count',
                'acceptedApplications as accepted_count',
            ])
            ->where('status', '!=', 'archived')
            ->where(function ($inner) use ($request): void {
                $inner->where('visibility', 'public')
                    ->orWhere('user_id', $request->user()->id);
            });

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        foreach (['platform', 'playstyle', 'region', 'language', 'preferred_time', 'experience_level', 'status'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, $value);
            }
        }

        if ($request->boolean('voice_required')) {
            $query->where('voice_required', true);
        }

        if ($request->boolean('mine')) {
            $query->where('user_id', $request->user()->id);
        }

        match ($request->query('sort', 'newest')) {
            'open_slots' => $query
                ->orderByRaw('(slots_total - slots_filled) desc')
                ->latest(),
            'applications' => $query
                ->orderByDesc('pending_count')
                ->latest(),
            'expiring' => $query
                ->orderByRaw('expires_at IS NULL')
                ->orderBy('expires_at')
                ->latest(),
            default => $query->latest(),
        };

        $posts = $query->paginate(12)->withQueryString();

        $managedLfgPosts = LfgPost::query()
            ->with(['user.profile'])
            ->withCount([
                'applications as applications_count',
                'pendingApplications as pending_count',
                'acceptedApplications as accepted_count',
            ])
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'archived')
            ->latest()
            ->take(8)
            ->get();

        $view = HntTheme::lfgEnabled() && ! $request->boolean('classic_lfg')
            ? HntTheme::resolve('lfg.index')
            : 'lfg.index';

        $memberSuggestions = User::query()
            ->with(['profile'])
            ->where('users.id', '!=', $request->user()->id)
            ->where('users.status', 'active')
            ->whereHas('profile', function ($profileQuery) use ($request): void {
                $profileQuery->where(function ($visibilityQuery) use ($request): void {
                    $visibilityQuery
                        ->whereIn('profile_visibility', ['public', 'registered'])
                        ->orWhere('user_id', $request->user()->id);
                });
            })
            ->latest('users.created_at')
            ->take(4)
            ->get();

        $openTeams = Team::query()
            ->with(['owner.profile'])
            ->withCount(['activeMembers as members_count'])
            ->where('status', 'active')
            ->where('visibility', 'public')
            ->where('recruitment_status', 'open')
            ->latest()
            ->take(4)
            ->get();

        $featuredCups = Cup::query()
            ->visible()
            ->withCount(['activeTeams as participants_count'])
            ->whereIn('status', ['active', 'planned'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_at')
            ->take(4)
            ->get();

        $sidebarData = ReworkFeedSidebar::forViewer($request->user());

        return view($view, [
            'posts' => $posts,
            'managedLfgPosts' => $managedLfgPosts,
            'filters' => $request->only([
                'q',
                'platform',
                'playstyle',
                'region',
                'language',
                'preferred_time',
                'experience_level',
                'status',
                'voice_required',
                'mine',
                'sort',
            ]),
            'memberSuggestions' => $memberSuggestions,
            'openTeams' => $openTeams,
            'featuredCups' => $featuredCups,
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }

    public function create(Request $request): View
    {
        $view = HntTheme::lfgEnabled() && ! $request->boolean('classic_lfg')
            ? 'themes.socialite.lfg.create'
            : 'lfg.create';

        return view($view);
    }

    public function store(Request $request, GamificationService $gamification, MediaService $mediaService, MentionService $mentions, NotificationService $notifications): RedirectResponse
    {
        $validated = $this->validatedLfgData($request, false);

        unset($validated['cover']);

        $post = LfgPost::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'voice_required' => $request->boolean('voice_required'),
            'slots_filled' => 1,
            'status' => 'open',
        ]);

        if ($request->hasFile('cover')) {
            $coverAsset = $mediaService->store($request->file('cover'), $request->user(), 'lfg_cover', [
                'attachable' => $post,
                'visibility' => 'public',
            ]);
            $post->cover_path = $coverAsset->path;
            $post->save();
        }

        $gamification->award($request->user(), 'lfg_post_created', source: $post);
        $mentions->syncForLfgPost($post, $request->user(), $post->body, $notifications);

        return redirect()->route('lfg.show', $post)->with('status', __('ui.lfg_created_status'));
    }

    public function show(Request $request, LfgPost $post): View
    {
        $post->loadMissing([
            'user.profile',
            'applications.user.profile',
        ]);
        $post->loadCount([
            'applications as applications_count',
            'pendingApplications as pending_count',
            'acceptedApplications as accepted_count',
        ]);

        if ($post->visibility === 'private' && ! $post->canDelete($request->user())) {
            abort(404);
        }

        $view = HntTheme::lfgEnabled() && ! $request->boolean('classic_lfg')
            ? HntTheme::resolve('lfg.show')
            : 'lfg.show';

        return view($view, [
            'post' => $post,
            'viewerApplication' => $post->applicationFor($request->user()),
            'canManage' => $post->canManage($request->user()),
            'canDelete' => $post->canDelete($request->user()),
        ]);
    }

    public function edit(Request $request, LfgPost $post): View
    {
        abort_unless($post->canManage($request->user()), 403);

        $view = HntTheme::lfgEnabled() && ! $request->boolean('classic_lfg')
            ? 'themes.socialite.lfg.edit'
            : 'lfg.edit';

        return view($view, [
            'post' => $post,
        ]);
    }

    public function update(Request $request, LfgPost $post, MediaService $mediaService, MentionService $mentions, NotificationService $notifications): RedirectResponse
    {
        abort_unless($post->canManage($request->user()), 403);

        $validated = $this->validatedLfgData($request, true);

        $removeCover = $request->boolean('remove_cover');

        unset($validated['cover'], $validated['remove_cover']);

        $post->fill([
            ...$validated,
            'voice_required' => $request->boolean('voice_required'),
        ]);

        if ($request->hasFile('cover')) {
            if ($post->cover_path) {
                Storage::disk('public')->delete($post->cover_path);
            }

            $coverAsset = $mediaService->store($request->file('cover'), $request->user(), 'lfg_cover', [
                'attachable' => $post,
                'visibility' => 'public',
            ]);
            $post->cover_path = $coverAsset->path;
        } elseif ($removeCover && $post->cover_path) {
            Storage::disk('public')->delete($post->cover_path);
            $post->cover_path = null;
        }

        if ((int) $post->slots_filled > (int) $post->slots_total) {
            return back()
                ->withErrors(['slots_filled' => __('ui.lfg_error_slots_filled_too_high')])
                ->withInput();
        }

        if ($post->status === 'open' && $post->slots_filled >= $post->slots_total) {
            $post->status = 'full';
        }

        $post->save();
        $mentions->syncForLfgPost($post, $request->user(), $post->body, $notifications);

        return redirect()->route('lfg.show', $post)->with('status', __('ui.lfg_saved_status'));
    }

    public function destroy(Request $request, LfgPost $post): RedirectResponse
    {
        abort_unless($post->canDelete($request->user()), 403);

        $post->update(['status' => 'archived']);
        $post->delete();

        return redirect()->route('lfg.index')->with('status', __('ui.lfg_deleted_status'));
    }

    private function validatedLfgData(Request $request, bool $isUpdate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2800'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'preferred_time' => ['nullable', 'string', 'max:80'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'voice_required' => ['nullable', 'boolean'],
            'slots_total' => ['required', 'integer', 'min:2', 'max:3'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.lfg_cover_kb', 6144)],
        ];

        if ($isUpdate) {
            $rules['status'] = ['required', 'string', 'in:open,full,closed'];
            $rules['slots_filled'] = ['required', 'integer', 'min:1', 'max:3'];
            $rules['remove_cover'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules);
    }
}
