<?php

use App\Http\Controllers\Next\NextFeedController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::redirect('/next', '/next/feed')->name('next.index');
    Route::get('/next/feed', NextFeedController::class)->name('next.feed');
    Route::post('/next/feed', [NextFeedController::class, 'store'])->name('next.feed.store');
    Route::get('/next/feed/posts/{post}/card', [NextFeedController::class, 'card'])->name('next.feed.card');
});
