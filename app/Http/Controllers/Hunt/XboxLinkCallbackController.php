<?php

namespace App\Http\Controllers\Hunt;

use App\Http\Controllers\Controller;
use App\Models\HuntGameConnection;
use App\Models\HuntGameLinkAttempt;
use App\Services\Hunt\XboxAccountVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class XboxLinkCallbackController extends Controller
{
    public function __invoke(Request $request, XboxAccountVerifier $xbox): RedirectResponse
    {
        $state = $request->query('state');
        if (! is_string($state) || preg_match('/^[a-f0-9]{64}$/D', $state) !== 1) {
            return $this->finish('invalid_state');
        }

        $userId = DB::transaction(function () use ($state): ?int {
            $attempt = HuntGameLinkAttempt::query()
                ->where('state_hash', hash('sha256', $state))
                ->lockForUpdate()
                ->first();

            if (! $attempt || $attempt->consumed_at || $attempt->expires_at->isPast()) {
                return null;
            }

            // State is single-use whether Microsoft succeeds or fails.
            $attempt->forceFill(['consumed_at' => now()])->save();

            return (int) $attempt->user_id;
        });

        if (! $userId) {
            return $this->finish('expired');
        }

        // Never trust the callback's login/account or redirection parameters.
        if ($request->has('error')) {
            return $this->finish('cancelled');
        }
        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 8192) {
            return $this->finish('invalid_response');
        }

        try {
            $identity = $xbox->verifyAuthorizationCode($code);

            DB::transaction(function () use ($userId, $identity): void {
                $xuid = $identity['provider_user_id'];
                $other = HuntGameConnection::query()
                    ->where('provider', 'xbox')
                    ->where('provider_user_id', $xuid)
                    ->lockForUpdate()
                    ->first();
                if ($other && (int) $other->user_id !== $userId) {
                    throw new RuntimeException('already_linked');
                }

                $mine = HuntGameConnection::query()
                    ->where('user_id', $userId)
                    ->where('provider', 'xbox')
                    ->lockForUpdate()
                    ->first();

                $data = [
                    'provider_user_id' => $xuid,
                    'provider_name' => $identity['provider_name'],
                    'hunt_stats' => null,
                    'sync_status' => 'unavailable',
                    'sync_error' => 'hunt_stats_api_not_approved',
                    'last_synced_at' => null,
                ];
                if ($mine) {
                    $mine->forceFill($data)->save();
                    return;
                }
                HuntGameConnection::query()->create([
                    ...$data,
                    'user_id' => $userId,
                    'provider' => 'xbox',
                ]);
            });

            return $this->finish('connected');
        } catch (RuntimeException $exception) {
            $reason = in_array($exception->getMessage(), [
                'already_linked', 'not_configured', 'microsoft_authorization_failed',
                'xbox_authorization_failed', 'xbox_identity_unavailable',
            ], true) ? $exception->getMessage() : 'failed';
            return $this->finish($reason);
        } catch (QueryException $exception) {
            // Do not log provider token response or authorization codes.
            report(new RuntimeException('Xbox account conflict'));
            return $this->finish('already_linked');
        } catch (Throwable $exception) {
            report(new RuntimeException('Xbox linking failed ('.get_class($exception).')'));
            return $this->finish('failed');
        }
    }

    private function finish(string $status): RedirectResponse
    {
        return redirect('/profile/edit?xbox_connection='.rawurlencode($status));
    }
}
