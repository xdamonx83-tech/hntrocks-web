<?php

namespace App\Services\Equipment;

use App\Models\EquipmentItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class EquipmentAssetImportService
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function importFanKit(string $sourcePath, bool $dryRun = false): array
    {
        $root = realpath($sourcePath);

        if ($root === false || ! is_dir($root) || ! is_readable($root)) {
            throw new RuntimeException('Fan-Kit-Verzeichnis ist nicht lesbar: '.$sourcePath);
        }

        $items = EquipmentItem::query()
            ->where('source_status', 'active')
            ->get(['id', 'slug', 'name', 'external_id', 'original_asset_url', 'local_asset_path']);

        $index = [];
        foreach ($items as $item) {
            foreach ($this->itemKeys($item) as $key) {
                if ($key !== '') {
                    $index[$key][] = $item;
                }
            }
        }

        $counts = [
            'files' => 0,
            'matched' => 0,
            'imported' => 0,
            'unchanged' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
            'invalid' => 0,
            'errors' => 0,
        ];

        $matches = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }

            $counts['files']++;

            $candidate = $this->validateImage($file->getPathname());
            if ($candidate === null) {
                $counts['invalid']++;
                continue;
            }

            $key = $this->normalize(pathinfo($file->getFilename(), PATHINFO_FILENAME));
            $candidates = collect($index[$key] ?? [])->unique('id')->values();

            if ($candidates->isEmpty()) {
                $counts['unmatched']++;
                continue;
            }

            if ($candidates->count() !== 1) {
                $counts['ambiguous']++;
                continue;
            }

            $item = $candidates->first();
            $counts['matched']++;

            $destination = 'arsenal/items/'.$item->slug.'.'.$candidate['extension'];
            $alreadySame = $item->local_asset_path === $destination
                && Storage::disk('public')->exists($destination)
                && hash_file('sha256', $file->getPathname()) === hash('sha256', Storage::disk('public')->get($destination));

            $matches[] = [
                'item' => $item->slug,
                'source' => $file->getPathname(),
                'destination' => $destination,
                'status' => $alreadySame ? 'unchanged' : ($dryRun ? 'would_import' : 'imported'),
            ];

            if ($alreadySame) {
                $counts['unchanged']++;
                continue;
            }

            if ($dryRun) {
                $counts['imported']++;
                continue;
            }

            try {
                $stream = fopen($file->getPathname(), 'rb');
                if (! is_resource($stream)) {
                    throw new RuntimeException('Datei konnte nicht geöffnet werden.');
                }

                try {
                    $written = Storage::disk('public')->writeStream($destination, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $written) {
                    throw new RuntimeException('Datei konnte nicht gespeichert werden.');
                }

                $item->forceFill([
                    'local_asset_path' => $destination,
                    'license_note' => 'Crytek Hunt Fan Kit · official fan-use asset · https://www.huntshowdown.com/news/download-the-hunt-fan-kit',
                ])->save();

                $counts['imported']++;
            } catch (\Throwable) {
                $counts['errors']++;
            }
        }

        return ['counts' => $counts, 'matches' => $matches];
    }

    private function itemKeys(EquipmentItem $item): array
    {
        $keys = [
            $this->normalize($item->slug),
            $this->normalize($item->name),
            $this->normalize($item->external_id),
        ];

        if ($item->original_asset_url) {
            $path = parse_url($item->original_asset_url, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $keys[] = $this->normalize(pathinfo($path, PATHINFO_FILENAME));
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }

    private function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();
    }

    private function validateImage(string $path): ?array
    {
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            return null;
        }

        $info = @getimagesize($path);
        if (! is_array($info) || empty($info['mime'])) {
            return null;
        }

        $extension = match ($info['mime']) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null) {
            return null;
        }

        return ['extension' => $extension, 'mime' => $info['mime']];
    }
}
