<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedNewsCard;
use App\Models\FeedPost;
use App\Models\HuntNewsItem;
use App\Models\User;
use App\Services\HuntNews\OfficialHuntNewsImporter;
use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminHuntNewsController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $status = $request->string('status')->toString();
        $allowedStatuses = array_keys(HuntNewsItem::statuses());

        $items = HuntNewsItem::query()
            ->with(['feedPost:id', 'outboundLink:id,slug,title,target_url', 'postedBy:id,name,username'])
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->orderByRaw('source_published_at is null')
            ->orderByDesc('source_published_at')
            ->orderByDesc('discovered_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'discovered' => HuntNewsItem::where('status', HuntNewsItem::STATUS_DISCOVERED)->count(),
            'posted' => HuntNewsItem::where('status', HuntNewsItem::STATUS_POSTED)->count(),
            'manual' => FeedNewsCard::query()->count(),
            'auto' => HuntNewsItem::where('auto_publish_eligible', true)->count(),
            'errors' => HuntNewsItem::where('status', HuntNewsItem::STATUS_ERROR)->count(),
        ];

        return view('admin.hunt-news.index', [
            'items' => $items,
            'stats' => $stats,
            'status' => $status,
            'statuses' => HuntNewsItem::statuses(),
            'publishers' => FeedNewsCard::publishers(),
            'recentNewsCards' => FeedNewsCard::query()
                ->with(['feedPost.user'])
                ->latest()
                ->limit(8)
                ->get(),
            'sourceUrl' => config('hunthub.hunt_news.source_url', 'https://www.huntshowdown.com/news'),
            'autoPublishFrom' => config('hunthub.hunt_news.auto_publish_from') ?: now()->startOfDay()->toDateString(),
            'huntNewsUserId' => (int) config('hunthub.hunt_news.user_id', 1),
        ]);
    }

    public function sync(
        Request $request,
        OfficialHuntNewsImporter $importer,
        FeedTranslationService $translations,
    ): RedirectResponse {
        $this->guardAdmin($request);

        if ($request->string('action')->toString() === 'manual_publish') {
            return $this->publishManual($request, $translations);
        }

        $limit = max(1, min(40, (int) $request->input('limit', 16)));

        try {
            $stats = $importer->sync(autoPublish: true, limit: $limit);
            $this->backfillOfficialNewsCards($request->user()?->id);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.hunt-news.index')
                ->with('status', 'Hunt-News-Abgleich fehlgeschlagen: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.hunt-news.index')
            ->with('status', 'Hunt-News geprüft: '.$stats['seen'].' gesehen, '.$stats['created'].' neu, '.$stats['updated'].' aktualisiert, '.$stats['posted'].' automatisch gepostet, '.$stats['errors'].' Fehler.');
    }

    public function publish(Request $request, HuntNewsItem $item, OfficialHuntNewsImporter $importer): RedirectResponse
    {
        $this->guardAdmin($request);

        try {
            $post = $importer->publish($item, $request->user()?->id);
            $this->ensureOfficialNewsCard($item, $post, $request->user()?->id);
        } catch (Throwable $exception) {
            $item->forceFill([
                'status' => HuntNewsItem::STATUS_ERROR,
                'error_message' => $exception->getMessage(),
            ])->save();

            return redirect()
                ->route('admin.hunt-news.index')
                ->with('status', 'News konnte nicht gepostet werden: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.hunt-news.index')
            ->with('status', 'Hunt-News wurde als Feed-Post #'.$post->id.' veröffentlicht.');
    }

    public function skip(Request $request, HuntNewsItem $item, OfficialHuntNewsImporter $importer): RedirectResponse
    {
        $this->guardAdmin($request);
        $importer->markSkipped($item);

        return redirect()
            ->route('admin.hunt-news.index')
            ->with('status', 'Hunt-News wurde übersprungen.');
    }

    private function publishManual(Request $request, FeedTranslationService $translations): RedirectResponse
    {
        $validated = $request->validate([
            'publisher' => ['required', 'string', 'in:'.implode(',', array_keys(FeedNewsCard::publishers()))],
            'body' => ['required', 'string', 'max:5000'],
            'badge' => ['required', 'string', 'max:40'],
            'kicker' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:190'],
            'highlights' => ['required', 'array', 'min:1', 'max:5'],
            'highlights.*' => ['nullable', 'string', 'max:180'],
            'pin' => ['nullable', 'boolean'],
        ]);

        $highlights = collect($validated['highlights'])
            ->map(fn ($highlight): string => trim((string) $highlight))
            ->filter()
            ->take(5)
            ->values()
            ->all();

        if ($highlights === []) {
            return back()->withErrors(['highlights' => 'Mindestens ein Stichpunkt ist erforderlich.'])->withInput();
        }

        $admin = $request->user();
        $publisher = (string) $validated['publisher'];
        $isPinned = $request->boolean('pin');
        $postOwner = $publisher === FeedNewsCard::PUBLISHER_HUNT_NEWS
            ? User::query()->find((int) config('hunthub.hunt_news.user_id', 1))
            : $admin;
        $postOwner ??= $admin;

        $post = DB::transaction(function () use ($validated, $highlights, $publisher, $postOwner, $admin, $translations, $isPinned): FeedPost {
            $post = FeedPost::create([
                'user_id' => $postOwner->id,
                'body' => trim((string) $validated['body']),
                'source_language' => $translations->detectLanguage((string) $validated['body']),
                'visibility' => 'public',
                'status' => 'published',
                'is_pinned' => $isPinned,
                'pinned_at' => $isPinned ? now() : null,
                'pinned_by_user_id' => $isPinned ? $admin?->id : null,
            ]);

            FeedNewsCard::create([
                'feed_post_id' => $post->id,
                'created_by_user_id' => $admin?->id,
                'publisher' => $publisher,
                'badge' => Str::limit(trim((string) $validated['badge']), 40, ''),
                'kicker' => filled($validated['kicker'] ?? null) ? Str::limit(trim((string) $validated['kicker']), 80, '') : null,
                'headline' => Str::limit(trim((string) $validated['headline']), 190, ''),
                'highlights' => $highlights,
            ]);

            return $post;
        });

        return redirect()
            ->route('admin.hunt-news.index')
            ->with('status', $publisher === FeedNewsCard::PUBLISHER_HUNT_NEWS
                ? 'News wurde als HuntNews-Post #'.$post->id.' veröffentlicht.'
                : 'News wurde als HNT.ROCKS News-Post #'.$post->id.' veröffentlicht.');
    }

    private function backfillOfficialNewsCards(?int $createdByUserId): void
    {
        HuntNewsItem::query()
            ->where('status', HuntNewsItem::STATUS_POSTED)
            ->whereNotNull('feed_post_id')
            ->latest('posted_at')
            ->limit(50)
            ->get()
            ->each(function (HuntNewsItem $item) use ($createdByUserId): void {
                $post = FeedPost::query()->find($item->feed_post_id);

                if ($post) {
                    $this->ensureOfficialNewsCard($item, $post, $createdByUserId);
                }
            });
    }

    private function ensureOfficialNewsCard(HuntNewsItem $item, FeedPost $post, ?int $createdByUserId): void
    {
        if (FeedNewsCard::query()->where('feed_post_id', $post->id)->exists()) {
            return;
        }

        $highlights = collect([
            filled($item->excerpt) ? Str::limit(trim((string) $item->excerpt), 180) : null,
            filled($item->category) ? 'Kategorie: '.Str::headline((string) $item->category) : null,
            'Vollständige Meldung über den HNT-Link im Beitrag.',
        ])->filter()->values()->all();

        FeedNewsCard::create([
            'feed_post_id' => $post->id,
            'created_by_user_id' => $createdByUserId,
            'publisher' => FeedNewsCard::PUBLISHER_HUNT_NEWS,
            'badge' => 'Official',
            'kicker' => 'HUNTNEWS',
            'headline' => Str::limit($item->displayTitle(), 190, ''),
            'highlights' => $highlights,
        ]);
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
