<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MediaAiDisclosureService
{
    /**
     * @param array<int, UploadedFile> $files
     * @return array<string, mixed>
     */
    public function analyzeUploads(array $files, string $context = 'feed'): array
    {
        if (! (bool) config('hunthub.ai_content_disclosure.enabled', true)) {
            return $this->baseResult(error: 'disabled');
        }

        $key = $this->apiKey();
        if ($key === null) {
            Log::info('AI content disclosure check skipped because no API key is configured.', [
                'context' => $context,
            ]);

            return $this->baseResult(error: 'missing_api_key');
        }

        $maxFiles = max(1, (int) config('hunthub.ai_content_disclosure.max_files', 4));
        $payloads = [];
        $scannedFiles = 0;

        foreach (array_slice($files, 0, $maxFiles) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: '');

            try {
                if (str_starts_with($mimeType, 'image/')) {
                    if (! (bool) config('hunthub.ai_content_disclosure.images', true)) {
                        continue;
                    }

                    $payload = $this->imagePayload($file);
                    if ($payload !== null) {
                        $payloads[] = $payload;
                        $scannedFiles++;
                    }

                    continue;
                }

                if (str_starts_with($mimeType, 'video/')) {
                    if (! (bool) config('hunthub.ai_content_disclosure.videos', true)) {
                        continue;
                    }

                    $frames = $this->videoFramePayloads($file);
                    if ($frames !== []) {
                        array_push($payloads, ...$frames);
                        $scannedFiles++;
                    }
                }
            } catch (Throwable $exception) {
                Log::warning('AI content disclosure payload preparation failed.', [
                    'context' => $context,
                    'file' => $file->getClientOriginalName(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($payloads === []) {
            return $this->baseResult(error: 'no_supported_media');
        }

        $result = $this->callOpenAi($key, $payloads, $context, (string) config('hunthub.ai_content_disclosure.model', 'gpt-5-nano'));

        if (($result['error'] ?? null) !== null) {
            $fallbackModel = (string) config('hunthub.ai_content_disclosure.fallback_model', 'gpt-4o-mini');
            $primaryModel = (string) config('hunthub.ai_content_disclosure.model', 'gpt-5-nano');

            if ($fallbackModel !== '' && $fallbackModel !== $primaryModel) {
                $fallback = $this->callOpenAi($key, $payloads, $context, $fallbackModel);

                if (($fallback['error'] ?? null) === null) {
                    $result = $fallback;
                }
            }
        }

        $result['scanned_files'] = $scannedFiles;
        $result['scanned_payloads'] = count($payloads);

        return $result;
    }

    /** @param array<int, array<string, string>> $imagePayloads @return array<string, mixed> */
    private function callOpenAi(string $key, array $imagePayloads, string $context, string $model): array
    {
        $content = [[
            'type' => 'text',
            'text' => $this->instructionText($context),
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

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('hunthub.ai_content_disclosure.timeout', 20))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [[
                        'role' => 'system',
                        'content' => 'You classify whether uploaded visual media is likely AI-generated. Return only valid JSON.',
                    ], [
                        'role' => 'user',
                        'content' => $content,
                    ]],
                ]);
        } catch (Throwable $exception) {
            Log::warning('AI content disclosure API request failed.', [
                'context' => $context,
                'model' => $model,
                'error' => $exception->getMessage(),
            ]);

            return $this->baseResult(error: 'request_failed', model: $model);
        }

        if (! $response->successful()) {
            Log::warning('AI content disclosure API returned an error.', [
                'context' => $context,
                'model' => $model,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 1000),
            ]);

            return $this->baseResult(error: 'api_error', model: $model);
        }

        $raw = (string) data_get($response->json(), 'choices.0.message.content', '');
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            Log::warning('AI content disclosure API returned invalid JSON.', [
                'context' => $context,
                'model' => $model,
                'body' => mb_substr($raw, 0, 1000),
            ]);

            return $this->baseResult(error: 'invalid_response', model: $model);
        }

        $confidence = max(0, min(1, (float) ($decoded['confidence'] ?? 0)));
        $threshold = max(0, min(1, (float) config('hunthub.ai_content_disclosure.possible_threshold', 0.72)));
        $possible = (bool) ($decoded['possible_ai'] ?? false);

        return [
            'possible' => $possible && $confidence >= $threshold,
            'confidence' => $confidence,
            'reason' => mb_substr(trim((string) ($decoded['reason'] ?? '')), 0, 500),
            'source' => 'openai',
            'model' => $model,
            'error' => null,
            'raw_possible' => $possible,
        ];
    }

    private function instructionText(string $context): string
    {
        return <<<'TEXT'
You are checking uploaded visual media for transparency labeling on hnt.rocks, a Hunt: Showdown community site.

This is NOT a safety moderation task. Do not classify sexual content, violence, hate, or policy issues. Only estimate whether the uploaded image/video frame appears AI-generated or substantially AI-edited.

Important:
- Do NOT flag normal Hunt: Showdown gameplay screenshots, UI screenshots, match summaries, memes, screenshots with filters, dark game scenes, horror/game art, or compressed images just because they are stylized.
- Do flag when the image/frame looks like generative AI artwork, synthetic illustration, AI-rendered poster/key art, AI-created character scene, or heavily AI-generated visual content.
- If uncertain, return possible_ai=false with low or medium confidence. False public labels are worse than missed weak signals.

Return JSON exactly:
{
  "possible_ai": true,
  "confidence": 0.0,
  "reason": "short German reason"
}
TEXT;
    }

    /** @return array<string, string>|null */
    private function imagePayload(UploadedFile $file): ?array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            return null;
        }

        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
        $compactDataUrl = $this->compactImageDataUrl($path);

        if ($compactDataUrl !== null) {
            return ['data_url' => $compactDataUrl];
        }

        $data = base64_encode((string) file_get_contents($path));

        return ['data_url' => 'data:'.$mimeType.';base64,'.$data];
    }

    private function compactImageDataUrl(string $path): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSide = max(128, (int) config('hunthub.ai_content_disclosure.image_max_side', 768));
        $scale = min(1, $maxSide / max(1, max($width, $height)));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $target) {
            imagedestroy($source);
            return null;
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, max(40, min(90, (int) config('hunthub.ai_content_disclosure.image_jpeg_quality', 72))));
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        if (! is_string($jpeg) || $jpeg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    /** @return array<int, array<string, string>> */
    private function videoFramePayloads(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            return [];
        }

        $ffmpeg = (string) config('hunthub.ai_content_disclosure.ffmpeg_binary', 'ffmpeg');
        $tmpDir = storage_path('app/tmp/ai-disclosure/'.uniqid('video_', true));
        if (! is_dir($tmpDir) && ! mkdir($tmpDir, 0775, true) && ! is_dir($tmpDir)) {
            return [];
        }

        $frames = [];
        $seconds = (array) config('hunthub.ai_content_disclosure.video_sample_seconds', [1, 4, 8]);

        foreach ($seconds as $index => $second) {
            $frame = $tmpDir.'/frame_'.$index.'.jpg';
            $command = sprintf(
                '%s -y -ss %s -i %s -frames:v 1 -vf scale=512:-1 %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg((string) max(0, (int) $second)),
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

    /** @return array<string, mixed> */
    private function baseResult(?string $error = null, ?string $model = null): array
    {
        return [
            'possible' => false,
            'confidence' => 0.0,
            'reason' => '',
            'source' => $error ? 'local' : 'none',
            'model' => $model,
            'error' => $error,
            'raw_possible' => false,
            'scanned_files' => 0,
            'scanned_payloads' => 0,
        ];
    }

    private function apiKey(): ?string
    {
        $key = (string) (config('hunthub.ai_content_disclosure.openai_api_key') ?: '');

        return $key !== '' ? $key : null;
    }
}
