<?php

use App\Http\Controllers\Api\V1\ApiLiveLobbyController;
use App\Http\Controllers\Api\V1\ApiLiveLobbyFeedbackController;
use App\Http\Controllers\ReadyLobby\ReadyLobbyPageController;
use Illuminate\Support\Facades\Route;

Route::get('/ready-lobbies', [ReadyLobbyPageController::class, 'index'])->name('ready-lobbies.index');
Route::get('/ready-lobbies/create', [ReadyLobbyPageController::class, 'create'])->name('ready-lobbies.create');
Route::get('/ready-lobbies/data', [ApiLiveLobbyController::class, 'index'])->name('ready-lobbies.data');
Route::get('/ready-lobbies/mine/data', [ApiLiveLobbyController::class, 'mine'])->name('ready-lobbies.mine.data');
Route::post('/ready-lobbies', [ApiLiveLobbyController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('ready-lobbies.store');

Route::get('/ready-lobbies/feedback/requests', [ApiLiveLobbyFeedbackController::class, 'index'])
    ->name('ready-lobbies.feedback.requests.index');
Route::post('/ready-lobbies/feedback/{feedbackRequest}/submit', [ApiLiveLobbyFeedbackController::class, 'submit'])
    ->middleware('throttle:20,1')
    ->name('ready-lobbies.feedback.submit');
Route::post('/ready-lobbies/feedback/{feedbackRequest}/dismiss', [ApiLiveLobbyFeedbackController::class, 'dismiss'])
    ->middleware('throttle:20,1')
    ->name('ready-lobbies.feedback.dismiss');

Route::get('/ready-lobbies/{lobby}', [ReadyLobbyPageController::class, 'show'])->name('ready-lobbies.show');
Route::get('/ready-lobbies/{lobby}/data', [ApiLiveLobbyController::class, 'show'])->name('ready-lobbies.show.data');
Route::post('/ready-lobbies/{lobby}/join', [ApiLiveLobbyController::class, 'join'])
    ->middleware('throttle:20,1')
    ->name('ready-lobbies.join');
Route::post('/ready-lobbies/{lobby}/leave', [ApiLiveLobbyController::class, 'leave'])
    ->middleware('throttle:20,1')
    ->name('ready-lobbies.leave');
Route::post('/ready-lobbies/{lobby}/close', [ApiLiveLobbyController::class, 'close'])
    ->middleware('throttle:20,1')
    ->name('ready-lobbies.close');
