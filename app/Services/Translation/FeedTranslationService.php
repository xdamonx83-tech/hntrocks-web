<?php

namespace App\Services\Translation;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedCommentTranslation;
use App\Models\FeedPostTranslation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

        $translation = $this->translateTextResult((string) $post->body, $source, $target);

        return $post->translations()->create([
            'locale' => $target,
            'source_locale' => $source,
            'provider' => $translation['provider'],
            'translated_body' => $translation['text'],
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

        $translation = $this->translateTextResult((string) $comment->body, $source, $target);

        return $comment->translations()->create([
            'locale' => $target,
            'source_locale' => $source,
            'provider' => $translation['provider'],
            'translated_body' => $translation['text'],
        ]);
    }

    public function translateText(string $text, string $sourceLocale, string $targetLocale): string
    {
        return $this->translateTextResult($text, $sourceLocale, $targetLocale)['text'];
    }

    /**
     * @return array{text:string,provider:string}
     */
    private function translateTextResult(string $text, string $sourceLocale, string $targetLocale): array
    {
        $text = trim($text);
        $source = $this->supportedLocale($sourceLocale);
        $target = $this->supportedLocale($targetLocale);

        if ($text === '') {
            throw new RuntimeException('No text to translate.');
        }

        if (! $source || ! $target || $source === $target) {
            throw new RuntimeException('Unsupported translation direction.');
        }

        $provider = strtolower(trim((string) config('translation.provider', 'openai')));
        if (! in_array($provider, ['openai', 'local', 'local_first'], true)) {
            $provider = 'openai';
        }

        if (in_array($provider, ['local', 'local_first'], true)) {
            try {
                return $this->translateLocally($text, $source, $target);
            } catch (Throwable $exception) {
                Log::warning('Local feed translation failed.', [
                    'message' => $exception->getMessage(),
                    'source_locale' => $source,
                    'target_locale' => $target,
                ]);

                if (! $this->openAiFallbackEnabled()) {
                    throw new RuntimeException('Local translation failed.');
                }
            }
        }

        return $this->translateWithOpenAi($text, $source, $target);
    }

    /**
     * @return array{text:string,provider:string}
     */
    private function translateLocally(string $text, string $sourceLocale, string $targetLocale): array
    {
        $url = trim((string) config('translation.local_url', 'http://127.0.0.1:8787/translate'));
        if ($url === '') {
            throw new RuntimeException('No local translation URL configured.');
        }

        [$protectedText, $tokens] = $this->protectTokens($text);
        $request = Http::acceptJson()->timeout($this->localTimeoutSeconds());
        $token = trim((string) config('translation.local_token', ''));

        if ($token !== '') {
            $request = $request->withHeaders(['X-HNT-Translator-Token' => $token]);
        }

        $response = $request->post($url, [
            'q' => $protectedText,
            'source' => $sourceLocale,
            'target' => $targetLocale,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Local translation API error.');
        }

        $translated = trim((string) ($response->json('translatedText') ?: $response->json('translated_text') ?: ''));
        if ($translated === '') {
            throw new RuntimeException('Local translation API returned an empty response.');
        }

        $translated = $this->restoreTokens($translated, $tokens);
        if ($translated === '') {
            throw new RuntimeException('Local translation token restoration failed.');
        }

        return [
            'text' => $translated,
            'provider' => 'local_argos',
        ];
    }

    /**
     * @return array{text:string,provider:string}
     */
    private function translateWithOpenAi(string $text, string $sourceLocale, string $targetLocale): array
    {
        $apiKey = $this->apiKey();

        if ($apiKey === '') {
            throw new RuntimeException('No translation API key configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($this->openAiTimeoutSeconds())
                ->asJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->openAiModel(),
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

            return [
                'text' => $translated,
                'provider' => 'openai',
            ];
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            throw new RuntimeException('Translation failed.');
        }
    }

    /**
     * @return array{0:string,1:array<string,string>}
     */
    private function protectTokens(string $text): array
    {
        $tokens = [];
        $pattern = '~https?://[^\s<]+|www\.[^\s<]+|@[\pL\pN_.-]+|#[\pL\pN_]+|HNT\.ROCKS|Hunt:\s*Showdown|PlayStation(?:\s*[45])?|Xbox(?:\s+Series\s+[XS])?|Steam|Bounty Marks?|Bloodline|Hunter|Bounty|Extract|\r\n|\r|\n~iu';

        $protected = preg_replace_callback($pattern, function (array $match) use (&$tokens): string {
            $placeholder = 'ZQXHNTTOKEN'.str_pad((string) count($tokens), 4, '0', STR_PAD_LEFT).'QXZ';
            $tokens[$placeholder] = $match[0];

            return $placeholder;
        }, $text);

        return [is_string($protected) ? $protected : $text, $tokens];
    }

    /**
     * @param array<string,string> $tokens
     */
    private function restoreTokens(string $text, array $tokens): string
    {
        foreach ($tokens as $placeholder => $original) {
            if (str_contains($text, $placeholder)) {
                $text = str_replace($placeholder, $original, $text);
                continue;
            }

            $characters = preg_split('//u', $placeholder, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $flexible = '/'.implode('\\s*', array_map(static fn (string $character): string => preg_quote($character, '/'), $characters)).'/iu';
            $replaced = preg_replace_callback($flexible, static fn (): string => $original, $text, 1, $count);

            if (! is_string($replaced) || $count !== 1) {
                throw new RuntimeException('Protected translation token was changed.');
            }

            $text = $replaced;
        }

        return trim($text);
    }

    private function apiKey(): string
    {
        return trim((string) config('translation.openai_api_key', ''));
    }

    private function openAiModel(): string
    {
        return trim((string) config('translation.openai_model', 'gpt-4o-mini'));
    }

    private function openAiTimeoutSeconds(): int
    {
        return max(5, min(60, (int) config('translation.openai_timeout', 20)));
    }

    private function localTimeoutSeconds(): int
    {
        return max(1, min(30, (int) config('translation.local_timeout', 12)));
    }

    private function openAiFallbackEnabled(): bool
    {
        return (bool) config('translation.openai_fallback', true);
    }
}
