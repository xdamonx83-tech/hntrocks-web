<?php

\Illuminate\Support\Facades\Route::get('/guides', [\App\Http\Controllers\Admin\AdminGuideController::class, 'index'])->name('guides.index');
\Illuminate\Support\Facades\Route::get('/guides/media/{media}', [\App\Http\Controllers\Admin\AdminGuideController::class, 'media'])->name('guides.media');
\Illuminate\Support\Facades\Route::get('/guides/{guide}', [\App\Http\Controllers\Admin\AdminGuideController::class, 'show'])->name('guides.show');
\Illuminate\Support\Facades\Route::post('/guides/{guide}/moderate', [\App\Http\Controllers\Admin\AdminGuideController::class, 'moderate'])->name('guides.moderate');
\Illuminate\Support\Facades\Route::post('/guides/{guide}/archive', [\App\Http\Controllers\Admin\AdminGuideController::class, 'archive'])->name('guides.archive');
\Illuminate\Support\Facades\Route::post('/guides/{guide}/restore', [\App\Http\Controllers\Admin\AdminGuideController::class, 'restore'])->name('guides.restore');
\Illuminate\Support\Facades\Route::post('/guides/{guide}/featured', [\App\Http\Controllers\Admin\AdminGuideController::class, 'featured'])->name('guides.featured');
