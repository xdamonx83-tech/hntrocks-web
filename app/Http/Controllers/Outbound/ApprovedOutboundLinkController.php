<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\ApprovedOutboundLink;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovedOutboundLinkController extends Controller
{
    public function show(ApprovedOutboundLink $link): View
    {
        abort_unless($link->is_active, 404);

        // The HNT preview outbound page still depends on the legacy preview shell
        // and can fail while the new dashboard rollout is only partially complete.
        // Keep approved /out links usable by rendering the stable Socialite warning
        // page for preview sessions until this page receives its dedicated redesign.
        $view = HntTheme::previewActive()
            ? 'themes.socialite.outbound.show'
            : HntTheme::resolve('outbound.show');

        return view($view, [
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
