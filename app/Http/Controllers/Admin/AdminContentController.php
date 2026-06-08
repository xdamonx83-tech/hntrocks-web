<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\FeedPost;
use App\Models\FeedPostMedia;
use App\Models\LfgPost;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\Team;
use App\Models\TeamLfgPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $availableSections = [
            'feed-posts',
            'moments',
            'media',
            'teams',
            'lfg',
            'team-lfg',
            'cups',
            'cup-submissions',
        ];

        $selectedSection = (string) $request->query('section', 'feed-posts');
        if ($selectedSection !== 'all' && ! in_array($selectedSection, $availableSections, true)) {
            $selectedSection = 'feed-posts';
        }

        $mediaAssets = MediaAsset::with(['user', 'attachable'])->latest()->limit(10)->get();
        $mediaPreviewMeta = $mediaAssets
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => $this->mediaPreviewData($asset)])
            ->all();

        return view('admin.content.index', [
            'selectedSection' => $selectedSection,
            'sectionCounts' => [
                'feed-posts' => FeedPost::count(),
                'moments' => Moment::count(),
                'media' => MediaAsset::count(),
                'teams' => Team::count(),
                'lfg' => LfgPost::count(),
                'team-lfg' => TeamLfgPost::count(),
                'cups' => Cup::count(),
                'cup-submissions' => CupSubmission::count(),
            ],
            'feedPosts' => FeedPost::with('user')->latest()->limit(10)->get(),
            'moments' => Moment::with('user')->latest()->limit(10)->get(),
            'mediaAssets' => $mediaAssets,
            'mediaPreviewMeta' => $mediaPreviewMeta,
            'teams' => Team::with('owner')->latest()->limit(10)->get(),
            'lfgPosts' => LfgPost::with('user')->latest()->limit(10)->get(),
            'teamLfgPosts' => TeamLfgPost::with(['user', 'team'])->latest()->limit(10)->get(),
            'cups' => Cup::with('owner')->latest()->limit(10)->get(),
            'cupSubmissions' => CupSubmission::with(['cup', 'team', 'submitter'])->latest()->limit(10)->get(),
        ]);
    }

    public function updateStatus(Request $request, string $type, int $id): RedirectResponse
    {
        $this->guardAdmin($request);

        $model = $this->resolveModeratable($type, $id);

        $allowed = $this->allowedStatuses($type);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', $allowed)],
        ]);

        $model->forceFill(['status' => $validated['status']])->save();

        return back()->with('status', 'Inhaltsstatus wurde aktualisiert.');
    }

    public function updateFeedAiLabel(Request $request, FeedPost $post): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'action' => ['required', 'in:confirm,dismiss'],
        ]);

        if ($validated['action'] === 'confirm') {
            $post->forceFill([
                'admin_confirmed_ai' => true,
                'admin_ai_reviewed_at' => now(),
                'admin_ai_reviewed_by_user_id' => $request->user()->id,
            ])->save();

            return back()->with('status', __('ui.ai_content_admin_confirmed_status'));
        }

        $post->forceFill([
            'ai_detected_possible' => false,
            'admin_confirmed_ai' => false,
            'admin_ai_reviewed_at' => now(),
            'admin_ai_reviewed_by_user_id' => $request->user()->id,
        ])->save();

        return back()->with('status', __('ui.ai_content_admin_dismissed_status'));
    }


    private function mediaPreviewData(MediaAsset $asset): array
    {
        $target = $this->resolveMediaTarget($asset);

        return [
            'thumbnail_url' => $this->safeMediaUrl($asset, thumbnail: true),
            'direct_url' => $this->safeMediaUrl($asset, thumbnail: false),
            'is_image' => $asset->isImage(),
            'is_video' => $asset->isVideo(),
            'type_label' => $asset->isImage() ? 'Bild' : ($asset->isVideo() ? 'Video' : 'Datei'),
            'size' => $asset->readableSize(),
            'dimensions' => $asset->width && $asset->height ? $asset->width.'×'.$asset->height : null,
            'target_url' => $target['url'],
            'target_label' => $target['label'],
            'target_type' => $target['type'],
        ];
    }

    private function safeMediaUrl(MediaAsset $asset, bool $thumbnail): ?string
    {
        try {
            return $thumbnail ? $asset->thumbnailUrl() : $asset->url();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveMediaTarget(MediaAsset $asset): array
    {
        $asset->loadMissing('attachable');
        $attachable = $asset->attachable;

        if ($attachable instanceof FeedPost) {
            return [
                'type' => 'Feed-Beitrag',
                'label' => 'Feed-Beitrag #'.$attachable->id,
                'url' => route('feed.show', $attachable),
            ];
        }

        if ($attachable instanceof Moment) {
            return [
                'type' => 'Moment',
                'label' => 'Moment #'.$attachable->id,
                'url' => route('moments.show', $attachable),
            ];
        }

        if ($attachable instanceof CupSubmission) {
            $attachable->loadMissing('cup');

            return [
                'type' => 'Cup-Einreichung',
                'label' => ($attachable->cup?->title ?? 'Cup').' · Einreichung #'.$attachable->id,
                'url' => $attachable->cup ? route('cups.show.section', [$attachable->cup, 'submissions']) : null,
            ];
        }

        if ($attachable instanceof Cup) {
            return [
                'type' => 'Cup',
                'label' => $attachable->title,
                'url' => route('cups.show', $attachable),
            ];
        }

        if ($attachable instanceof Team) {
            return [
                'type' => 'Team',
                'label' => $attachable->name,
                'url' => route('teams.show', $attachable),
            ];
        }

        if ($attachable instanceof LfgPost) {
            return [
                'type' => 'LFG',
                'label' => $attachable->title,
                'url' => route('lfg.show', $attachable),
            ];
        }

        if ($attachable instanceof TeamLfgPost) {
            return [
                'type' => 'Team-LFG',
                'label' => $attachable->title,
                'url' => route('team-lfg.show', $attachable),
            ];
        }

        $feedMedia = FeedPostMedia::query()->where('media_asset_id', $asset->id)->first();
        $feedPost = $feedMedia?->post()->withTrashed()->first();
        if ($feedPost) {
            return [
                'type' => 'Feed-Beitrag',
                'label' => 'Feed-Beitrag #'.$feedPost->id,
                'url' => route('feed.show', $feedPost),
            ];
        }

        $moment = Moment::withTrashed()
            ->where('media_asset_id', $asset->id)
            ->orWhere('cover_media_asset_id', $asset->id)
            ->first();
        if ($moment) {
            return [
                'type' => 'Moment',
                'label' => 'Moment #'.$moment->id,
                'url' => route('moments.show', $moment),
            ];
        }

        $submission = CupSubmission::withTrashed()->with('cup')->where('screenshot_media_asset_id', $asset->id)->first();
        if ($submission) {
            return [
                'type' => 'Cup-Einreichung',
                'label' => ($submission->cup?->title ?? 'Cup').' · Einreichung #'.$submission->id,
                'url' => $submission->cup ? route('cups.show.section', [$submission->cup, 'submissions']) : null,
            ];
        }

        return [
            'type' => 'Medienbibliothek',
            'label' => 'Kein verknüpfter Inhalt',
            'url' => null,
        ];
    }

    private function resolveModeratable(string $type, int $id): Model
    {
        $class = match ($type) {
            'feed-posts' => FeedPost::class,
            'moments' => Moment::class,
            'media' => MediaAsset::class,
            'teams' => Team::class,
            'lfg' => LfgPost::class,
            'team-lfg' => TeamLfgPost::class,
            'cups' => Cup::class,
            'cup-submissions' => CupSubmission::class,
            default => abort(404),
        };

        return $class::withTrashed()->findOrFail($id);
    }

    private function allowedStatuses(string $type): array
    {
        return match ($type) {
            'feed-posts', 'moments' => ['published', 'hidden', 'removed'],
            'media' => ['ready', 'hidden', 'quarantined', 'removed'],
            'teams' => ['active', 'archived', 'suspended'],
            'lfg' => ['open', 'full', 'closed', 'archived'],
            'team-lfg' => ['open', 'filled', 'closed', 'archived'],
            'cups' => ['planned', 'active', 'finished', 'archived'],
            'cup-submissions' => ['pending', 'approved', 'rejected'],
            default => ['active'],
        };
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
