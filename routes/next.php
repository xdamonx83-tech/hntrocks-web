<?php

use App\Http\Controllers\Next\NextFeedController;
use Illuminate\Support\Facades\Route;

// Only the new /next namespace is handled here. Old live routes are untouched.
// 'auth' is required for private posts, but no additional admin allowlist is
// imposed, as requested for this early preview.
Route::middleware('auth')->group(function (): void {
    Route::redirect('/next', '/next/feed')->name('next.index');
    Route::get('/next/feed', NextFeedController::class)->name('next.feed');
});
