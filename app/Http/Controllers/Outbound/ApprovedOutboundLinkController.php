<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\ApprovedOutboundLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovedOutboundLinkController extends Controller
{
    public function show(ApprovedOutboundLink $link): View
    {
        abort_unless($link->is_active, 404);

        return view('themes.socialite.outbound.show', [
            'link' => $link,
        ]);
    }

    public function go(ApprovedOutboundLink $link): RedirectResponse
    {
        abort_unless($link->is_active, 404);

        $link->forceFill([
            'clicks_count' => $link->clicks_count + 1,
            'last_clicked_at' => now(),
        ])->save();

        return redirect()->away($link->target_url);
    }
}
