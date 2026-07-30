<?php

use App\Http\Controllers\Api\V1\ApiSecurityHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/me/login-history', ApiSecurityHistoryController::class)
    ->name('me.login-history.index');
