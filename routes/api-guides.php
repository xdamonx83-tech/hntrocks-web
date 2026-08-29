<?php

use App\Http\Controllers\Api\V1\ApiGuideEditorController;
use App\Http\Controllers\Api\V1\ApiGuidesController;
use App\Http\Controllers\Api\V1\ApiMyGuidesController;
use App\Http\Controllers\Guides\GuideBookmarkController;
use App\Http\Controllers\Guides\GuideCommentController;
use App\Http\Controllers\Guides\GuideHelpfulController;
use App\Http\Controllers\Guides\GuideMediaController;
use Illuminate\Support\Facades\Route;

Route::get('/guides', [ApiGuidesController::class, 'index'])->name('guides.index');
Route::get('/guides/media/{media}', [ApiGuidesController::class, 'media'])->name('guides.media.show');

Route::get('/guides/mine', [ApiMyGuidesController::class, 'index'])->name('guides.mine.index');

Route::get('/guides/editor/options', [ApiGuideEditorController::class, 'options'])->name('guides.editor.options');
Route::post('/guides/drafts', [ApiGuideEditorController::class, 'store'])->name('guides.drafts.store');
Route::get('/guides/drafts/media/{media}', [GuideMediaController::class, 'show'])->name('guides.drafts.media.show');
Route::get('/guides/drafts/{guide:slug}', [ApiGuideEditorController::class, 'show'])->name('guides.drafts.show');
Route::patch('/guides/drafts/{guide:slug}', [ApiGuideEditorController::class, 'update'])->name('guides.drafts.update');
Route::post('/guides/drafts/{guide:slug}/media', [ApiGuideEditorController::class, 'media'])->name('guides.drafts.media.store');
Route::post('/guides/drafts/{guide:slug}/submit', [ApiGuideEditorController::class, 'submit'])->name('guides.drafts.submit');
Route::post('/guides/drafts/{guide:slug}/withdraw', [ApiMyGuidesController::class, 'withdraw'])->name('guides.drafts.withdraw');
Route::delete('/guides/drafts/{guide:slug}', [ApiMyGuidesController::class, 'destroy'])->name('guides.drafts.destroy');

Route::post('/guides/{guide:slug}/revision', [ApiGuideEditorController::class, 'beginRevision'])->name('guides.revisions.store');
Route::get('/guides/{guide:slug}', [ApiGuidesController::class, 'show'])->name('guides.show');
Route::get('/guides/{guide:slug}/comments', [ApiGuidesController::class, 'comments'])->name('guides.comments.index');
Route::post('/guides/{guide:slug}/helpful', [GuideHelpfulController::class, 'toggle'])->name('guides.helpful.toggle');
Route::post('/guides/{guide:slug}/bookmark', [GuideBookmarkController::class, 'toggle'])->name('guides.bookmark.toggle');
Route::post('/guides/{guide:slug}/comments', [GuideCommentController::class, 'store'])->name('guides.comments.store');
Route::patch('/guides/comments/{comment}', [GuideCommentController::class, 'update'])->name('guides.comments.update');
Route::delete('/guides/comments/{comment}', [GuideCommentController::class, 'destroy'])->name('guides.comments.destroy');
