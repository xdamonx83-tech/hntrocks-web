<?php

use App\Http\Controllers\Api\V1\HuntGameAccountsController;
use Illuminate\Support\Facades\Route;

// Included only inside the existing api.token middleware group.
Route::get('/me/game-accounts', [HuntGameAccountsController::class, 'index'])
    ->name('game-accounts.index');
Route::post('/me/game-accounts/steam/start', [HuntGameAccountsController::class, 'startSteam'])
    ->middleware('throttle:5,1')->name('game-accounts.steam.start');
Route::post('/me/game-accounts/steam/sync', [HuntGameAccountsController::class, 'syncSteam'])
    ->middleware('throttle:2,5')->name('game-accounts.steam.sync');
Route::delete('/me/game-accounts/steam', [HuntGameAccountsController::class, 'disconnectSteam'])
    ->middleware('throttle:6,1')->name('game-accounts.steam.disconnect');

// OAuth is for linking an existing HNT account, never for HNT login.
Route::post('/me/game-accounts/xbox/start', [HuntGameAccountsController::class, 'startXbox'])
    ->middleware('throttle:5,1')->name('game-accounts.xbox.start');
Route::delete('/me/game-accounts/xbox', [HuntGameAccountsController::class, 'disconnectXbox'])
    ->middleware('throttle:6,1')->name('game-accounts.xbox.disconnect');
