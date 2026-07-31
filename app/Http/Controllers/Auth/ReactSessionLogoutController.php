<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReactSessionLogoutController extends Controller
{
    public function __invoke(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            $securityLog->record($user, 'logout', $request, [
                'source' => 'react',
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }
}
