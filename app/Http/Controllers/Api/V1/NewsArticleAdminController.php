<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NewsArticleResource;
use App\Http\Resources\Api\NewsArticleRevisionResource;
use App\Models\NewsArticle;
use App\Models\NewsArticleRevision;
use App\Models\User;
use App\Rules\NewsContentBlocks;
use App\Services\NewsArticleEditorService;
use App\Services\Translation\NewsArticleTranslationService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsArticleAdminController extends Controller
{
    public function access(): JsonResponse
    {
        return response()->json(['data' => ['can_manage' => true]]);
    }

    public function index(Request $request): JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = NewsArticle::query()
            ->with(['translations', 'heroMedia'])
            ->withCount('revisions');

        $status = $request->query('status');
        if (is_string($status) && in_array($status, NewsArticle::statuses(), true)) {
            $query->where('status', $status);
        }

        $locale = $request->query('locale');
        if (is_string($locale) && in_array($locale, NewsArticle::LOCALES, true)) {
            $query->whereHas('translations', fn ($translationQuery) => $translationQuery->where('locale', $locale));
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->whereHas('translations', fn ($translationQuery) => $translationQuery
                ->where(fn ($textQuery) => $textQuery
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')));
        }

        return NewsArticleResource::collection($query->orderByDesc('updated_at')->paginate(25)->withQueryString());
    }

    public function store(Request $request, NewsArticleEditorService $editor): JsonResponse
    {
        $data = $this->validatedEditorData($request, null, false);
        $article = $editor->create($this->adminUser($request), $data)->load(['translations', 'heroMedia'])->loadCount('revisions');

        return NewsArticleResource::make($article)->response()->setStatusCode(201);
    }

    public function show(NewsArticle $article): NewsArticleResource
    {
        return NewsArticleResource::make($article->load(['translations', 'heroMedia'])->loadCount('revisions'));
    }

    public function update(Request $request, NewsArticle $article, NewsArticleEditorService $editor): NewsArticleResource
    {
        $data = $this->validatedEditorData($request, $article, true);
        $version = (int) $data['lock_version'];
        unset($data['lock_version']);

        return NewsArticleResource::make($editor->autosave($article, $this->adminUser($request), $version, $data)->loadCount('revisions'));
    }

    public function workflow(Request $request, NewsArticle $article, NewsArticleEditorService $editor): NewsArticleResource
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'schedule', 'publish', 'archive'])],
            'lock_version' => ['required', 'integer', 'min:1'],
            'scheduled_at' => ['required_if:action,schedule', 'prohibited_unless:action,schedule', 'date', 'after:now'],
        ]);

        $updated = $editor->transition(
            $article,
            $this->adminUser($request),
            (int) $data['lock_version'],
            $data['action'],
            $data['scheduled_at'] ?? null,
        );

        return NewsArticleResource::make($updated->load(['translations', 'heroMedia'])->loadCount('revisions'));
    }

    public function translate(
        Request $request,
        NewsArticle $article,
        NewsArticleTranslationService $translationService,
    ): JsonResponse {
        $data = $request->validate([
            'lock_version' => ['required', 'integer', 'min:1'],
            'source_locale' => ['required', Rule::in(NewsArticle::LOCALES)],
            'target_locales' => ['required', 'array', 'min:1', 'max:3'],
            'target_locales.*' => ['required', 'string', 'distinct', Rule::in(NewsArticle::LOCALES)],
            'overwrite' => ['sometimes', 'boolean'],
        ]);

        $result = $translationService->translateArticle(
            $article,
            $this->adminUser($request),
            (int) $data['lock_version'],
            (string) $data['source_locale'],
            $data['target_locales'],
            (bool) ($data['overwrite'] ?? false),
        );

        return response()->json([
            'data' => [
                'article' => (new NewsArticleResource($result['article']))->resolve($request),
                'translated_locales' => $result['translated_locales'],
                'skipped_locales' => $result['skipped_locales'],
            ],
        ]);
    }

    public function revisions(NewsArticle $article): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $revisions = $article->revisions()->with('editor')->paginate(50);

        return NewsArticleRevisionResource::collection($revisions);
    }

    public function showRevision(NewsArticle $article, NewsArticleRevision $revision): NewsArticleRevisionResource
    {
        $revision = $article->revisions()->with('editor')->whereKey($revision->id)->firstOrFail();

        return NewsArticleRevisionResource::make($revision);
    }

    public function restoreRevision(Request $request, NewsArticle $article, NewsArticleRevision $revision, NewsArticleEditorService $editor): NewsArticleResource
    {
        $data = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $restored = $editor->restoreRevision($article, $revision, $this->adminUser($request), (int) $data['lock_version']);

        return NewsArticleResource::make($restored->load(['translations', 'heroMedia'])->loadCount('revisions'));
    }

    private function validatedEditorData(Request $request, ?NewsArticle $article, bool $requireVersion): array
    {
        $translations = $request->input('translations', []);
        if (is_array($translations)) {
            $unsupported = array_diff(array_keys($translations), NewsArticle::LOCALES);
            if ($unsupported !== []) {
                throw ValidationException::withMessages(['translations' => 'Only de, en, es, and ru translations are supported.']);
            }
        }

        $rules = [
            'status' => ['prohibited'],
            'scheduled_at' => ['prohibited'],
            'published_at' => ['prohibited'],
            'archived_at' => ['prohibited'],
            'category_key' => ['sometimes', 'nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'hero_media_asset_id' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('media_assets', 'id')->where(fn (QueryBuilder $query) => $query->where('context', 'news')->whereIn('type', ['image', 'video'])),
            ],
            'featured' => ['sometimes', 'boolean'],
            'comments_enabled' => ['sometimes', 'boolean'],
            'translations' => ['sometimes', 'array', 'max:4'],
            'translations.*' => ['array'],
            'translations.*.title' => ['sometimes', 'nullable', 'string', 'max:240'],
            'translations.*.excerpt' => ['sometimes', 'nullable', 'string', 'max:1200'],
            'translations.*.content_json' => ['sometimes', 'nullable', new NewsContentBlocks()],
            'translations.*.seo_title' => ['sometimes', 'nullable', 'string', 'max:240'],
            'translations.*.seo_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'translations.*.canonical_url' => ['sometimes', 'nullable', 'url:https', 'max:2048'],
        ];

        foreach (array_keys(is_array($translations) ? $translations : []) as $locale) {
            $existingId = $article?->translations()->where('locale', $locale)->value('id');
            $rules["translations.{$locale}.slug"] = [
                'sometimes', 'nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('news_article_translations', 'slug')
                    ->where(fn (QueryBuilder $query) => $query->where('locale', $locale))
                    ->ignore($existingId),
            ];
        }

        if ($requireVersion) {
            $rules['lock_version'] = ['required', 'integer', 'min:1'];
        } else {
            $rules['lock_version'] = ['prohibited'];
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    private function adminUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isAdmin(), 403);

        return $user;
    }
}
