<?php

namespace App\Http\Controllers\Hunt;

use App\Http\Controllers\Controller;
use App\Models\HuntGameConnection;
use App\Models\HuntGameLinkAttempt;
use App\Services\Hunt\SteamHuntStatsProvider;
use App\Services\Hunt\SteamOpenIdVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class SteamLinkCallbackController extends Controller
{
    public function __invoke(
        Request $request,
        SteamOpenIdVerifier $steam,
        SteamHuntStatsProvider $provider,
    ): RedirectResponse {
        $state = $request->query('state');
        if (! is_string($state) || preg_match('/^[0-9a-f]{64}$/D', $state) !== 1) {
            return $this->finish('invalid_state');
        }

        // Consume once BEFORE checking the external provider, even if it fails.
        // The random state associates this browser redirect with the account
        // that made the authenticated API request. It is not a login.
        $userId = DB::transaction(function () use ($state): ?int {
            $attempt = HuntGameLinkAttempt::query()
                ->where('state_hash', hash('sha256', $state))
                ->lockForUpdate()
                ->first();

            if (! $attempt || $attempt->consumed_at || $attempt->expires_at->isPast()) {
                return null;
            }

            $attempt->forceFill(['consumed_at' => now()])->save();

            return (int) $attempt->user_id;
        });

        if (! $userId) {
            return $this->finish('expired');
        }

        try {
            if (! config('hunt_platform.steam.enabled')) {
                throw new RuntimeException('not_configured');
            }

            $steamId = $steam->verify($request, $state);

            $account = DB::transaction(function () use ($userId, $steamId): HuntGameConnection {
                $other = HuntGameConnection::query()
                    ->where('provider', 'steam')
                    ->where('provider_user_id', $steamId)
                    ->lockForUpdate()
                    ->first();

                if ($other && (int) $other->user_id !== $userId) {
                    throw new RuntimeException('already_linked');
                }

                $mine = HuntGameConnection::query()
                    ->where('user_id', $userId)
                    ->where('provider', 'steam')
                    ->lockForUpdate()
                    ->first();

                if ($mine) {
                    $mine->forceFill([
                        'provider_user_id' => $steamId,
                        'provider_name' => null,
                        'hunt_stats' => null,
                        'sync_status' => 'pending',
                        'sync_error' => null,
                        'last_synced_at' => null,
                    ])->save();

                    return $mine;
                }

                return HuntGameConnection::query()->create([
                    'user_id' => $userId,
                    'provider' => 'steam',
                    'provider_user_id' => $steamId,
                    'sync_status' => 'pending',
                ]);
            });

            $provider->sync($account);

            return $this->finish('connected');
        } catch (RuntimeException $exception) {
            $reason = in_array($exception->getMessage(), [
                'already_linked', 'invalid_identity', 'invalid_response', 'verification_failed',
                'not_configured',
            ], true) ? $exception->getMessage() : 'failed';

            return $this->finish($reason);
        } catch (QueryException $exception) {
            report($exception);
            return $this->finish('already_linked');
        } catch (Throwable $exception) {
            report($exception);
            return $this->finish('failed');
        }
    }

    private function finish(string $status): RedirectResponse
    {
        // Never redirect to an attacker-supplied return URL.
        return redirect('/profile/edit?steam_connection='.rawurlencode($status));
    }
}
