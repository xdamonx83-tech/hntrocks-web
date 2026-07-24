<?php

use App\Http\Controllers\Api\V1\ApiGuidesController;
use Illuminate\Support\Facades\Route;

Route::get('/guides', [ApiGuidesController::class, 'index'])->name('guides.index');
Route::get('/guides/media/{media}', [ApiGuidesController::class, 'media'])->name('guides.media.show');
