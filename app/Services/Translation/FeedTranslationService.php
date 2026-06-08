<?php

namespace App\Services\Translation;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedCommentTranslation;
use App\Models\FeedPostTranslation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FeedTranslationService
{
    public function supportedLocale(?string $locale): ?string
    {
        $locale = strtolower(substr((string) $locale, 0, 2));

        return in_array($locale, ['de', 'en'], true) ? $locale : null;
    }

    public function shouldOfferTranslation(?string $text, ?string $sourceLanguage, ?string $targetLocale): bool
    {
        $target = $this->supportedLocale($targetLocale);

        if (! $target || trim(strip_tags((string) $text)) === '') {
            return false;
        }

        $source = $this->supportedLocale($sourceLanguage) ?: $this->detectLanguage($text);

        return $source !== null && $source !== $target;
    }

    public function detectLanguage(?string $text): ?string
    {
        $text = trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($text === '') {
            return null;
        }

        $lower = strtolower($text);

        if (preg_match('/[äöüß]/iu', $text)) {
            return 'de';
        }

        preg_match_all('/[a-zäöüß]{2,}/iu', $lower, $matches);
        $words = $matches[0] ?? [];

        if (count($words) < 2 && strlen($lower) < 12) {
            return null;
        }

        $deWords = [
            'der','die','das','den','dem','des','und','oder','aber','nicht','kein','keine','ist','sind','war','wird','werden','ich','du','wir','ihr','sie','mein','dein','mit','für','auf','zum','zur','von','wie','was','wenn','dann','auch','noch','nur','schon','einen','eine','einem','einer','habe','hat','haben','kann','können','muss','müssen','heute','morgen','spieler','jagd','beute','punkte','hochladen','gewinnen','teilnehmen'
        ];

        $enWords = [
            'the','and','or','but','not','no','is','are','was','were','will','would','can','could','i','you','we','they','my','your','with','for','on','to','from','of','in','this','that','these','those','if','then','also','just','already','have','has','had','player','hunt','bounty','points','upload','win','join','today','tomorrow','community','cup'
        ];

        $deScore = 0;
        $enScore = 0;

        foreach ($words as $word) {
            $word = strtolower($word);

            if (in_array($word, $deWords, true)) {
                $deScore += 2;
            }

            if (in_array($word, $enWords, true)) {
                $enScore += 2;
            }
        }

        if (preg_match('/\b(ich|du|wir|nicht|und|oder|aber|für|dass|wenn|spieler|punkte)\b/i', $lower)) {
            $deScore++;
        }

        if (preg_match('/\b(the|you|and|not|with|for|that|this|player|points)\b/i', $lower)) {
            $enScore++;
        }

        if ($deScore >= $enScore + 2) {
            return 'de';
        }

        if ($enScore >= $deScore + 2) {
            return 'en';
        }

        return null;
    }

    public function translationMeta(?string $text, ?string $sourceLanguage, ?string $targetLocale): array
    {
        $target = $this->supportedLocale($targetLocale) ?: 'de';
        $source = $this->supportedLocale($sourceLanguage) ?: $this->detectLanguage($text);

        return [
            'target_locale' => $target,
            'source_locale' => $source,
            'should_offer' => $source !== null && $source !== $target && trim(strip_tags((string) $text)) !== '',
        ];
    }

    public function translatePost(FeedPost $post, string $targetLocale): FeedPostTranslation
    {
        $target = $this->supportedLocale($targetLocale) ?: 'de';

        $existing = $post->translations()->where('locale', $target)->first();
        if ($existing) {
            return $existing;
        }

        $source = $this->supportedLocale($post->source_language) ?: $this->detectLanguage($post->body);

        if ($source && $post->source_language !== $source) {
            $post->forceFill(['source_language' => $source]);
            $post->timestamps = false;
            $post->saveQuietly();
        }

        if (! $source || $source === $target) {
            throw new RuntimeException('Translation is not needed for this post.');
        }

        $translated = $this->translateText((string) $post->body, $source, $target);

        return $post->translations()->create([
            'locale' => $target,
            'source_locale' => $source,
            'provider' => 'openai',
            'translated_body' => $translated,
        ]);
    }

    public function translateComment(FeedComment $comment, string $targetLocale): FeedCommentTranslation
    {
        $target = $this->supportedLocale($targetLocale) ?: 'de';

        $existing = $comment->translations()->where('locale', $target)->first();
        if ($existing) {
            return $existing;
        }

        $source = $this->supportedLocale($comment->source_language) ?: $this->detectLanguage($comment->body);

        if ($source && $comment->source_language !== $source) {
            $comment->forceFill(['source_language' => $source]);
            $comment->timestamps = false;
            $comment->saveQuietly();
        }

        if (! $source || $source === $target) {
            throw new RuntimeException('Translation is not needed for this comment.');
        }

        $translated = $this->translateText((string) $comment->body, $source, $target);

        return $comment->translations()->create([
            'locale' => $target,
            'source_locale' => $source,
            'provider' => 'openai',
            'translated_body' => $translated,
        ]);
    }

    public function translateText(string $text, string $sourceLocale, string $targetLocale): string
    {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('No text to translate.');
        }

        $apiKey = $this->apiKey();

        if ($apiKey === '') {
            throw new RuntimeException('No translation API key configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($this->timeoutSeconds())
                ->asJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model(),
                    'temperature' => 0.1,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You translate user-generated community posts for hnt.rocks. Translate only between German and English. Preserve usernames, @mentions, URLs, emojis, line breaks, Hunt: Showdown terms, platform names and profanity tone. Return only the translated text, no explanation.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Translate from {$sourceLocale} to {$targetLocale}:\n\n" . $text,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Feed translation API returned an error.', [
                    'status' => $response->status(),
                    'body' => substr((string) $response->body(), 0, 500),
                ]);

                throw new RuntimeException('Translation API error.');
            }

            $translated = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            if ($translated === '') {
                throw new RuntimeException('Translation API returned an empty response.');
            }

            return $translated;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw new RuntimeException('Translation failed.');
        }
    }

    private function apiKey(): string
    {
        return trim((string) (env('HH_TRANSLATION_OPENAI_API_KEY')
            ?: env('HH_OPENAI_API_KEY')
            ?: env('OPENAI_API_KEY')
            ?: env('HH_MEDIA_OPENAI_API_KEY')
            ?: env('HH_CUP_OPENAI_API_KEY')
            ?: ''));
    }

    private function model(): string
    {
        return trim((string) (env('HH_TRANSLATION_OPENAI_MODEL') ?: 'gpt-4o-mini'));
    }

    private function timeoutSeconds(): int
    {
        return max(5, min(60, (int) (env('HH_TRANSLATION_OPENAI_TIMEOUT') ?: 20)));
    }
}
