<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSecurityEvent;
use Illuminate\Http\Request;

class SecurityLogService
{
    public function record(?User $user, string $event, Request $request, array $meta = []): void
    {
        UserSecurityEvent::create([
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'meta' => $meta ?: null,
        ]);
    }
}
