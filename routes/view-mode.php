<?php

use App\Http\Controllers\Presentation\ViewModeController;
use Illuminate\Support\Facades\Route;

Route::get('/view/{mode}', ViewModeController::class)
    ->whereIn('mode', ['auto', 'desktop', 'mobile'])
    ->name('view-mode.set');
