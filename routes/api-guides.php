<?php

use App\Http\Controllers\Api\V1\ApiGuidesController;
use App\Http\Controllers\Guides\GuideBookmarkController;
use App\Http\Controllers\Guides\GuideCommentController;
use App\Http\Controllers\Guides\GuideHelpfulController;
use Illuminate\Support\Facades\Route;

Route::get('/guides', [ApiGuidesController::class, 'index'])->name('guides.index');
Route::get('/guides/media/{media}', [ApiGuidesController::class, 'media'])->name('guides.media.show');
Route::get('/guides/{guide:slug}', [ApiGuidesController::class, 'show'])->name('guides.show');
Route::get('/guides/{guide:slug}/comments', [ApiGuidesController::class, 'comments'])->name('guides.comments.index');
Route::post('/guides/{guide:slug}/helpful', [GuideHelpfulController::class, 'toggle'])->name('guides.helpful.toggle');
Route::post('/guides/{guide:slug}/bookmark', [GuideBookmarkController::class, 'toggle'])->name('guides.bookmark.toggle');
Route::post('/guides/{guide:slug}/comments', [GuideCommentController::class, 'store'])->name('guides.comments.store');
Route::patch('/guides/comments/{comment}', [GuideCommentController::class, 'update'])->name('guides.comments.update');
Route::delete('/guides/comments/{comment}', [GuideCommentController::class, 'destroy'])->name('guides.comments.destroy');
