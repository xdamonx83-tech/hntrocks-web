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

        return view(HntTheme::resolve('outbound.show'), [
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
