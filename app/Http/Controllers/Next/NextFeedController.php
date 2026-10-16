<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Stage 1: exact HNTv3 prototype with original local-only interactions.
 * Laravel authentication still applies via routes/next.php.
 * Live feed queries will be restored as separate incremental components.
 */
final class NextFeedController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('next.feed');
    }
}