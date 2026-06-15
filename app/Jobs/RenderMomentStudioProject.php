<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\MomentStudioProject;
use App\Services\GamificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RenderMomentStudioProject implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout;
    public int $tries = 1;

    public function __construct(public readonly int $projectId)
    {
        $this->onQueue((string) config('hunthub.moment_video_transcoding.queue', 'media'));
        $this->timeout = max(120, (int) config('hunthub.moment_video_transcoding.timeout', 900));
    }

    public function handle(GamificationService $gamification): void
    {
        $project = null;

        try {
        /** @var MomentStudioProject|null $project */
        $project = MomentStudioProject::query()->with('user')->find($this->projectId);

        if (! $project || ! $project->user || in_array($project->status, ['ready', 'published'], true)) {
            return;
        }

        $project->update([
            'status' => 'rendering',
            'started_at' => now(),
            'error_message' => null,
        ]);

        if (! function_exists('exec')) {
            $this->markFailed($project, 'PHP exec() ist nicht verfügbar. ffmpeg kann nicht gestartet werden.');
            return;
        }

        $sourceIds = array_values(array_filter(array_map('intval', (array) $project->source_media_asset_ids)));
        $timeline = is_array($project->timeline) ? $project->timeline : [];
        $clips = array_values((array) ($timeline['clips'] ?? []));
        $textLayers = array_values((array) ($timeline['text_layers'] ?? []));
        $format = $this->normalizeFormat($timeline['format'] ?? null);

        if (count($sourceIds) < 1 || count($clips) !== count($sourceIds)) {
            $this->markFailed($project, 'Studio-Projekt hat keine gültige Clip-Liste.');
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, MediaAsset> $assets */
        $assets = MediaAsset::query()->whereIn('id', $sourceIds)->get()->keyBy('id');
        $orderedAssets = [];
        foreach ($sourceIds as $sourceId) {
            $asset = $assets->get($sourceId);
            if (! $asset || ! $asset->isVideo()) {
                $this->markFailed($project, 'Ein Quellclip fehlt oder ist kein Video.');
                return;
            }
            $orderedAssets[] = $asset;
        }

        $disk = Storage::disk('public');
        $outputPath = 'moments/rendered/'.date('Y/m').'/'.Str::uuid().'.mp4';
        $outputFullPath = $disk->path($outputPath);
        $outputDirectory = dirname($outputFullPath);

        if (! is_dir($outputDirectory) && ! @mkdir($outputDirectory, 0775, true) && ! is_dir($outputDirectory)) {
            $this->markFailed($project, 'Zielordner für gerendertes Moment konnte nicht erstellt werden.');
            return;
        }

        @unlink($outputFullPath);

        $command = $this->buildRenderCommand($orderedAssets, $clips, $textLayers, $format, $outputFullPath);
        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($outputFullPath) || filesize($outputFullPath) <= 0) {
            @unlink($outputFullPath);
            $this->markFailed($project, 'ffmpeg Studio-Render fehlgeschlagen: '.Str::limit(implode("\n", $output), 1200));
            return;
        }

        $profile = $this->profile($format);
        $probe = $this->probeVideo($outputFullPath, $profile);
        $thumbnailPath = $this->generateThumbnail($outputPath, $outputFullPath, $profile);

        $outputAsset = MediaAsset::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $project->user_id,
            'context' => 'moments',
            'disk' => 'public',
            'path' => $outputPath,
            'thumbnail_path' => $thumbnailPath,
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'original_name' => 'moment-studio-'.($project->id).'.mp4',
            'extension' => 'mp4',
            'size_bytes' => filesize($outputFullPath) ?: 0,
            'width' => $probe['width'] ?? (int) $profile['width'],
            'height' => $probe['height'] ?? (int) $profile['height'],
            'duration_seconds' => $probe['duration_seconds'] ?? $project->total_duration_seconds,
            'visibility' => $project->visibility,
            'status' => 'ready',
            'metadata' => [
                'source' => 'moment_studio_render',
                'studio_project_id' => $project->id,
                'source_media_asset_ids' => $sourceIds,
                'timeline' => $project->timeline,
                'format' => $format,
                'aspect_ratio_label' => $format,
                'aspect_ratio' => $this->aspectRatioValue($format),
                'width' => $probe['width'] ?? null,
                'height' => $probe['height'] ?? null,
                'duration_seconds' => $probe['duration_seconds'] ?? null,
                'crf' => $profile['crf'],
                'preset' => $profile['preset'],
                'fps' => $profile['fps'],
                'profile' => 'moment_studio_concat_'.str_replace(':', '_', $format),
                'text_layer_count' => count($textLayers),
                'filter_count' => count(array_filter($clips, fn (array $clip): bool => ($clip['filter'] ?? 'none') !== 'none')),
                'color_adjustment_count' => count(array_filter($clips, fn (array $clip): bool => $this->hasColorAdjustments($clip['colors'] ?? []))),
                'transition_count' => count(array_filter($clips, fn (array $clip): bool => ($clip['transition_out'] ?? 'none') !== 'none')),
            ],
        ]);

        $moment = Moment::create([
            'user_id' => $project->user_id,
            'media_asset_id' => $outputAsset->id,
            'caption' => $project->caption,
            'description' => $project->description,
            'visibility' => $project->visibility,
            'status' => 'published',
            'processing_status' => 'ready',
            'duration_seconds' => $outputAsset->duration_seconds,
            'published_at' => now(),
        ]);

        $outputAsset->update([
            'attachable_type' => $moment->getMorphClass(),
            'attachable_id' => $moment->getKey(),
        ]);

        $project->update([
            'moment_id' => $moment->id,
            'output_media_asset_id' => $outputAsset->id,
            'status' => 'published',
            'finished_at' => now(),
            'expires_at' => now(),
        ]);

        $gamification->award($project->user, 'moment_created', source: $moment, description: 'Moment veröffentlicht');
        $this->deleteSourceAssets($orderedAssets);
        } catch (Throwable $exception) {
            if ($project instanceof MomentStudioProject) {
                $this->markFailed(
                    $project,
                    'Studio render crashed: '.Str::limit($exception->getMessage(), 800)
                );
            }

            Log::error('Moment studio render crashed.', [
                'project_id' => $this->projectId,
                'error' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        $project = MomentStudioProject::query()->find($this->projectId);
        if (! $project instanceof MomentStudioProject) {
            return;
        }

        $this->markFailed(
            $project,
            'Studio render job failed: '.Str::limit($exception->getMessage(), 800)
        );
    }

    /** @param array<int, MediaAsset> $assets @param array<int, array<string, mixed>> $clips @param array<int, array<string, mixed>> $textLayers */
    private function buildRenderCommand(array $assets, array $clips, array $textLayers, string $format, string $outputFullPath): string
    {
        $profile = $this->profile($format);
        $ffmpeg = (string) $profile['ffmpeg_binary'];
        $width = (int) $profile['width'];
        $height = (int) $profile['height'];
        $fps = (int) $profile['fps'];

        $parts = [escapeshellcmd($ffmpeg), '-y'];
        $audioInputIndexes = [];
        $inputIndex = 0;

        foreach ($assets as $assetIndex => $asset) {
            $inputPath = Storage::disk($asset->disk)->path($asset->path);
            $parts[] = '-i';
            $parts[] = escapeshellarg($inputPath);

            if ($this->hasAudioStream($inputPath, $profile)) {
                $audioInputIndexes[$assetIndex] = $inputIndex;
            } else {
                $duration = $this->clipDuration($clips[$assetIndex] ?? []);
                $parts[] = '-f';
                $parts[] = 'lavfi';
                $parts[] = '-t';
                $parts[] = (string) max(0.1, $duration);
                $parts[] = '-i';
                $parts[] = escapeshellarg('anullsrc=channel_layout=stereo:sample_rate=44100');
                $audioInputIndexes[$assetIndex] = $inputIndex + 1;
                $inputIndex++;
            }

            $inputIndex++;
        }

        $filters = [];
        $videoLabels = [];
        $audioLabels = [];
        $clipDurations = [];
        $transitionOuts = [];
        foreach ($assets as $index => $asset) {
            $clip = $clips[$index] ?? [];
            $start = max(0.0, (float) ($clip['start'] ?? 0));
            $end = max($start + 0.1, (float) ($clip['end'] ?? ($start + $this->clipDuration($clip))));
            $duration = max(0.1, $end - $start);
            $audioInput = (int) ($audioInputIndexes[$index] ?? $index);

            $videoColorFilters = $this->clipFilterVideoFilters($clip);
            $videoEffectFilters = $this->clipEffectVideoFilters($clip, $width, $height);
            $videoFadeFilters = $this->clipFadeVideoFilters($clip, $duration);
            $videoColorAdjustmentFilters = $this->clipColorAdjustmentVideoFilters($clip);
            $audioFadeFilters = $this->clipFadeAudioFilters($clip, $duration);

            $frameFilter = $format === '9:16'
                ? sprintf('scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d', $width, $height, $width, $height)
                : sprintf('scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:black', $width, $height, $width, $height);

            $filters[] = sprintf(
                '[%d:v:0]trim=start=%s:end=%s,setpts=PTS-STARTPTS,%s,fps=%d%s%s%s%s,format=yuv420p,setsar=1[v%d]',
                $this->videoInputIndex($index, $audioInputIndexes),
                $this->ffmpegNumber($start),
                $this->ffmpegNumber($end),
                $frameFilter,
                $fps,
                $videoColorFilters,
                $videoEffectFilters,
                $videoColorAdjustmentFilters,
                $videoFadeFilters,
                $index
            );

            if ($audioInput === $this->videoInputIndex($index, $audioInputIndexes)) {
                $filters[] = sprintf('[%d:a:0]atrim=start=%s:end=%s,asetpts=PTS-STARTPTS,aresample=44100,aformat=sample_fmts=fltp:sample_rates=44100:channel_layouts=stereo%s[a%d]', $audioInput, $this->ffmpegNumber($start), $this->ffmpegNumber($end), $audioFadeFilters, $index);
            } else {
                $filters[] = sprintf('[%d:a:0]atrim=start=0:duration=%s,asetpts=PTS-STARTPTS,aresample=44100,aformat=sample_fmts=fltp:sample_rates=44100:channel_layouts=stereo%s[a%d]', $audioInput, $this->ffmpegNumber($duration), $audioFadeFilters, $index);
            }

            $videoLabels[] = 'v'.$index;
            $audioLabels[] = 'a'.$index;
            $clipDurations[] = $duration;
            $transitionOuts[] = $this->normalizeTransition((string) ($clip['transition_out'] ?? 'none'));
        }

        [$renderedVideoLabel, $renderedAudioLabel] = $this->appendTransitionChains($filters, $videoLabels, $audioLabels, $clipDurations, $transitionOuts);

        $finalVideoLabel = $this->appendTextLayerFilters($filters, $textLayers, $width, $height, $renderedVideoLabel);

        return implode(' ', array_merge($parts, [
            '-filter_complex', escapeshellarg(implode(';', $filters)),
            '-map', escapeshellarg('['.$finalVideoLabel.']'),
            '-map', escapeshellarg('['.$renderedAudioLabel.']'),
            '-max_muxing_queue_size', '1024',
            '-c:v', 'libx264',
            '-preset', escapeshellarg((string) $profile['preset']),
            '-crf', (string) $profile['crf'],
            '-profile:v', 'high',
            '-level', '4.1',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', escapeshellarg((string) $profile['audio_bitrate']),
            '-movflags', '+faststart',
            escapeshellarg($outputFullPath),
            '2>&1',
        ]));
    }

    /** @param array<int, string> $filters @param array<int, string> $videoLabels @param array<int, string> $audioLabels @param array<int, float> $clipDurations @param array<int, string> $transitionOuts @return array{0:string,1:string} */
    private function appendTransitionChains(array &$filters, array $videoLabels, array $audioLabels, array $clipDurations, array $transitionOuts): array
    {
        $count = count($videoLabels);
        if ($count <= 0) {
            return ['outv', 'outa'];
        }

        if ($count === 1) {
            $filters[] = sprintf('[%s]null[outv]', $videoLabels[0]);
            $filters[] = sprintf('[%s]anull[outa]', $audioLabels[0]);
            return ['outv', 'outa'];
        }

        $currentVideo = $videoLabels[0];
        $currentAudio = $audioLabels[0];
        $currentDuration = max(0.1, (float) ($clipDurations[0] ?? 0.1));

        for ($i = 1; $i < $count; $i++) {
            $transition = $this->normalizeTransition((string) ($transitionOuts[$i - 1] ?? 'none'));
            $nextDuration = max(0.1, (float) ($clipDurations[$i] ?? 0.1));
            $videoOut = $i === $count - 1 ? 'outv' : 'xv'.$i;
            $audioOut = $i === $count - 1 ? 'outa' : 'xa'.$i;

            if ($transition === 'none') {
                $filters[] = sprintf('[%s][%s]concat=n=2:v=1:a=0[%s]', $currentVideo, $videoLabels[$i], $videoOut);
                $filters[] = sprintf('[%s][%s]concat=n=2:v=0:a=1[%s]', $currentAudio, $audioLabels[$i], $audioOut);
                $currentDuration += $nextDuration;
            } else {
                $transitionDuration = $this->transitionDuration($currentDuration, $nextDuration);
                $offset = max(0.0, $currentDuration - $transitionDuration);
                $filters[] = sprintf(
                    '[%s][%s]xfade=transition=%s:duration=%s:offset=%s[%s]',
                    $currentVideo,
                    $videoLabels[$i],
                    $this->ffmpegTransitionName($transition),
                    $this->ffmpegNumber($transitionDuration),
                    $this->ffmpegNumber($offset),
                    $videoOut
                );
                $filters[] = sprintf(
                    '[%s][%s]acrossfade=d=%s:c1=tri:c2=tri[%s]',
                    $currentAudio,
                    $audioLabels[$i],
                    $this->ffmpegNumber($transitionDuration),
                    $audioOut
                );
                $currentDuration = max(0.1, $currentDuration + $nextDuration - $transitionDuration);
            }

            $currentVideo = $videoOut;
            $currentAudio = $audioOut;
        }

        return ['outv', 'outa'];
    }

    private function normalizeTransition(string $transition): string
    {
        $transition = strtolower(trim($transition));
        return in_array($transition, ['none', 'crossfade', 'fadeblack', 'fadewhite', 'slideleft', 'slideright', 'smoothleft'], true) ? $transition : 'none';
    }

    private function ffmpegTransitionName(string $transition): string
    {
        return match ($this->normalizeTransition($transition)) {
            'fadeblack' => 'fadeblack',
            'fadewhite' => 'fadewhite',
            'slideleft' => 'slideleft',
            'slideright' => 'slideright',
            'smoothleft' => 'smoothleft',
            default => 'fade',
        };
    }

    private function transitionDuration(float $currentDuration, float $nextDuration): float
    {
        return min(0.75, max(0.22, min($currentDuration, $nextDuration) / 3));
    }

    /** @param array<int, string> $filters @param array<int, array<string, mixed>> $textLayers */
    private function appendTextLayerFilters(array &$filters, array $textLayers, int $width, int $height, string $sourceLabel = 'outv'): string
    {
        $inputLabel = $sourceLabel;
        $fontSize = max(26, min(56, (int) round($width * 0.064)));
        $fontFile = $this->drawTextFontFile();

        foreach (array_values($textLayers) as $index => $layer) {
            $text = trim((string) ($layer['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $start = max(0.0, (float) ($layer['start'] ?? 0));
            $end = max($start + 0.1, (float) ($layer['end'] ?? ($start + 4.0)));
            $xPercent = min(95.0, max(5.0, (float) ($layer['x'] ?? 50)));
            $yPercent = min(92.0, max(7.0, (float) ($layer['y'] ?? 78)));
            $color = $this->drawTextColor($layer['color'] ?? null);
            $outputLabel = 'txtv'.$index;

            $options = [];
            if ($fontFile !== null) {
                $options[] = 'fontfile='.$this->escapeDrawTextOption($fontFile);
            }
            $options[] = "text='".$this->escapeDrawTextOption($text)."'";
            $options[] = 'x='.$this->escapeDrawTextExpression(sprintf('min(max(0,(w*%.5F)-(text_w/2)),w-text_w)', $xPercent / 100));
            $options[] = 'y='.$this->escapeDrawTextExpression(sprintf('min(max(0,(h*%.5F)-(text_h/2)),h-text_h)', $yPercent / 100));
            $options[] = 'fontsize='.$fontSize;
            $options[] = 'fontcolor='.$color;
            $options[] = 'borderw=3';
            $options[] = 'bordercolor=black@0.72';
            $options[] = 'shadowx=0';
            $options[] = 'shadowy=3';
            $options[] = 'shadowcolor=black@0.55';
            $options[] = 'enable='.$this->escapeDrawTextExpression(sprintf('between(t,%s,%s)', $this->ffmpegNumber($start), $this->ffmpegNumber($end)));

            $filters[] = sprintf('[%s]drawtext=%s[%s]', $inputLabel, implode(':', $options), $outputLabel);
            $inputLabel = $outputLabel;
        }

        return $inputLabel;
    }

    private function drawTextColor(mixed $value): string
    {
        $color = trim((string) $value);
        if (! preg_match('/\A#?([0-9a-f]{6})\z/i', $color, $matches)) {
            return 'white';
        }

        return '0x'.strtolower($matches[1]);
    }

    private function drawTextFontFile(): ?string
    {
        // Do not probe system font paths here. Some shared-hosting setups restrict PHP
        // with open_basedir, so checking /usr/share/fonts can throw before ffmpeg even
        // starts. Let ffmpeg/libfontconfig pick its default font unless a local, readable
        // project font is deliberately provided later.
        return null;
    }

    private function escapeDrawTextOption(string $value): string
    {
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?: $value;

        return strtr(trim($value), [
            '\\' => '\\\\',
            ':' => '\\:',
            "'" => "\\'",
            ',' => '\\,',
            '[' => '\\[',
            ']' => '\\]',
            '%' => '\\%',
        ]);
    }

    private function escapeDrawTextExpression(string $value): string
    {
        return strtr($value, [
            ':' => '\\:',
            ',' => '\\,',
        ]);
    }

    /** @param array<string, mixed> $clip */
    private function clipFilterVideoFilters(array $clip): string
    {
        $filter = strtolower(trim((string) ($clip['filter'] ?? 'none')));

        $filters = [
            // Keep these render filters intentionally close to the browser preview CSS.
            // CSS preview filters and FFmpeg filters are not 100% identical engines, but
            // these chains use the same core operations/order wherever possible:
            // sepia/color matrix -> hue/saturation -> eq brightness/contrast.
            'orange_teal' => $this->sepiaColorMatrix(0.18).',hue=h=-10:s=1.28,eq=contrast=1.08',
            'bold_blue' => 'hue=h=195:s=1.18,eq=contrast=1.16',
            'golden_hour' => $this->sepiaColorMatrix(0.24).',hue=s=1.30,eq=brightness=0.04:contrast=1.08',
            'vivid_vlogger' => 'hue=s=1.42,eq=brightness=0.04:contrast=1.08',
            'purple_undertone' => 'hue=h=28:s=1.20,eq=contrast=1.06',
            'winter_sunset_35' => $this->sepiaColorMatrix(0.12).',hue=h=-18:s=0.96,eq=contrast=1.14',
            'contrast' => 'hue=s=1.06,eq=contrast=1.28',
            'autumn' => $this->sepiaColorMatrix(0.28).',hue=h=-18:s=1.18,eq=contrast=1.08',
            'winter' => 'hue=h=190:s=0.82,eq=brightness=0.03:contrast=1.10',
            'old_western' => $this->sepiaColorMatrix(0.52).',hue=s=0.82,eq=brightness=-0.01:contrast=1.16',
            'warm_coast' => $this->sepiaColorMatrix(0.12).',hue=s=1.15,eq=brightness=0.04:contrast=1.05',
            'cool_coast' => 'hue=h=190:s=1.06,eq=brightness=0.03:contrast=1.05',
            'warm_landscape' => $this->sepiaColorMatrix(0.17).',hue=s=1.24,eq=contrast=1.08',
            'cool_landscape' => 'hue=h=200:s=1.08,eq=contrast=1.10',
            'golden' => $this->sepiaColorMatrix(0.30).',hue=s=1.35,eq=brightness=0.04:contrast=1.12',
            'dreamscape' => 'hue=h=18:s=1.18,eq=brightness=0.06:contrast=0.96',
        ];

        return isset($filters[$filter]) ? ','.$filters[$filter] : '';
    }

    private function sepiaColorMatrix(float $amount): string
    {
        $amount = max(0.0, min(1.0, $amount));
        $identity = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
        ];
        $sepia = [
            [0.393, 0.769, 0.189],
            [0.349, 0.686, 0.168],
            [0.272, 0.534, 0.131],
        ];

        $values = [];
        foreach ([0, 1, 2] as $row) {
            foreach ([0, 1, 2] as $col) {
                $values[] = $identity[$row][$col] * (1.0 - $amount) + $sepia[$row][$col] * $amount;
            }
        }

        return sprintf(
            'colorchannelmixer=rr=%s:rg=%s:rb=%s:gr=%s:gg=%s:gb=%s:br=%s:bg=%s:bb=%s',
            ...array_map(fn (float $value): string => $this->ffmpegNumber($value), $values)
        );
    }

    /** @param array<string, mixed> $clip */
    private function clipEffectVideoFilters(array $clip, int $width, int $height): string
    {
        $effect = strtolower(trim((string) ($clip['effect'] ?? 'none')));

        $zoomPulse = "scale=w='trunc({$width}*(1+0.026*sin(8*t))/2)*2':h='trunc({$height}*(1+0.026*sin(8*t))/2)*2':eval=frame,crop={$width}:{$height}";
        $fastZoom = "scale=w='trunc({$width}*(1+0.055*sin(10*t))/2)*2':h='trunc({$height}*(1+0.055*sin(10*t))/2)*2':eval=frame,crop={$width}:{$height}";
        $slowZoom = "scale=w='trunc({$width}*(1+0.004*t)/2)*2':h='trunc({$height}*(1+0.004*t)/2)*2':eval=frame,crop={$width}:{$height}";
        $randomZoom = "scale=w='trunc({$width}*(1.035+0.022*sin(0.8*t)+0.014*sin(2.7*t))/2)*2':h='trunc({$height}*(1.035+0.022*sin(0.8*t)+0.014*sin(2.7*t))/2)*2':eval=frame,crop={$width}:{$height}";
        $retroWidth = max(2, (int) round($width / 8));
        $retroHeight = max(2, (int) round($height / 8));

        $effects = [
            // Real movement/time-based effects. Keep each chain label-free so it can be
            // appended safely inside the existing per-clip filter chain.
            'flash' => "eq=brightness='0.08*gt(sin(24*t),0.94)':contrast=1.10",
            'impulse' => $zoomPulse.',eq=contrast=1.06:saturation=1.04',
            'rotate' => "rotate='0.018*sin(3*t)':ow={$width}:oh={$height}:c=black",
            'vhs' => "noise=alls=18:allf=t+u,eq=saturation=0.82:contrast=1.12,hue=h='2*sin(9*t)'",
            'vaporwave' => "hue=h='285+28*sin(1.6*t)':s=1.42,eq=contrast=1.08",
            'chromatic' => 'rgbashift=rh=3:bh=-3,eq=contrast=1.12:saturation=1.16',
            'fast_zoom' => $fastZoom.',eq=contrast=1.05',
            'slow_zoom' => $slowZoom,
            'random_zoom' => $randomZoom.',eq=contrast=1.04',
            'blur' => 'gblur=sigma=3',
            'filmic' => 'eq=contrast=1.14:saturation=0.92,vignette=angle=PI/5,noise=alls=5:allf=t+u',
            'glitch' => 'rgbashift=rh=4:bh=-4,noise=alls=12:allf=t+u,eq=contrast=1.18:saturation=1.22',
            'disco' => "hue=h='120*sin(2*t)':s=1.45",
            'comic' => 'eq=contrast=1.55:saturation=0.88,edgedetect=low=0.08:high=0.22',
            'retro' => "scale={$retroWidth}:{$retroHeight},scale={$width}:{$height}:flags=neighbor,eq=contrast=1.16:saturation=0.90",
            'smoke' => 'boxblur=1.2:1,eq=brightness=0.02:contrast=0.94:saturation=0.86,noise=alls=6:allf=t+u',
            'shine' => "eq=brightness='0.035+0.035*sin(4*t)':contrast=1.06:saturation=1.06",
            'spread' => 'boxblur=2.2:1,noise=alls=8:allf=t+u',
        ];

        return isset($effects[$effect]) ? ','.$effects[$effect] : '';
    }

    private function hasColorAdjustments(mixed $colors): bool
    {
        if (! is_array($colors)) {
            return false;
        }

        foreach (['exposure', 'contrast', 'saturation', 'temperature', 'transparency'] as $key) {
            if (abs((float) ($colors[$key] ?? 0)) > 0.01) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $clip */
    private function clipColorAdjustmentVideoFilters(array $clip): string
    {
        $colors = is_array($clip['colors'] ?? null) ? $clip['colors'] : [];
        $exposure = max(-50.0, min(50.0, (float) ($colors['exposure'] ?? 0)));
        $contrast = max(-50.0, min(50.0, (float) ($colors['contrast'] ?? 0)));
        $saturation = max(-50.0, min(50.0, (float) ($colors['saturation'] ?? 0)));
        $temperature = max(-50.0, min(50.0, (float) ($colors['temperature'] ?? 0)));
        $transparency = max(0.0, min(70.0, (float) ($colors['transparency'] ?? 0)));
        $parts = [];

        if (abs($exposure) > 0.01 || abs($contrast) > 0.01 || abs($saturation) > 0.01 || $transparency > 0.01) {
            $brightness = $exposure / 180.0;
            // Preview transparency is opacity over a black stage. Approximate that in MP4 by reducing brightness.
            $brightness -= $transparency / 180.0;
            $parts[] = sprintf(
                'eq=brightness=%s:contrast=%s:saturation=%s',
                $this->ffmpegNumber(max(-0.65, min(0.65, $brightness))),
                $this->ffmpegNumber(max(0.2, 1 + ($contrast / 100.0))),
                $this->ffmpegNumber(max(0.0, 1 + ($saturation / 100.0)))
            );
        }

        if ($temperature > 0.01) {
            // Match the Studio preview more aggressively. Browser CSS temperature preview
            // stacks sepia/hue/saturation/brightness, while FFmpeg's sepia matrix alone
            // stayed too muted in the final render. Add real RGB channel warmth across
            // shadows/mids/highlights and a stronger saturation/contrast lift.
            $warm = min(1.0, $temperature / 50.0);
            $parts[] = $this->sepiaColorMatrix(min(0.72, 0.18 + ($warm * 0.54)));
            $parts[] = sprintf(
                'colorbalance=rs=%s:gs=%s:bs=%s:rm=%s:gm=%s:bm=%s:rh=%s:gh=%s:bh=%s:pl=0',
                $this->ffmpegNumber(0.10 * $warm),
                $this->ffmpegNumber(0.035 * $warm),
                $this->ffmpegNumber(-0.095 * $warm),
                $this->ffmpegNumber(0.16 * $warm),
                $this->ffmpegNumber(0.045 * $warm),
                $this->ffmpegNumber(-0.15 * $warm),
                $this->ffmpegNumber(0.22 * $warm),
                $this->ffmpegNumber(0.04 * $warm),
                $this->ffmpegNumber(-0.20 * $warm)
            );
            $parts[] = sprintf('hue=h=%s:s=%s', $this->ffmpegNumber(-$temperature * 0.62), $this->ffmpegNumber(1 + ($warm * 0.42)));
            $parts[] = sprintf('eq=brightness=%s:contrast=%s:saturation=%s', $this->ffmpegNumber(min(0.19, 0.05 + ($warm * 0.14))), $this->ffmpegNumber(1 + ($warm * 0.16)), $this->ffmpegNumber(1 + ($warm * 0.22)));
        } elseif ($temperature < -0.01) {
            $cold = abs($temperature);
            $coldRatio = min(1.0, $cold / 50.0);
            $parts[] = sprintf(
                'colorbalance=rs=%s:gs=%s:bs=%s:rm=%s:gm=%s:bm=%s:rh=%s:gh=%s:bh=%s:pl=0',
                $this->ffmpegNumber(-0.10 * $coldRatio),
                $this->ffmpegNumber(-0.035 * $coldRatio),
                $this->ffmpegNumber(0.13 * $coldRatio),
                $this->ffmpegNumber(-0.12 * $coldRatio),
                $this->ffmpegNumber(-0.045 * $coldRatio),
                $this->ffmpegNumber(0.18 * $coldRatio),
                $this->ffmpegNumber(-0.14 * $coldRatio),
                $this->ffmpegNumber(-0.04 * $coldRatio),
                $this->ffmpegNumber(0.22 * $coldRatio)
            );
            $parts[] = sprintf('hue=h=%s:s=%s', $this->ffmpegNumber($cold * 2.6), $this->ffmpegNumber(max(0.35, 1 - ($cold / 210.0))));
            $parts[] = sprintf('eq=brightness=%s:contrast=%s:saturation=%s', $this->ffmpegNumber(max(-0.20, -$cold / 430.0)), $this->ffmpegNumber(1 + ($coldRatio * 0.08)), $this->ffmpegNumber(max(0.42, 1 - ($coldRatio * 0.34))));
        }

        return $parts ? ','.implode(',', $parts) : '';
    }

    /** @param array<string, mixed> $clip */
    private function clipFadeDuration(array $clip, float $duration): float
    {
        if ($duration <= 0.4) {
            return max(0.08, $duration / 4);
        }

        return min(0.75, max(0.2, $duration / 3));
    }

    /** @param array<string, mixed> $clip */
    private function clipFadeVideoFilters(array $clip, float $duration): string
    {
        $fadeDuration = $this->clipFadeDuration($clip, $duration);
        $parts = [];

        if (filter_var($clip['fade_in'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $parts[] = sprintf('fade=t=in:st=0:d=%s', $this->ffmpegNumber($fadeDuration));
        }

        if (filter_var($clip['fade_out'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $start = max(0.0, $duration - $fadeDuration);
            $parts[] = sprintf('fade=t=out:st=%s:d=%s', $this->ffmpegNumber($start), $this->ffmpegNumber($fadeDuration));
        }

        return $parts ? ','.implode(',', $parts) : '';
    }

    /** @param array<string, mixed> $clip */
    private function clipFadeAudioFilters(array $clip, float $duration): string
    {
        $fadeDuration = $this->clipFadeDuration($clip, $duration);
        $parts = [];

        if (filter_var($clip['fade_in'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $parts[] = sprintf('afade=t=in:st=0:d=%s', $this->ffmpegNumber($fadeDuration));
        }

        if (filter_var($clip['fade_out'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $start = max(0.0, $duration - $fadeDuration);
            $parts[] = sprintf('afade=t=out:st=%s:d=%s', $this->ffmpegNumber($start), $this->ffmpegNumber($fadeDuration));
        }

        return $parts ? ','.implode(',', $parts) : '';
    }

    /** @param array<int, int> $audioInputIndexes */
    private function videoInputIndex(int $assetIndex, array $audioInputIndexes): int
    {
        $input = 0;
        for ($i = 0; $i < $assetIndex; $i++) {
            $input++;
            if (($audioInputIndexes[$i] ?? $input - 1) !== $input - 1) {
                $input++;
            }
        }

        return $input;
    }

    /** @param array<string, mixed> $clip */
    private function clipDuration(array $clip): float
    {
        $duration = (float) ($clip['duration'] ?? 0);
        if ($duration > 0) {
            return $duration;
        }

        $start = (float) ($clip['start'] ?? 0);
        $end = (float) ($clip['end'] ?? 0);

        return max(0.1, $end - $start);
    }

    private function ffmpegNumber(float $value): string
    {
        return rtrim(rtrim(number_format(max(0, $value), 3, '.', ''), '0'), '.') ?: '0';
    }

    private function hasAudioStream(string $path, array $profile): bool
    {
        $ffprobe = (string) ($profile['ffprobe_binary'] ?? 'ffprobe');
        $command = implode(' ', [
            escapeshellcmd($ffprobe),
            '-v', 'error',
            '-select_streams', 'a:0',
            '-show_entries', 'stream=index',
            '-of', 'csv=p=0',
            escapeshellarg($path),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        return $exitCode === 0 && trim(implode('', $output)) !== '';
    }

    private function generateThumbnail(string $outputPath, string $outputFullPath, array $profile): ?string
    {
        if (! (bool) ($profile['generate_thumbnail'] ?? true)) {
            return null;
        }

        $disk = Storage::disk('public');
        $directory = trim(dirname($outputPath), '.');
        $basename = pathinfo($outputPath, PATHINFO_FILENAME);
        $thumbnailPath = trim($directory, '/').'/'.$basename.'-thumb.jpg';
        $thumbnailFullPath = $disk->path($thumbnailPath);

        if (! is_dir(dirname($thumbnailFullPath)) && ! @mkdir(dirname($thumbnailFullPath), 0775, true) && ! is_dir(dirname($thumbnailFullPath))) {
            return null;
        }

        @unlink($thumbnailFullPath);

        $command = implode(' ', [
            escapeshellcmd((string) $profile['ffmpeg_binary']),
            '-y',
            '-ss', '0.35',
            '-i', escapeshellarg($outputFullPath),
            '-frames:v', '1',
            '-vf', escapeshellarg("scale='min(".((int) $profile['thumbnail_width']).",iw)':-2"),
            '-q:v', '3',
            escapeshellarg($thumbnailFullPath),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($thumbnailFullPath) || filesize($thumbnailFullPath) <= 0) {
            @unlink($thumbnailFullPath);
            Log::warning('Moment studio thumbnail generation failed.', [
                'project_id' => $this->projectId,
                'output' => Str::limit(implode("\n", $output), 800),
            ]);

            return null;
        }

        return $thumbnailPath;
    }

    private function probeVideo(string $path, array $profile): array
    {
        $command = implode(' ', [
            escapeshellcmd((string) ($profile['ffprobe_binary'] ?? 'ffprobe')),
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:format=duration',
            '-of', 'json',
            escapeshellarg($path),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            return [];
        }

        $data = json_decode(implode("\n", $output), true);
        if (! is_array($data)) {
            return [];
        }

        $stream = $data['streams'][0] ?? [];
        $format = $data['format'] ?? [];

        return [
            'width' => isset($stream['width']) ? (int) $stream['width'] : null,
            'height' => isset($stream['height']) ? (int) $stream['height'] : null,
            'duration_seconds' => isset($format['duration']) ? (int) round((float) $format['duration']) : null,
        ];
    }

    private function deleteSourceAssets(array $assets): void
    {
        foreach ($assets as $asset) {
            $paths = array_values(array_filter([$asset->path, $asset->thumbnail_path]));
            if ($paths !== []) {
                try {
                    Storage::disk($asset->disk)->delete($paths);
                } catch (Throwable $exception) {
                    Log::warning('Moment studio source clip could not be deleted.', [
                        'media_asset_id' => $asset->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $asset->update(['status' => 'deleted']);
            $asset->delete();
        }
    }

    private function markFailed(MomentStudioProject $project, string $message): void
    {
        $project->update([
            'status' => 'failed',
            'error_message' => $message,
            'finished_at' => now(),
        ]);

        Log::warning('Moment studio render failed.', [
            'project_id' => $project->id,
            'error' => $message,
        ]);
    }

    private function normalizeFormat(mixed $value): string
    {
        $format = trim((string) $value);

        return in_array($format, ['9:16', '16:9', '1:1'], true) ? $format : '9:16';
    }

    private function aspectRatioValue(string $format): float
    {
        return match ($this->normalizeFormat($format)) {
            '16:9' => round(16 / 9, 6),
            '1:1' => 1.0,
            default => round(9 / 16, 6),
        };
    }

    private function profile(string $format = '9:16'): array
    {
        $portraitWidth = (int) config('hunthub.moment_video_transcoding.width', 720);
        $portraitHeight = (int) config('hunthub.moment_video_transcoding.height', 1280);
        [$width, $height] = match ($this->normalizeFormat($format)) {
            '16:9' => [$portraitHeight, $portraitWidth],
            '1:1' => [min($portraitWidth, $portraitHeight), min($portraitWidth, $portraitHeight)],
            default => [$portraitWidth, $portraitHeight],
        };

        return [
            'ffmpeg_binary' => (string) config('hunthub.moment_video_transcoding.ffmpeg_binary', 'ffmpeg'),
            'ffprobe_binary' => (string) config('hunthub.moment_video_transcoding.ffprobe_binary', 'ffprobe'),
            'width' => $width,
            'height' => $height,
            'fps' => (int) config('hunthub.moment_video_transcoding.fps', 30),
            'crf' => (int) config('hunthub.moment_video_transcoding.crf', 24),
            'preset' => (string) config('hunthub.moment_video_transcoding.preset', 'medium'),
            'audio_bitrate' => (string) config('hunthub.moment_video_transcoding.audio_bitrate', '128k'),
            'thumbnail_width' => (int) config('hunthub.moment_video_transcoding.thumbnail_width', 720),
            'generate_thumbnail' => (bool) config('hunthub.moment_video_transcoding.generate_thumbnail', true),
        ];
    }
}
