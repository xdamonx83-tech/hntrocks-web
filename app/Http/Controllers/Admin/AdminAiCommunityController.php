<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCommunityCharacter;
use App\Models\AiCommunityDraft;
use App\Models\FeedPost;
use App\Models\User;
use App\Services\AiCommunityClaimRiskInspector;
use App\Services\AiCommunityVerifiedFacts;
use App\Services\AiCommunityGenerationService;
use App\Services\AiCommunityEditorialReviewer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAiCommunityController extends Controller
{
    private function guard(Request $request): void { abort_unless($request->user()?->isAdmin(), 403); }

    public function index(Request $request, AiCommunityGenerationService $generator): View
    {
        $this->guard($request);
        $characters = AiCommunityCharacter::with('user.profile')->orderBy('id')->get();
        $previewCharacter = $characters->firstWhere('id', (int) $request->query('preview'));
        $recentBodies = AiCommunityDraft::latest()->limit(12)->pluck('body')->all();
        $drafts = AiCommunityDraft::with('character')->latest()->limit(100)->get();
        $claimWarnings = $drafts->mapWithKeys(static fn (AiCommunityDraft $draft): array => [
            $draft->id => AiCommunityClaimRiskInspector::inspect((string) $draft->body),
        ])->all();
        $factSources = $drafts->mapWithKeys(static fn (AiCommunityDraft $draft): array => [
            $draft->id => AiCommunityVerifiedFacts::forAngle($draft->editorial_angle),
        ])->all();
        return view('admin.ai-community.index', [
            'characters' => $characters,
            'drafts' => $drafts,
            'claimWarnings' => $claimWarnings,
            'factSources' => $factSources,
            'previewCharacter' => $previewCharacter,
            'previewMessages' => $previewCharacter ? $generator->promptContext($previewCharacter, $recentBodies) : null,
            'weekCount' => AiCommunityDraft::where('created_at', '>=', now('Europe/Berlin')->startOfWeek()->utc())->count(),
            'monthCost' => AiCommunityDraft::where('created_at', '>=', now()->startOfMonth())->sum('estimated_cost_microusd')
                + AiCommunityDraft::where('quality_reviewed_at', '>=', now()->startOfMonth())->sum('quality_review_cost_microusd'),
        ]);
    }

    public function update(Request $request, AiCommunityCharacter $character): RedirectResponse
    {
        $this->guard($request);
        $character->update($request->validate([
            'persona_voice' => ['required', 'string', 'max:1000'],
            'topics' => ['required', 'string', 'max:1000'],
            'prompt_settings' => ['nullable', 'string', 'max:1000'],
        ]));
        return back()->with('status', 'Persona saved.');
    }

    public function toggle(Request $request, AiCommunityCharacter $character): RedirectResponse
    {
        $this->guard($request);
        $character->update(['enabled' => ! $character->enabled]);
        return back()->with('status', 'Character state updated.');
    }

    public function provision(Request $request, AiCommunityCharacter $character): RedirectResponse
    {
        $this->guard($request);
        DB::transaction(function () use ($character): void {
            $character = AiCommunityCharacter::whereKey($character->id)->lockForUpdate()->firstOrFail();
            if ($character->user_id) { return; }
            if (User::where('username', $character->name)->exists()) {
                throw ValidationException::withMessages(['provision' => 'Username already belongs to another account.']);
            }
            $user = User::create([
                'name' => $character->name,
                'username' => $character->name,
                'email' => strtolower($character->name).'.'.Str::random(16).'@characters.hnt.invalid',
                'password' => Str::random(80),
                'is_ai_character' => true,
            ]);
            $user->profile()->create([
                'headline' => $character->disclosure(),
                'bio' => $character->disclosure(),
                'language' => $character->language,
                'profile_visibility' => 'public',
            ]);
            $character->update(['user_id' => $user->id]);
        });
        return back()->with('status', 'Synthetic account provisioned.');
    }

    public function generate(Request $request, AiCommunityCharacter $character, AiCommunityGenerationService $generator): RedirectResponse
    {
        $this->guard($request);
        $generator->generate($character);
        return back()->with('status', 'Draft generated for review.');
    }

    public function editDraft(Request $request, AiCommunityDraft $draft): RedirectResponse
    {
        $this->guard($request);
        abort_unless($draft->status === 'draft', 409);
        $body = trim($request->validate(['body' => ['required', 'string', 'max:700']])['body']);
        if (preg_match('~https?://|www\.~i', $body)) {
            throw ValidationException::withMessages(['body' => 'Links are not allowed in AI community drafts.']);
        }
        $hash = AiCommunityDraft::hashBody($body);
        if (AiCommunityDraft::where('body_hash', $hash)->whereKeyNot($draft->id)->exists()) {
            throw ValidationException::withMessages(['body' => 'Duplicate draft blocked.']);
        }
        Cache::lock('ai-community-generate', 90)->block(30, function () use ($draft, $body, $hash): void {
            $draft->refresh();
            abort_unless($draft->status === 'draft', 409);
            $draft->update([
                'body' => $body, 'body_hash' => $hash,
                'quality_review_decision' => null, 'quality_review_naturalness' => null,
                'quality_review_accuracy' => null, 'quality_review_reason' => null,
            ]);
        });
        return back()->with('status', 'Draft saved.');
    }

    public function qualityReview(Request $request, AiCommunityDraft $draft, AiCommunityEditorialReviewer $reviewer): RedirectResponse
    {
        $this->guard($request);
        $reviewer->review($draft);
        return back()->with('status', 'KI-Qualitätsprüfung gespeichert. Redaktionelle Freigabe bleibt erforderlich.');
    }

    public function review(Request $request, AiCommunityDraft $draft): RedirectResponse
    {
        $this->guard($request);
        abort_unless($draft->status === 'draft', 409);
        $decision = $request->validate(['decision' => ['required', 'in:approved,rejected']])['decision'];
        $draft->update(['status' => $decision, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        return back()->with('status', 'Draft '.$decision.'.');
    }

    public function publish(Request $request, AiCommunityDraft $draft): RedirectResponse
    {
        $this->guard($request);
        DB::transaction(function () use ($draft): void {
            $draft = AiCommunityDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            abort_unless($draft->status === 'approved' && ! $draft->published_feed_post_id, 409);
            abort_unless(mb_strlen($draft->body) <= 700 && ! preg_match('~https?://|www\.~i', $draft->body), 409);
            $character = $draft->character()->with('user.profile')->firstOrFail();
            $user = $character->user;
            abort_unless($user?->is_ai_character && $user->username === $character->name
                && $user->profile?->profile_visibility === 'public'
                && str_contains((string) $user->profile?->bio, $character->disclosure()), 409);
            $post = FeedPost::create([
                'user_id' => $user->id, 'body' => $draft->body,
                'status' => 'published', 'visibility' => 'public',
                'source_language' => $character->language,
                'ai_user_declared' => true, 'admin_confirmed_ai' => true,
            ]);
            $draft->update(['status' => 'published', 'published_feed_post_id' => $post->id]);
            $character->increment('published_count');
        });
        return back()->with('status', 'Approved draft published.');
    }
}
