<?php

use App\Http\Controllers\Economy\RocksController;
use Illuminate\Support\Facades\Route;

Route::get('/rocks', [RocksController::class, 'index'])->name('rocks.index');
Route::get('/rocks/history', [RocksController::class, 'history'])->name('rocks.history');
Route::post('/rocks/collect', [RocksController::class, 'collectPending'])
    ->middleware('throttle:20,1')
    ->name('rocks.collect');
Route::post('/rocks/dismiss', [RocksController::class, 'dismissPending'])
    ->middleware('throttle:30,1')
    ->name('rocks.dismiss');
