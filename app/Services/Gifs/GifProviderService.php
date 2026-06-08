<?php

namespace App\Services\Gifs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GifProviderService
{
    public function trending(int $limit = 24): array
    {
        return $this->giphy('/v1/gifs/trending', [
            'limit' => $this->limit($limit),
        ]);
    }

    public function search(?string $query, int $limit = 24): array
    {
        $query = trim((string) $query);

        if ($query === '') {
            return $this->trending($limit);
        }

        return $this->giphy('/v1/gifs/search', [
            'q' => $query,
            'limit' => $this->limit($limit),
            'lang' => app()->getLocale() === 'de' ? 'de' : 'en',
        ]);
    }

    private function giphy(string $path, array $params): array
    {
        $apiKey = (string) (env('GIPHY_API_KEY') ?: env('HH_GIPHY_API_KEY'));

        if ($apiKey === '') {
            return [
                'provider' => 'giphy',
                'configured' => false,
                'message' => __('ui.feed_gif_provider_missing'),
                'results' => [],
            ];
        }

        $rating = strtolower((string) env('GIPHY_RATING', 'pg-13'));
        if (! in_array($rating, ['g', 'pg', 'pg-13', 'r'], true)) {
            $rating = 'pg-13';
        }

        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://api.giphy.com' . $path, array_merge($params, [
                'api_key' => $apiKey,
                'rating' => $rating,
            ]));

        if (! $response->ok()) {
            return [
                'provider' => 'giphy',
                'configured' => true,
                'message' => __('ui.feed_gif_provider_error'),
                'results' => [],
            ];
        }

        $data = collect($response->json('data', []));

        return [
            'provider' => 'giphy',
            'configured' => true,
            'attribution' => 'Powered by GIPHY',
            'results' => $data->map(fn (array $item): ?array => $this->mapGiphyItem($item))->filter()->values()->all(),
        ];
    }

    private function mapGiphyItem(array $item): ?array
    {
        $images = $item['images'] ?? [];
        $preview = $images['fixed_width_downsampled']['url']
            ?? $images['fixed_height_downsampled']['url']
            ?? $images['downsized_still']['url']
            ?? null;
        $gif = $images['downsized_medium']['url']
            ?? $images['fixed_width']['url']
            ?? $images['original']['url']
            ?? null;

        if (! is_string($gif) || ! $this->isAllowedGifUrl($gif)) {
            return null;
        }

        $preview = is_string($preview) && $this->isAllowedGifUrl($preview) ? $preview : $gif;

        return [
            'provider' => 'giphy',
            'id' => (string) ($item['id'] ?? ''),
            'title' => Str::limit(trim((string) ($item['title'] ?? 'GIF')), 120, ''),
            'gif_url' => $gif,
            'preview_url' => $preview,
            'source_url' => is_string($item['url'] ?? null) ? (string) $item['url'] : null,
        ];
    }

    public function normalizeSelection(array $data): ?array
    {
        $provider = strtolower(trim((string) ($data['gif_provider'] ?? '')));
        $gifUrl = trim((string) ($data['gif_url'] ?? ''));
        $previewUrl = trim((string) ($data['gif_preview_url'] ?? ''));

        if ($provider === '' || $provider === 'none' || $gifUrl === '') {
            return null;
        }

        if (! in_array($provider, ['giphy'], true) || ! $this->isAllowedGifUrl($gifUrl)) {
            return null;
        }

        if ($previewUrl === '' || ! $this->isAllowedGifUrl($previewUrl)) {
            $previewUrl = $gifUrl;
        }

        return [
            'gif_provider' => $provider,
            'gif_id' => Str::limit(trim((string) ($data['gif_id'] ?? '')), 255, ''),
            'gif_url' => $gifUrl,
            'gif_preview_url' => $previewUrl,
            'gif_title' => Str::limit(trim((string) ($data['gif_title'] ?? '')), 180, ''),
            'gif_source_url' => trim((string) ($data['gif_source_url'] ?? '')) ?: null,
        ];
    }

    private function isAllowedGifUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && (str_ends_with($host, 'giphy.com') || str_ends_with($host, 'giphyusercontent.com'));
    }

    private function limit(int $limit): int
    {
        return max(6, min($limit, 30));
    }
}
