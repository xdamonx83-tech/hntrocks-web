<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Http\Controllers\Controller;
use App\Models\Arcade\ArcadeMatchPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ArcadeBroadcastController extends Controller
{
    public function authenticate(Request $request): mixed
    {
        $channel = (string) $request->input('channel_name');

        if (str_starts_with($channel, 'private-arcade.match.')) {
            if (preg_match('/^private-arcade\.match\.(\d+)$/', $channel, $matches) !== 1
                || ! ArcadeMatchPlayer::query()
                ->where('match_id', (int) $matches[1])
                ->where('user_id', $request->user()->id)
                ->exists()) {
                throw new AccessDeniedHttpException;
            }
        }

        return Broadcast::auth($request);
    }
}
