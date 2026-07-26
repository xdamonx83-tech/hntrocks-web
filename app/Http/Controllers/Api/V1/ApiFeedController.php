<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FeedPostResource;
use App\Models\FeedPost;
use App\Services\AI\MediaAiDisclosureService;
use App\Services\GamificationService;
use App\Services\Gifs\GifProviderService;
use App\Services\MediaService;
use App\Services\MentionService;
use App\Services\NotificationService;
use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApiFeedController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = FeedPost::query()
            ->with([
                'user.profile',
                'team',
                'sharedPost.user.profile',
                'sharedPost.team',
            'sharedPost.viewerShare',
                'sharedPost.media.mediaAsset',
                'media.mediaAsset',
                'viewerReaction',
                'viewerBookmark',
            'viewerShare',
                'previewComments' => function ($query): void {
                    $query
                        ->with([
                            'user.profile',
                            'media.mediaAsset',
                            'viewerReaction',
                        ])
                        ->withCount('reactions');
                },
                'previewReactions.user.profile',
                'poll.options.votes',
                'poll.votes',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks'])
            ->withCount('sharedByPosts as shares_count')
            ->where('status', 'published')
            ->where(function ($query) use ($request): void {
                $query->where(function ($normalPosts) use ($request): void {
                    $normalPosts->whereNull('team_id')
                        ->where(function ($visibility) use ($request): void {
                            $visibility->whereIn('visibility', ['public', 'followers'])
                                ->orWhere(function ($private) use ($request): void {
                                    $private->where('visibility', 'private')
                                        ->where('user_id', $request->user()->id);
                                });
                        });
                })->orWhere(function ($teamPosts) use ($request): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', function ($teamQuery) use ($request): void {
                            $teamQuery->where('visibility', '!=', 'private')
                                ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $request->user()->id));
                        });
                });
            })
            ->when(
                $request->boolean('bookmarked'),
                fn ($query) => $query->whereHas(
                    'bookmarks',
                    fn ($bookmarkQuery) => $bookmarkQuery->where('user_id', $request->user()->id)
                )
            )
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest()
            ->paginate(20);

        return FeedPostResource::collection($posts);
    }

    public function show(Request $request, FeedPost $post): FeedPostResource
    {
        $post->loadMissing([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.viewerShare',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'viewerReaction',
            'viewerBookmark',
            'viewerShare',
                'previewComments' => function ($query): void {
                    $query
                        ->with([
                            'user.profile',
                            'media.mediaAsset',
                            'viewerReaction',
                        ])
                        ->withCount('reactions');
                },
                'previewReactions.user.profile',
            'poll.options.votes',
            'poll.votes',
        ]);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);

        return new FeedPostResource($post);
    }

    public function store(
        Request $request,
        MediaService $mediaService,
        MediaAiDisclosureService $aiDisclosure,
        GamificationService $gamification,
        MentionService $mentions,
        NotificationService $notifications,
        GifProviderService $gifs,
        FeedTranslationService $translations
    ): JsonResponse {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['required', 'string', 'in:public,followers,private'],
            'background_style' => ['nullable', 'string', 'in:none,bayou,blood,gold,night'],
            'feeling_key' => ['nullable', 'string', 'in:none,happy,excited,focused,chill,tired,salty'],
            'poll_question' => ['nullable', 'string', 'max:180'],
            'poll_options' => ['nullable', 'array', 'max:6'],
            'poll_options.*' => ['nullable', 'string', 'max:180'],
            'gif_provider' => ['nullable', 'string', 'max:40'],
            'gif_id' => ['nullable', 'string', 'max:255'],
            'gif_url' => ['nullable', 'url', 'max:2048'],
            'gif_preview_url' => ['nullable', 'url', 'max:2048'],
            'gif_title' => ['nullable', 'string', 'max:180'],
            'gif_source_url' => ['nullable', 'url', 'max:2048'],
            'media' => ['nullable', 'array', 'max:'.config('hunthub.upload_limits.feed_media_count', 12)],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:'.config('hunthub.upload_limits.feed_media_kb', 102400)],
            'ai_generated' => ['nullable', 'boolean'],
        ]);

        $files = $request->file('media', []);
        if ($files && ! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $mediaService->assertAllowed($file, $request->user(), 'feed');
        }

        $pollOptions = $this->validatedPollOptions($validated['poll_options'] ?? []);
        $pollQuestion = trim((string) ($validated['poll_question'] ?? ''));
        $hasPoll = count($pollOptions) >= 2;
        $gifSelection = $gifs->normalizeSelection($validated);
        $hasGif = $gifSelection !== null;

        if (! filled($validated['body'] ?? null) && count($files) === 0 && ! $hasPoll && ! $hasGif) {
            return response()->json([
                'message' => __('ui.feed_body_or_media_required'),
                'errors' => [
                    'body' => [__('ui.feed_body_or_media_required')],
                ],
            ], 422);
        }

        $postBody = $validated['body'] ?? null;
        $backgroundStyle = FeedPost::normalizeBackgroundStyle($validated['background_style'] ?? null);
        $feelingKey = FeedPost::normalizeFeelingKey($validated['feeling_key'] ?? null);
        $hasMediaUploads = count($files) > 0;
        $userDeclaredAi = $hasMediaUploads && $request->boolean('ai_generated');
        $aiDetection = $hasMediaUploads && ! $userDeclaredAi
            ? $aiDisclosure->analyzeUploads($files, 'feed')
            : null;

        $post = DB::transaction(function () use ($request, $validated, $files, $mediaService, $translations, $postBody, $backgroundStyle, $feelingKey, $pollQuestion, $pollOptions, $hasPoll, $gifSelection, $userDeclaredAi, $aiDetection): FeedPost {
            $post = FeedPost::create([
                'user_id' => $request->user()->id,
                'body' => $postBody,
                'background_style' => $backgroundStyle,
                'feeling_key' => $feelingKey,
                'gif_provider' => $gifSelection['gif_provider'] ?? null,
                'gif_id' => $gifSelection['gif_id'] ?? null,
                'gif_url' => $gifSelection['gif_url'] ?? null,
                'gif_preview_url' => $gifSelection['gif_preview_url'] ?? null,
                'gif_title' => $gifSelection['gif_title'] ?? null,
                'gif_source_url' => $gifSelection['gif_source_url'] ?? null,
                'source_language' => $translations->detectLanguage($postBody),
                'ai_user_declared' => $userDeclaredAi,
                'ai_detected_possible' => (bool) ($aiDetection['possible'] ?? false),
                'ai_detection_confidence' => $aiDetection ? (float) ($aiDetection['confidence'] ?? 0) : null,
                'ai_detection_reason' => $aiDetection['reason'] ?? null,
                'ai_detection_source' => $aiDetection['source'] ?? null,
                'ai_detection_model' => $aiDetection['model'] ?? null,
                'ai_detection_error' => $aiDetection['error'] ?? null,
                'ai_detection_checked_at' => $aiDetection ? now() : null,
                'visibility' => $validated['visibility'],
                'status' => 'published',
            ]);

            if ($hasPoll) {
                $poll = $post->poll()->create([
                    'question' => $pollQuestion !== '' ? $pollQuestion : null,
                    'allows_multiple' => false,
                ]);

                foreach ($pollOptions as $index => $optionBody) {
                    $poll->options()->create([
                        'body' => $optionBody,
                        'sort_order' => $index,
                    ]);
                }
            }

            foreach ($files as $index => $file) {
                $asset = $mediaService->store($file, $request->user(), 'feed', [
                    'attachable' => $post,
                    'visibility' => $validated['visibility'],
                ]);

                $post->media()->create([
                    'user_id' => $request->user()->id,
                    'media_asset_id' => $asset->id,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                    'mime_type' => $asset->mime_type,
                    'original_name' => $asset->original_name,
                    'size_bytes' => $asset->size_bytes,
                    'sort_order' => $index,
                ]);
            }

            return $post;
        });

        $gamification->award($request->user(), 'feed_post_created', source: $post);
        $mentions->syncForFeedPost($post, $request->user(), $post->body, $notifications);

        $post->loadMissing(['user.profile', 'media.mediaAsset', 'viewerReaction', 'viewerBookmark',
            'viewerShare', 'poll.options.votes', 'poll.votes']);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'message' => __('ui.feed_post_published'),
            'post' => new FeedPostResource($post),
        ], 201);
    }

    public function votePoll(Request $request, FeedPost $post): JsonResponse
    {
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 404);

        $validated = $request->validate([
            'poll_option_id' => ['required', 'integer'],
        ]);

        $poll = $post->poll()->with('options')->firstOrFail();
        abort_if($poll->isClosed(), 422, __('ui.feed_poll_closed'));

        $optionId = (int) $validated['poll_option_id'];
        $option = $poll->options->firstWhere('id', $optionId);
        abort_unless($option, 422);

        $poll->votes()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['feed_post_poll_option_id' => $option->id]
        );

        $post->loadMissing([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.viewerShare',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'viewerReaction',
            'viewerBookmark',
            'viewerShare',
            'poll.options.votes',
            'poll.votes',
        ]);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'message' => __('ui.feed_poll_vote_saved'),
            'post' => new FeedPostResource($post),
        ]);
    }

    public function update(
        Request $request,
        FeedPost $post,
        MentionService $mentions,
        NotificationService $notifications,
        GifProviderService $gifs,
        FeedTranslationService $translations
    ): JsonResponse {
        $viewer = $request->user();

        abort_unless(
            (int) $post->user_id === (int) $viewer->id
            || ($viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin()),
            403
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['nullable', 'string', 'in:public,followers,private,team'],
            'background_style' => ['nullable', 'string', 'in:none,bayou,blood,gold,night'],
            'feeling_key' => ['nullable', 'string', 'in:none,happy,excited,focused,chill,tired,salty'],
        ]);

        $visibility = (string) ($validated['visibility'] ?? $post->visibility ?? 'public');
        if ($post->isTeamPost()) {
            $visibility = 'team';
        } elseif ($visibility === 'team') {
            $visibility = 'public';
        }

        $body = trim((string) $validated['body']);
        $backgroundStyle = array_key_exists('background_style', $validated)
            ? FeedPost::normalizeBackgroundStyle($validated['background_style'] ?? null)
            : $post->background_style;
        $feelingKey = array_key_exists('feeling_key', $validated)
            ? FeedPost::normalizeFeelingKey($validated['feeling_key'] ?? null)
            : $post->feeling_key;

        $post->update([
            'body' => $body,
            'background_style' => $backgroundStyle,
            'feeling_key' => $feelingKey,
            'source_language' => $translations->detectLanguage($body),
            'visibility' => $visibility,
        ]);

        $post->translations()->delete();
        $mentions->syncForFeedPost($post, $request->user(), $post->body, $notifications);

        $post->loadMissing([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.viewerShare',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'viewerReaction',
            'viewerBookmark',
            'viewerShare',
            'poll.options.votes',
            'poll.votes',
        ]);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'message' => __('ui.feed_post_updated'),
            'post' => new FeedPostResource($post),
        ]);
    }

    public function destroy(Request $request, FeedPost $post, MediaService $mediaService): JsonResponse
    {
        $viewer = $request->user();

        abort_unless(
            (int) $post->user_id === (int) $viewer->id
            || ($viewer && method_exists($viewer, 'isAdmin') && $viewer->isAdmin()),
            403
        );

        $postId = (int) $post->id;
        $post->loadMissing(['media.mediaAsset']);

        foreach ($post->media as $media) {
            if ($media->mediaAsset) {
                $mediaService->delete($media->mediaAsset);
                continue;
            }

            Storage::disk($media->disk)->delete($media->path);
        }

        $post->delete();

        return response()->json([
            'ok' => true,
            'message' => __('ui.feed_post_deleted'),
            'id' => $postId,
        ]);
    }

    public function share(
        Request $request,
        FeedPost $post,
        GamificationService $gamification,
        MentionService $mentions,
        NotificationService $notifications,
        GifProviderService $gifs,
        FeedTranslationService $translations
    ): JsonResponse {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:1000'],
        ]);

        $post->loadMissing(['sharedPost', 'team']);

        $sourcePost = $post->sharedPost ?: $post;
        $sourcePost->loadMissing(['team']);

        abort_unless($sourcePost->canBeViewedBy($request->user()), 403);

        if (! $sourcePost->isTeamPost() && $sourcePost->visibility !== 'public') {
            return response()->json([
                'message' => __('ui.feed_public_only_share'),
            ], 422);
        }

        if ($sourcePost->isTeamPost() && $sourcePost->team?->visibility === 'private') {
            return response()->json([
                'message' => __('ui.feed_private_team_not_shareable'),
            ], 422);
        }

        $existingShare = FeedPost::query()
            ->where('user_id', $request->user()->id)
            ->where('shared_post_id', $sourcePost->id)
            ->first();

        if ($existingShare) {
            return response()->json([
                'message' => __('ui.feed_already_shared'),
            ], 409);
        }

        $shareText = trim((string) ($validated['body'] ?? ''));

        $share = FeedPost::create([
            'user_id' => $request->user()->id,
            'team_id' => $sourcePost->team_id,
            'shared_post_id' => $sourcePost->id,
            'body' => $shareText !== '' ? $shareText : null,
            'background_style' => null,
            'feeling_key' => null,
            'source_language' => $translations->detectLanguage($shareText !== '' ? $shareText : null),
            'visibility' => $sourcePost->isTeamPost() ? 'team' : 'public',
            'status' => 'published',
        ]);

        $gamification->award($request->user(), 'feed_post_shared', source: $share, description: 'Beitrag geteilt');
        $mentions->syncForFeedPost($share, $request->user(), $share->body, $notifications);

        $share->loadMissing([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.viewerShare',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'viewerReaction',
            'viewerBookmark',
            'viewerShare',
            'poll.options.votes',
            'poll.votes',
        ]);
        $share->loadCount(['comments', 'reactions', 'bookmarks']);
        $share->loadCount('sharedByPosts as shares_count');

        return response()->json([
            'message' => __('ui.feed_post_shared'),
            'post' => new FeedPostResource($share),
        ], 201);
    }


    private function validatedPollOptions(array $options): array
    {
        return collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter(fn (string $option) => $option !== '')
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }
}
