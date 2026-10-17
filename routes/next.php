<?php

use App\Http\Controllers\Next\NextFeedController;
use App\Http\Controllers\Next\NextSavedController;
use App\Http\Controllers\Next\NextTopbarController;
use App\Http\Controllers\Next\NextOnlineController;
use App\Http\Controllers\Next\NextLocaleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', NextLocaleMiddleware::class])->group(function (): void {
    Route::redirect('/next', '/next/feed')->name('next.index');
    Route::get('/next/topbar', [NextTopbarController::class, 'show'])->name('next.topbar');
    Route::get('/next/online', NextOnlineController::class)->name('next.online');
    Route::post('/next/language/{locale}', function (Request $request, string $locale) {
        abort_unless(in_array($locale, ['de','en','es','ru'], true), 404);
        $request->session()->put('hnt_next_locale', $locale);
        return response()->json(['ok'=>true, 'locale'=>$locale]);
    })->name('next.language');
    Route::get('/next/feed', NextFeedController::class)->name('next.feed');
    Route::get('/next/saved', NextSavedController::class)->name('next.saved');
    Route::post('/next/feed', [NextFeedController::class, 'store'])->name('next.feed.store');
    Route::get('/next/feed/posts/{post}/card', [NextFeedController::class, 'card'])->name('next.feed.card');
    Route::get('/next/feed/posts/{post}/poll/voters', [NextFeedController::class, 'pollVoters'])->name('next.feed.poll.voters');
});
