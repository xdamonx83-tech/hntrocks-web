<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\ApiAccessToken;
use App\Services\SecurityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactSessionAuthController extends Controller
{
    public function __invoke(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->status !== 'active') {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $tokenData = ApiAccessToken::createForUser($user, 'HNT.ROCKS Web');

        $securityLog->record($user, 'react_session_exchange_success', $request, [
            'token_id' => $tokenData['token']->id,
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged in.',
            'access_token' => $tokenData['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $tokenData['token']->expires_at?->toISOString(),
            'user' => new UserResource($user->load('profile')),
        ]);
    }
}
