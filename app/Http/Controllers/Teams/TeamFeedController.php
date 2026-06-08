<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\Team;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\AI\MediaAiDisclosureService;
use App\Services\MentionService;
use App\Services\NotificationService;
use App\Services\Translation\FeedTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamFeedController extends Controller
{
    public function store(Request $request, Team $team, MediaService $mediaService, MediaAiDisclosureService $aiDisclosure, GamificationService $gamification, MentionService $mentions, NotificationService $notifications, FeedTranslationService $translations): RedirectResponse|JsonResponse
    {
        $team->loadMissing('members');
        abort_unless($team->isActiveMember($request->user()), 403);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'background_style' => ['nullable', 'string', 'in:none,bayou,blood,gold,night'],
            'feeling_key' => ['nullable', 'string', 'in:none,happy,excited,focused,chill,tired,salty'],
            'media' => ['nullable', 'array', 'max:'.config('hunthub.upload_limits.team_feed_media_count', 12)],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov', 'max:'.config('hunthub.upload_limits.team_feed_media_kb', 51200)],
            'ai_generated' => ['nullable', 'boolean'],
        ]);

        $files = $request->file('media', []);

        foreach ($files as $file) {
            $mediaService->assertAllowed($file, $request->user(), 'team_feed');
        }

        if (! filled($validated['body'] ?? null) && count($files) === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('ui.feed_body_or_media_required'),
                    'errors' => [
                        'body' => [__('ui.feed_body_or_media_required')],
                    ],
                ], 422);
            }

            return redirect()
                ->route('teams.show', $team)
                ->withErrors(['body' => __('ui.feed_body_or_media_required')])
                ->withInput();
        }

        $postBody = $validated['body'] ?? null;
        $backgroundStyle = FeedPost::normalizeBackgroundStyle($validated['background_style'] ?? null);
        $feelingKey = FeedPost::normalizeFeelingKey($validated['feeling_key'] ?? null);
        $hasMediaUploads = count($files) > 0;
        $userDeclaredAi = $hasMediaUploads && $request->boolean('ai_generated');
        $aiDetection = $hasMediaUploads && ! $userDeclaredAi
            ? $aiDisclosure->analyzeUploads($files, 'team_feed')
            : null;

        $post = FeedPost::create([
            'user_id' => $request->user()->id,
            'team_id' => $team->id,
            'body' => $postBody,
            'background_style' => $backgroundStyle,
            'feeling_key' => $feelingKey,
            'source_language' => $translations->detectLanguage($postBody),
            'ai_user_declared' => $userDeclaredAi,
            'ai_detected_possible' => (bool) ($aiDetection['possible'] ?? false),
            'ai_detection_confidence' => $aiDetection ? (float) ($aiDetection['confidence'] ?? 0) : null,
            'ai_detection_reason' => $aiDetection['reason'] ?? null,
            'ai_detection_source' => $aiDetection['source'] ?? null,
            'ai_detection_model' => $aiDetection['model'] ?? null,
            'ai_detection_error' => $aiDetection['error'] ?? null,
            'ai_detection_checked_at' => $aiDetection ? now() : null,
            'visibility' => 'team',
            'status' => 'published',
        ]);

        foreach ($files as $index => $file) {
            $asset = $mediaService->store($file, $request->user(), 'team_feed', [
                'attachable' => $post,
                'visibility' => $team->visibility,
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

        $gamification->award($request->user(), 'feed_post_created', source: $post, description: __('ui.team_feed_post_created_description'));
        $mentions->syncForFeedPost($post, $request->user(), $post->body, $notifications);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.team_feed_post_published'),
                'redirect_url' => route('teams.show', $team),
                'post_id' => $post->id,
            ]);
        }

        return redirect()->route('teams.show', $team)->with('status', __('ui.team_feed_post_published'));
    }
}
