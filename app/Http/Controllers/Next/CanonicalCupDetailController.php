<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\React\ReactAppController;
use App\Models\Cup;
use Illuminate\Http\Request;

/**
 * Canonical Cup detail route used by /cups/{cup:slug}.
 *
 * Signed-in hunters receive the interactive HNTv3 Cup detail; anonymous
 * visitors retain the public, indexable SEO document that was already live.
 * No Cup writes or management policies are moved into this controller.
 */
final class CanonicalCupDetailController extends Controller
{
    public function __invoke(Request $request, Cup $cup)
    {
        if ($request->user()) {
            return app(NextCupDetailController::class)($request, $cup);
        }

        return app(ReactAppController::class)();
    }

    public function redirectLegacy(Cup $cup): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('cups.show', $cup, 301);
    }
}
