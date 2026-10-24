# HNTv3 News editing workspace

The HNTv3 News authoring interface is accessible to signed-in **News administrators only** at:

- `/next/news/editor` (create)
- `/next/news/editor/{articleId}` (edit)

The official News overview and detail page link to these routes for administrators. The routes are already registered and tested in the production `routes/next.php`. This repository does not yet track the server's newer `routes/next.php`; **do not replace it with an older file**.

## Route registration for a fresh HNTv3 installation

Add inside the Laravel web-routes registration:

```php
Route::middleware(['auth', \App\Http\Controllers\Next\NextLocaleMiddleware::class])->group(function (): void {
    Route::get('/next/news/editor', \App\Http\Controllers\Next\NextNewsEditorController::class)
        ->name('next.news.editor');
    Route::get('/next/news/editor/{articleId}', \App\Http\Controllers\Next\NextNewsEditorController::class)
        ->whereNumber('articleId')->name('next.news.editor.edit');
});
```

The controller also checks `$request->user()->isAdmin()` and returns 403 to a non-admin. Do not remove either layer of protection.

## Architecture and functionality

The layout is built from the production HNTv3 Blade shell. A same-origin embedded React workspace preserves the existing, previously implemented TipTap News editor. A carefully scoped stylesheet hides only the nested legacy navigation and skins the editor; a parent bridge adjusts frame height and tracks the draft ID in the HNTv3 URL so reloads reopen the same article.

Retained News authoring features include:
- TipTap rich text with headings, lists, links, alignment, typography and undo/redo
- image and video upload, media resizing and placement
- before/after comparison slider, galleries, media beside text, HNT quotes and dividers
- automatic draft creation, autosave, concurrent-edit conflict handling and revision restore
- separate DE/EN/ES/RU translations and the previous automated translation endpoint
- SEO fields, hero media, tags, featured/comment switches
- preview, schedule, publish, archive

Do **not** directly port the old React implementation into Blade or replace its API, token checks and editor data format. A future editor-native HNTv3 component can supersede the embedded workspace after feature parity is verified.

## Verification

Performed without creating/publishing any articles:
- PHP syntax and route-list checks.
- Signed-in admin view render for create and edit.
- Non-admin controller access returns 403.
- Responsive Chrome visual smoke test at 1440px/390px confirms embedded TipTap frame is sized and styled with no JS runtime errors. It uses a mock editor fixture, not a real credentialed save/publish.
- Route is guest-authenticated; indexed pages exclude editorial workspace via noindex.

No backend schema, News API or public News article route was changed.
