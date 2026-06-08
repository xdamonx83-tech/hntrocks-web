<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class MediaModerationService
{
    /** @var array<string, array<string, mixed>> */
    private array $results = [];

    public function assertAllowed(UploadedFile $file, ?User $user = null, string $context = 'library'): void
    {
        if (! (bool) config('hunthub.media_moderation.enabled', false)) {
            return;
        }

        $cacheKey = $this->cacheKey($file, $context);
        if (array_key_exists($cacheKey, $this->results)) {
            $this->throwIfBlocked($this->results[$cacheKey], $file, $context, $user);
            return;
        }

        $result = $this->scan($file, $context, $user);
        $this->results[$cacheKey] = $result;
        $this->throwIfBlocked($result, $file, $context, $user);
    }

    /** @return array<string, mixed> */
    private function scan(UploadedFile $file, string $context, ?User $user): array
    {
        $key = $this->apiKey();
        if ($key === null) {
            Log::warning('Media moderation is enabled, but no API key is configured.', [
                'context' => $context,
                'user_id' => $user?->id,
            ]);

            return $this->errorResult('missing_api_key');
        }

        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());

        try {
            if (str_starts_with($mimeType, 'image/')) {
                if (! (bool) config('hunthub.media_moderation.images', true)) {
                    return $this->allowedResult('images_disabled');
                }

                return $this->scanImages([$this->imagePayload($file)], $file, $context, $user, $key);
            }

            if (str_starts_with($mimeType, 'video/')) {
                if (! (bool) config('hunthub.media_moderation.videos', true)) {
                    return $this->allowedResult('videos_disabled');
                }

                $frames = $this->videoFramePayloads($file);
                if ($frames === []) {
                    return $this->errorResult('video_frame_extraction_failed');
                }

                return $this->scanImages($frames, $file, $context, $user, $key);
            }

            if ($this->isTextFile($file, $mimeType)) {
                if (! (bool) config('hunthub.media_moderation.texts', true)) {
                    return $this->allowedResult('texts_disabled');
                }

                return $this->scanText($this->readText($file), $file, $context, $user, $key);
            }
        } catch (Throwable $exception) {
            Log::warning('Media moderation scan failed.', [
                'context' => $context,
                'user_id' => $user?->id,
                'file' => $file->getClientOriginalName(),
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResult('scan_failed');
        }

        return $this->allowedResult('unsupported_mime');
    }

    /** @param array<int, array<string, string>> $imagePayloads @return array<string, mixed> */
    private function scanImages(array $imagePayloads, UploadedFile $file, string $context, ?User $user, string $key): array
    {
        $content = [[
            'type' => 'text',
            'text' => $this->instructionText($context, $file),
        ]];

        foreach ($imagePayloads as $payload) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $payload['data_url'],
                    'detail' => 'low',
                ],
            ];
        }

        return $this->callOpenAi($key, $content, $file, $context, $user);
    }

    /** @return array<string, mixed> */
    private function scanText(string $text, UploadedFile $file, string $context, ?User $user, string $key): array
    {
        $content = [[
            'type' => 'text',
            'text' => $this->instructionText($context, $file)."\n\nDateitext:\n".mb_substr($text, 0, 12000),
        ]];

        return $this->callOpenAi($key, $content, $file, $context, $user);
    }

    /** @param array<int, array<string, mixed>> $content @return array<string, mixed> */
    private function callOpenAi(string $key, array $content, UploadedFile $file, string $context, ?User $user): array
    {
        $response = Http::withToken($key)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('hunthub.media_moderation.timeout', 30))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => (string) config('hunthub.media_moderation.model', 'gpt-4o-mini'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [[
                    'role' => 'system',
                    'content' => 'You are a strict upload safety classifier. Return only valid JSON.',
                ], [
                    'role' => 'user',
                    'content' => $content,
                ]],
            ]);

        if (! $response->successful()) {
            Log::warning('Media moderation API returned an error.', [
                'context' => $context,
                'user_id' => $user?->id,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 1000),
            ]);

            return $this->errorResult('api_error');
        }

        $raw = (string) data_get($response->json(), 'choices.0.message.content', '');
        $result = json_decode($raw, true);

        if (! is_array($result)) {
            Log::warning('Media moderation API returned invalid JSON.', [
                'context' => $context,
                'user_id' => $user?->id,
                'body' => mb_substr($raw, 0, 1000),
            ]);

            return $this->errorResult('invalid_api_response');
        }

        $confidence = (float) ($result['confidence'] ?? 0);
        $decision = strtolower((string) ($result['decision'] ?? 'allow'));
        $category = strtolower((string) ($result['category'] ?? 'safe'));
        $reason = (string) ($result['reason'] ?? '');
        $threshold = (float) config('hunthub.media_moderation.block_confidence', 0.74);

        $blocked = $decision === 'block' && $confidence >= $threshold;
        if ($decision === 'review') {
            $blocked = (bool) config('hunthub.media_moderation.block_review', false) && $confidence >= $threshold;
        }

        $normalized = [
            'blocked' => $blocked,
            'decision' => $decision,
            'category' => $category,
            'confidence' => $confidence,
            'reason' => $reason,
            'source' => 'openai',
        ];

        if ($blocked) {
            Log::warning('Media upload blocked by moderation.', [
                'context' => $context,
                'user_id' => $user?->id,
                'file' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'category' => $category,
                'confidence' => $confidence,
                'reason' => $reason,
            ]);
        }

        return $normalized;
    }

    private function instructionText(string $context, UploadedFile $file): string
    {
        return <<<'TEXT'
Classify this user upload for a Hunt: Showdown community platform.

Block only when the upload clearly contains one of these categories:
- pornography, explicit sexual content, sexual nudity, or fetish sexual content
- racist symbols, racist text/slurs, white supremacist imagery, Nazi/neo-Nazi symbols, extremist hate propaganda
- hateful content targeting protected classes

Do NOT block normal Hunt: Showdown/game content just because it contains:
- blood, gore-like game visuals, weapons, monsters, horror, dark western atmosphere, skulls, violence, bounty screens, game screenshots

Return JSON exactly with:
{
  "decision": "allow" | "review" | "block",
  "category": "safe" | "sexual" | "hate_racism" | "extremism" | "other",
  "confidence": 0.0,
  "reason": "short German reason"
}
TEXT;
    }

    /** @return array<string, string> */
    private function imagePayload(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            throw new \RuntimeException('Upload file is not readable.');
        }

        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
        $data = base64_encode((string) file_get_contents($path));

        return ['data_url' => 'data:'.$mimeType.';base64,'.$data];
    }

    /** @return array<int, array<string, string>> */
    private function videoFramePayloads(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            return [];
        }

        $ffmpeg = (string) config('hunthub.media_moderation.ffmpeg_binary', 'ffmpeg');
        $frames = [];
        $tmpDir = storage_path('app/tmp/media-moderation/'.uniqid('video_', true));
        if (! is_dir($tmpDir) && ! mkdir($tmpDir, 0775, true) && ! is_dir($tmpDir)) {
            return [];
        }

        $seconds = (array) config('hunthub.media_moderation.video_sample_seconds', [1, 3, 7]);

        foreach ($seconds as $index => $second) {
            $frame = $tmpDir.'/frame_'.$index.'.jpg';
            $command = sprintf(
                '%s -y -ss %s -i %s -frames:v 1 -vf scale=512:-1 %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg((string) $second),
                escapeshellarg($path),
                escapeshellarg($frame)
            );

            @exec($command, $output, $code);

            if ($code === 0 && is_file($frame) && filesize($frame) > 0) {
                $frames[] = ['data_url' => 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($frame))];
            }
        }

        foreach (glob($tmpDir.'/*') ?: [] as $tmpFile) {
            @unlink($tmpFile);
        }
        @rmdir($tmpDir);

        return $frames;
    }

    private function isTextFile(UploadedFile $file, string $mimeType): bool
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return $extension === 'txt' || str_starts_with($mimeType, 'text/');
    }

    private function readText(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path, false, null, 0, 200000);
    }

    /** @return array<string, mixed> */
    private function allowedResult(string $reason): array
    {
        return [
            'blocked' => false,
            'decision' => 'allow',
            'category' => 'safe',
            'confidence' => 0.0,
            'reason' => $reason,
            'source' => 'local',
        ];
    }

    /** @return array<string, mixed> */
    private function errorResult(string $reason): array
    {
        return [
            'blocked' => (bool) config('hunthub.media_moderation.block_on_error', false),
            'decision' => (bool) config('hunthub.media_moderation.block_on_error', false) ? 'block' : 'allow',
            'category' => 'other',
            'confidence' => 0.0,
            'reason' => $reason,
            'source' => 'local',
        ];
    }

    /** @param array<string, mixed> $result */
    private function throwIfBlocked(array $result, UploadedFile $file, string $context, ?User $user): void
    {
        if (! (bool) ($result['blocked'] ?? false)) {
            return;
        }

        $category = (string) ($result['category'] ?? 'other');
        $message = match ($category) {
            'sexual' => 'Der Upload wurde blockiert, weil er als sexueller/NSFW-Inhalt erkannt wurde.',
            'hate_racism', 'extremism' => 'Der Upload wurde blockiert, weil rassistische, extremistische oder hasserfüllte Inhalte erkannt wurden.',
            default => 'Der Upload wurde durch die Sicherheitsprüfung blockiert.',
        };

        Log::warning('Media moderation blocked upload validation.', [
            'context' => $context,
            'user_id' => $user?->id,
            'file' => $file->getClientOriginalName(),
            'result' => $result,
        ]);

        throw ValidationException::withMessages([
            'media' => [$message],
            'files' => [$message],
            'avatar' => [$message],
            'cover' => [$message],
            'screenshot' => [$message],
            'video' => [$message],
        ]);
    }

    private function cacheKey(UploadedFile $file, string $context): string
    {
        $path = (string) $file->getRealPath();
        $size = (string) ($file->getSize() ?: 0);

        return sha1($context.'|'.$path.'|'.$size.'|'.$file->getClientOriginalName());
    }

    private function apiKey(): ?string
    {
        $key = (string) (config('hunthub.media_moderation.openai_api_key') ?: '');

        return $key !== '' ? $key : null;
    }
}
