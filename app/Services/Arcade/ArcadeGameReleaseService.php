<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeGameType;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeGameRelease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArcadeGameReleaseService
{
    public function __construct(private readonly ArcadeDynamicClientPolicy $policy) {}

    public function createDraft(ArcadeGame $game, array $data, User $actor): ArcadeGameRelease
    {
        $this->assertWebGame($game);
        $this->policy->assertTrustedHttpsUrl($data['entrypoint_url'] ?? null, 'entrypoint_url');
        if (! empty($data['manifest_url'])) {
            $this->policy->assertTrustedHttpsUrl($data['manifest_url'], 'manifest_url');
        }

        $version = (string) $data['version'];
        $entrypointUrl = (string) $data['entrypoint_url'];
        $integrity = strtolower((string) $data['integrity_sha256']);

        return $game->releases()->create([
            'version' => $version,
            'status' => ArcadeGameRelease::STATUS_DRAFT,
            'entrypoint_url' => $entrypointUrl,
            'manifest_url' => $data['manifest_url'] ?? null,
            'integrity_sha256' => $integrity,
            'manifest' => $this->manifest($game, $version, $entrypointUrl, $integrity),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    public function publish(ArcadeGame $game, ArcadeGameRelease $release, User $actor): ArcadeGameRelease
    {
        $this->assertReleaseBelongsToGame($game, $release);
        $this->assertWebGame($game);
        $this->policy->assertTrustedHttpsUrl($release->entrypoint_url, 'entrypoint_url');
        if ($release->manifest_url) $this->policy->assertTrustedHttpsUrl($release->manifest_url, 'manifest_url');

        return DB::transaction(function () use ($game, $release, $actor): ArcadeGameRelease {
            $locked = ArcadeGameRelease::query()->whereKey($release->id)->lockForUpdate()->firstOrFail();

            ArcadeGameRelease::query()
                ->where('game_id', $game->id)
                ->where('status', ArcadeGameRelease::STATUS_PUBLISHED)
                ->where('id', '!=', $locked->id)
                ->update([
                    'status' => ArcadeGameRelease::STATUS_RETIRED,
                    'retired_at' => now(),
                    'updated_by' => $actor->id,
                    'updated_at' => now(),
                ]);

            $locked->forceFill([
                'status' => ArcadeGameRelease::STATUS_PUBLISHED,
                'published_at' => $locked->published_at ?? now(),
                'retired_at' => null,
                'updated_by' => $actor->id,
            ])->save();

            return $locked->fresh();
        });
    }

    public function retire(ArcadeGame $game, ArcadeGameRelease $release, User $actor): ArcadeGameRelease
    {
        $this->assertReleaseBelongsToGame($game, $release);

        $release->forceFill([
            'status' => ArcadeGameRelease::STATUS_RETIRED,
            'retired_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        return $release->fresh();
    }

    public function publicDescriptor(ArcadeGame $game): ?array
    {
        if ($game->type !== ArcadeGameType::Web) return null;

        $release = $game->relationLoaded('publishedRelease')
            ? $game->publishedRelease
            : $game->publishedRelease()->first();

        return [
            'contract_version' => (int) config('arcade.dynamic_contract_version', 1),
            'launch_ticket_required' => true,
            'release_version' => $release?->version,
        ];
    }

    public function manifest(ArcadeGame $game, string $version, string $entrypointUrl, string $integrity): array
    {
        return [
            'contract_version' => (int) config('arcade.dynamic_contract_version', 1),
            'game_key' => $game->key,
            'version' => $version,
            'entrypoint_url' => $entrypointUrl,
            'integrity_sha256' => strtolower($integrity),
        ];
    }

    private function assertWebGame(ArcadeGame $game): void
    {
        if ($game->type !== ArcadeGameType::Web) {
            throw ValidationException::withMessages(['type' => 'Dynamic releases can only be published for web Arcade games.']);
        }
    }

    private function assertReleaseBelongsToGame(ArcadeGame $game, ArcadeGameRelease $release): void
    {
        if ((int) $release->game_id !== (int) $game->id) abort(404);
    }
}
