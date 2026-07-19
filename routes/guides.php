<?php

use App\Http\Controllers\Guides\GuideDashboardController;
use Illuminate\Support\Facades\Route;

Route::delete('/guides/{guide:slug}', [GuideDashboardController::class, 'destroy'])
    ->name('guides.destroy');
