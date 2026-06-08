<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HuntNewsItem;
use App\Services\HuntNews\OfficialHuntNewsImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'auto' => HuntNewsItem::where('auto_publish_eligible', true)->count(),
            'errors' => HuntNewsItem::where('status', HuntNewsItem::STATUS_ERROR)->count(),
        ];

        return view('admin.hunt-news.index', [
            'items' => $items,
            'stats' => $stats,
            'status' => $status,
            'statuses' => HuntNewsItem::statuses(),
            'sourceUrl' => config('hunthub.hunt_news.source_url', 'https://www.huntshowdown.com/news'),
            'autoPublishFrom' => config('hunthub.hunt_news.auto_publish_from') ?: now()->startOfDay()->toDateString(),
            'huntNewsUserId' => (int) config('hunthub.hunt_news.user_id', 1),
        ]);
    }

    public function sync(Request $request, OfficialHuntNewsImporter $importer): RedirectResponse
    {
        $this->guardAdmin($request);

        $limit = max(1, min(40, (int) $request->input('limit', 16)));

        try {
            $stats = $importer->sync(autoPublish: true, limit: $limit);
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

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
