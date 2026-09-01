<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeGameType;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeGameRelease;
use App\Models\Arcade\ArcadeLaunchTicket;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ArcadeLaunchTicketService
{
    public function __construct(private readonly ArcadeDynamicClientPolicy $policy) {}

    public function issue(User $user, ArcadeGame $game, string $client, ?int $matchId = null): array
    {
        if ($game->type !== ArcadeGameType::Web) {
            throw ValidationException::withMessages(['game' => 'Launch tickets are only available for dynamic web Arcade games.']);
        }

        $release = $game->publishedRelease()->first();
        if (! $release || $release->status !== ArcadeGameRelease::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['game' => 'No published dynamic client release is available.']);
        }

        $this->policy->assertTrustedHttpsUrl($release->entrypoint_url, 'entrypoint_url');

        $match = null;
        if ($matchId !== null) {
            $match = ArcadeMatch::query()
                ->whereKey($matchId)
                ->where('game_id', $game->id)
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->id))
                ->firstOrFail();
        }

        $rawToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $expiresAt = now()->addSeconds(max(15, (int) config('arcade.launch_ticket_ttl_seconds', 60)));

        ArcadeLaunchTicket::query()->create([
            'token_hash' => hash('sha256', $rawToken),
            'user_id' => $user->id,
            'game_id' => $game->id,
            'release_id' => $release->id,
            'match_id' => $match?->id,
            'client' => $client,
            'expires_at' => $expiresAt,
        ]);

        $contractVersion = (int) config('arcade.dynamic_contract_version', 2);
        $exchangeUrl = $this->exchangeUrl();
        $launchUrl = $release->entrypoint_url
            .'#hnt_contract='.$contractVersion
            .'&hnt_launch_ticket='.rawurlencode($rawToken)
            .'&hnt_exchange='.rawurlencode($exchangeUrl);

        return [
            'contract_version' => $contractVersion,
            'launch_url' => $launchUrl,
            'exchange_url' => $exchangeUrl,
            'origin' => $this->policy->origin($release->entrypoint_url),
            'expires_at' => $expiresAt->toIso8601String(),
            'release' => [
                'version' => $release->version,
                'integrity_sha256' => $release->integrity_sha256,
            ],
            'match_id' => $match?->id,
        ];
    }

    public function exchange(string $rawToken): array
    {
        return DB::transaction(function () use ($rawToken): array {
            $ticket = ArcadeLaunchTicket::query()
                ->where('token_hash', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->first();

            if (! $ticket || $ticket->consumed_at !== null || $ticket->expires_at->isPast()) {
                throw ValidationException::withMessages(['ticket' => 'The Arcade launch ticket is invalid or expired.']);
            }

            $ticket->forceFill(['consumed_at' => now()])->save();
            $ticket->load(['user', 'game', 'release', 'match']);

            if (! $ticket->user || ! $ticket->game || ! $ticket->release) {
                throw ValidationException::withMessages(['ticket' => 'The Arcade launch ticket is invalid or expired.']);
            }

            $this->policy->assertTrustedHttpsUrl($ticket->release->entrypoint_url, 'entrypoint_url');

            return [
                'contract_version' => (int) config('arcade.dynamic_contract_version', 2),
                'game' => [
                    'key' => $ticket->game->key,
                    'type' => $ticket->game->type->value,
                    'game_version' => $ticket->game->game_version,
                ],
                'release' => [
                    'version' => $ticket->release->version,
                    'entrypoint_url' => $ticket->release->entrypoint_url,
                    'integrity_sha256' => $ticket->release->integrity_sha256,
                    'manifest' => $ticket->release->manifest,
                ],
                'viewer' => [
                    'id' => $ticket->user->id,
                    'username' => $ticket->user->username,
                    'name' => $ticket->user->name,
                ],
                'match' => $ticket->match ? [
                    'id' => $ticket->match->id,
                    'mode' => $ticket->match->mode->value,
                    'status' => $ticket->match->status->value,
                ] : null,
                'client' => $ticket->client,
                'capabilities' => [
                    'bearer_token_exposed' => false,
                    'launch_ticket_single_use' => true,
                    'match_binding' => $ticket->match_id !== null,
                ],
            ];
        });
    }

    private function exchangeUrl(): string
    {
        $url = trim((string) config('arcade.dynamic_exchange_url'));
        $parts = parse_url($url);

        if (
            ! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)
        ) {
            throw new RuntimeException('ARCADE_DYNAMIC_EXCHANGE_URL must be an absolute HTTPS URL without credentials or a non-standard port.');
        }

        return $url;
    }
}
