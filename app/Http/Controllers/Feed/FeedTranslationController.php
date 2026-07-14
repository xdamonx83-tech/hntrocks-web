<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Services\Translation\FeedTranslationService;
use App\Support\FeedTextRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FeedTranslationController extends Controller
{
    public function post(Request $request, FeedPost $post, FeedTranslationService $translations): JsonResponse
    {
        abort_unless($post->status === 'published' && $post->canBeViewedBy($request->user()), 403);

        $locale = $translations->supportedLocale($request->input('locale') ?: app()->getLocale()) ?: 'de';

        try {
            $translation = $translations->translatePost($post, $locale);
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.translation_unavailable'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'source_locale' => $translation->source_locale,
            'target_locale' => $translation->locale,
            'source_label' => $this->languageLabel($translation->source_locale),
            'target_label' => $this->languageLabel($translation->locale),
            'translated_body' => $translation->translated_body,
            'translated_html' => FeedTextRenderer::render($translation->translated_body),
            'provider_label' => $this->automaticTranslationLabel(),
            'meta_label' => __('ui.translation_meta', [
                'language' => $this->languageLabel($translation->source_locale),
            ]),
        ]);
    }

    public function comment(Request $request, FeedComment $comment, FeedTranslationService $translations): JsonResponse
    {
        $comment->loadMissing('post');
        $post = $comment->post;

        abort_unless($post && $post->status === 'published' && $post->canBeViewedBy($request->user()), 403);

        $locale = $translations->supportedLocale($request->input('locale') ?: app()->getLocale()) ?: 'de';

        try {
            $translation = $translations->translateComment($comment, $locale);
        } catch (RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.translation_unavailable'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'source_locale' => $translation->source_locale,
            'target_locale' => $translation->locale,
            'source_label' => $this->languageLabel($translation->source_locale),
            'target_label' => $this->languageLabel($translation->locale),
            'translated_body' => $translation->translated_body,
            'translated_html' => FeedTextRenderer::render($translation->translated_body),
            'provider_label' => $this->automaticTranslationLabel(),
            'meta_label' => __('ui.translation_meta', [
                'language' => $this->languageLabel($translation->source_locale),
            ]),
        ]);
    }

    private function languageLabel(?string $locale): string
    {
        return match (strtolower((string) $locale)) {
            'en' => __('ui.language_english'),
            'de' => __('ui.language_german'),
            default => __('ui.translation_language_unknown'),
        };
    }

    private function automaticTranslationLabel(): string
    {
        return app()->getLocale() === 'en'
            ? 'Automatically translated'
            : 'Automatisch übersetzt';
    }
}
