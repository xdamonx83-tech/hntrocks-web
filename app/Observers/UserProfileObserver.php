<?php

namespace App\Observers;

use App\Models\UserProfile;
use Illuminate\Validation\ValidationException;

class UserProfileObserver
{
    public function saving(UserProfile $profile): void
    {
        $request = request();

        if (! $request->routeIs('profile.update') || ! $request->has('cover_display_mode')) {
            return;
        }

        $mode = (string) $request->input('cover_display_mode');

        if (! in_array($mode, UserProfile::COVER_DISPLAY_MODES, true)) {
            throw ValidationException::withMessages([
                'cover_display_mode' => __('The selected cover display mode is invalid.'),
            ]);
        }

        $profile->cover_display_mode = $mode;
    }
}
