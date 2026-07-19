<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Admin\AdminThemePreviewController;
use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DashboardSinglePostLiveController extends Controller
{
    public function __invoke(Request $request, FeedPost $post): SymfonyResponse
    {
        $viewer = $request->user();

        abort_unless($viewer, 401);

        $post->loadMissing([
            'user.profile',
            'team',
            'cupTeam.cup',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.cupTeam.cup',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'comments.user.profile',
            'comments.media.mediaAsset',
            'comments.reactions',
            'comments.viewerReaction',
            'reactions',
            'viewerReaction',
            'viewerBookmark',
            'poll.options.votes',
            'poll.votes',
        ]);
        $post->loadCount(['comments', 'reactions', 'bookmarks']);
        $post->loadCount('sharedByPosts as shares_count');

        abort_unless($post->status === 'published' && $post->canBeViewedBy($viewer), 404);

        if ($request->boolean('data')) {
            return $this->dataResponse($request, $post);
        }

        return $this->renderPage($request, $post);
    }

    private function dataResponse(Request $request, FeedPost $post): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $payload = $this->invokePrivate(
            app(AdminThemePreviewController::class),
            'serializePreviewPost',
            [$post, $viewerId, true]
        );

        if ($request->filled('post_id')) {
            return response()->json([
                'post' => $payload,
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()->json([
            'mode' => 'single',
            'posts' => [$payload],
            'pagination' => [
                'page' => 1,
                'per_page' => 1,
                'total' => 1,
                'has_more' => false,
                'next_page' => null,
            ],
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
    }

    private function renderPage(Request $request, FeedPost $post): Response
    {
        /** @var Response $response */
        $response = $this->invokePrivate(
            app(DashboardFeedLiveController::class),
            'renderDashboard',
            [$request]
        );

        $html = (string) $response->getContent();
        $author = $post->user;
        $authorName = $author?->name ?: ($author?->username ?: 'HNT Hunter');
        $excerpt = trim((string) $post->excerpt(160));
        $title = Str::limit($excerpt !== '' ? $excerpt : 'Community-Beitrag', 70, '…').' · HNT.ROCKS';
        $description = $excerpt !== '' ? $excerpt : 'Beitrag, Reaktionen und Kommentare auf HNT.ROCKS.';

        $html = str_replace(
            '<body data-page="feed">',
            '<body data-page="feed" class="hnt-single-post-page">',
            $html
        );

        $html = preg_replace(
            '~<a class="([^"]*)" data-page="feed"~',
            '<a class="$1 active" data-page="feed"',
            $html,
            1
        ) ?: $html;

        $html = preg_replace(
            '~<section class="salary-attendance-card personal-dashboard-card">.*?</section>(?=\s*<section class="social-feed-card">)~s',
            '',
            $html,
            1
        ) ?: $html;

        $singleHeader = '<header class="social-feed-head single-post-feed-head">'
            .'<div><span class="eyebrow">HNT.ROCKS</span><h2>'.e(__('ui.feed_post')).'</h2></div>'
            .'<a class="single-post-back" href="'.e(route('feed.index')).'">'
            .'<svg><use href="#i-arrow"></use></svg><span>'.e(__('ui.back_to_feed')).'</span>'
            .'</a></header>';

        $html = preg_replace(
            '~<header class="social-feed-head">.*?</header>~s',
            $singleHeader,
            $html,
            1
        ) ?: $html;

        $html = preg_replace(
            '~<script[^>]+src="[^"]*/real-feed\.js\?v=[^"]+"[^>]*></script>~i',
            '',
            $html
        ) ?: $html;
        $html = preg_replace(
            '~<script[^>]+real-dashboard-progress-live\.js[^>]*></script>~i',
            '',
            $html
        ) ?: $html;

        $stylePath = public_path('assets/themes/hnt_preview/dashboard-feed/single-post-live.css');
        $styleVersion = is_file($stylePath) ? filemtime($stylePath) : time();
        $scriptPath = public_path('assets/themes/hnt_preview/dashboard-feed/single-post-live.js');
        $scriptVersion = is_file($scriptPath) ? filemtime($scriptPath) : time();

        $head = '<meta name="description" content="'.e($description).'">'
            .'<meta property="og:title" content="'.e($title).'">'
            .'<meta property="og:description" content="'.e($description).'">'
            .'<meta property="og:url" content="'.e($post->permalink()).'">'
            .'<script>window.HNT_SINGLE_POST_PAGE=true;window.HNT_SINGLE_POST_ID='.(int) $post->id.';</script>'
            .'<link href="'.asset('assets/themes/hnt_preview/dashboard-feed/single-post-live.css').'?v='.$styleVersion.'" rel="stylesheet">';

        $html = preg_replace('~<title>.*?</title>~s', '<title>'.e($title).'</title>', $html, 1) ?: $html;
        $html = str_replace('</head>', $head.'</head>', $html);
        $html = str_replace(
            '</body>',
            '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/single-post-live.js').'?v='.$scriptVersion.'"></script></body>',
            $html
        );

        $response->setContent($html);

        return $response;
    }

    private function invokePrivate(object $instance, string $method, array $arguments = []): mixed
    {
        if (! method_exists($instance, $method)) {
            throw new RuntimeException(sprintf(
                'Dashboard single-post dependency %s::%s is unavailable.',
                $instance::class,
                $method
            ));
        }

        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($instance, $arguments);
    }
}
