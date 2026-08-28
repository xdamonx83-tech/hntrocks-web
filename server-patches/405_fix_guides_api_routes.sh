#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="405"
HUB="/home/users/hunthub"
LIVE="$HUB/www/hnt.rocks"
REPO="$LIVE"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
MAIN_ROUTES_BLOB="88ac4174f350ef8cbf28f566f9c8e74d22d28641"
LEGACY_REF="codex/guides-mine-api"
LEGACY_SHA="3025a6d9812e9a8e3bf4eb748484b4b7e7085a4b"
LEGACY_ROUTES_BLOB="b11b44b74925c440adc02ae9de8c1d2bbd6b4f6c"
FEATURE_BRANCH="agent/guides-api-route-restore"
STAMP="$(date +%Y%m%d-%H%M%S)"
WT="$HUB/.worktrees/${PATCH_NO}-guides-api-routes-$STAMP"
BACKUP="$LIVE/storage/app/deploy-backups/guides-api-routes/$STAMP"
LOG="$HUB/${PATCH_NO}_fix_guides_api_routes_${STAMP}.log"
ROUTES="routes/api.php"
DEPLOYED=0
ROUTES_WERE_CACHED=0

mkdir -p "$(dirname "$LOG")"
exec > >(tee -a "$LOG") 2>&1

cleanup() {
  set +e
  git -C "$REPO" worktree remove --force "$WT" >/dev/null 2>&1 || true
  git -C "$REPO" worktree prune >/dev/null 2>&1 || true
  set -e
}

atomic_copy() {
  local src="$1" dst="$2" dir tmp
  dir="$(dirname "$dst")"
  mkdir -p "$dir"
  tmp="$dir/.${PATCH_NO}-$(basename "$dst").tmp.$$"
  cp -p "$src" "$tmp"
  if [[ -e "$dst" ]]; then
    chmod --reference="$dst" "$tmp" 2>/dev/null || true
    chown --reference="$dst" "$tmp" 2>/dev/null || true
  fi
  mv -f "$tmp" "$dst"
}

rollback() {
  set +e
  echo
  echo "ROLLBACK: Guides API Routes wiederherstellen ..."
  if [[ -f "$BACKUP/$ROUTES" ]]; then
    atomic_copy "$BACKUP/$ROUTES" "$LIVE/$ROUTES"
  fi
  (cd "$LIVE" && php artisan route:clear >/dev/null 2>&1) || true
  if [[ "$ROUTES_WERE_CACHED" -eq 1 ]]; then
    (cd "$LIVE" && php artisan route:cache >/dev/null 2>&1) || true
  fi
  echo "ROLLBACK: abgeschlossen."
  set -e
}

die() {
  echo
  echo "FEHLER: $*" >&2
  if [[ "$DEPLOYED" -eq 1 ]]; then
    rollback
    DEPLOYED=0
  fi
  exit 2
}

trap 'rc=$?; if [[ $rc -ne 0 && "$DEPLOYED" -eq 1 ]]; then rollback; fi; cleanup; exit $rc' ERR
trap cleanup EXIT

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 405 - GUIDES API ROUTES WIEDERHERSTELLEN
============================================================
- untersucht ausschliesslich den bestehenden Laravel Guide-API-Vertrag
- Ursache laut Git-Stand: current main hat keinerlei /api/v1/guides-Routen mehr
- stellt die vorhandenen historischen Guide-Controller-Routen aus codex/guides-mine-api wieder her
- KEINE neue Guide-Architektur / KEINE Demo-Daten
- /guides/mine und /guides/editor/options stehen VOR /guides/{slug}
- Auth bleibt wie im bestehenden App-API-Konzept ueber api.token
- prueft vor Deploy, dass die bestehende Guide-Runtime live vorhanden ist
- prueft Guide-DB-Tabellen READ-ONLY; KEINE Migration
- baut routes/api.php isoliert auf current main und pusht NUR Feature-Branch
- Backup + atomarer Deploy + Route-Cache-Invalidierung + Rollback
- Live HTTP: Guides-Routen muessen ohne Token 401 statt Route-404 liefern
- Controller-Smoke fuer Guides/Mine/Editor laeuft mit bestehendem User READ-ONLY
- main wird NICHT veraendert / KEIN MERGE / KEIN MAIN-PUSH
- KEIN FLUTTER
============================================================
HEAD

echo
echo "[1/12] Aktuellen Git-Stand und historischen Guide-Vertrag verifizieren ..."
git -C "$REPO" fetch origin main "$LEGACY_REF" agent/server-patches --prune
MAIN_NOW="$(git -C "$REPO" rev-parse origin/main)"
LEGACY_NOW="$(git -C "$REPO" rev-parse "origin/$LEGACY_REF")"
MAIN_BLOB_NOW="$(git -C "$REPO" rev-parse "origin/main:$ROUTES")"
LEGACY_BLOB_NOW="$(git -C "$REPO" rev-parse "origin/$LEGACY_REF:routes/api-guides.php")"
echo "origin/main:               $MAIN_NOW"
echo "Historischer Guide-Head:   $LEGACY_NOW"
echo "main routes/api.php blob:  $MAIN_BLOB_NOW"
echo "Guide-Routenvertrag blob:  $LEGACY_BLOB_NOW"
[[ "$MAIN_NOW" == "$MAIN_SHA" ]] || die "origin/main ist $MAIN_NOW, erwartet $MAIN_SHA"
[[ "$LEGACY_NOW" == "$LEGACY_SHA" ]] || die "$LEGACY_REF ist $LEGACY_NOW, erwartet $LEGACY_SHA"
[[ "$MAIN_BLOB_NOW" == "$MAIN_ROUTES_BLOB" ]] || die "main routes/api.php wurde unerwartet veraendert"
[[ "$LEGACY_BLOB_NOW" == "$LEGACY_ROUTES_BLOB" ]] || die "historischer Guide-Routenvertrag wurde unerwartet veraendert"
if git -C "$REPO" show "origin/main:$ROUTES" | grep -q "'/guides"; then
  die "origin/main enthaelt inzwischen Guide-Routen; Patch 405 darf nicht blind angewendet werden"
fi
echo "Git-Ursache bestaetigt: main registriert aktuell KEINE Guide-API-Routen."

echo
echo "[2/12] Live routes/api.php und aktuelle Route-Tabelle pruefen ..."
LIVE_ROUTES_BLOB="$(git -C "$REPO" hash-object "$LIVE/$ROUTES")"
echo "Live routes/api.php blob:  $LIVE_ROUTES_BLOB"
[[ "$LIVE_ROUTES_BLOB" == "$MAIN_ROUTES_BLOB" ]] || die "Live routes/api.php weicht von main ab; kein blindes Ueberschreiben"
if [[ -f "$LIVE/bootstrap/cache/routes-v7.php" ]] || compgen -G "$LIVE/bootstrap/cache/routes-*.php" >/dev/null; then
  ROUTES_WERE_CACHED=1
fi
CURRENT_GUIDE_ROUTES="$(cd "$LIVE" && php artisan route:list --path=api/v1/guides --json 2>/dev/null || true)"
CURRENT_COUNT="$(printf '%s' "$CURRENT_GUIDE_ROUTES" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo is_array($j)?count($j):0;' 2>/dev/null || echo 0)"
echo "Aktuell registrierte Guide-Routen: $CURRENT_COUNT"
[[ "$CURRENT_COUNT" -eq 0 ]] || die "Live hat bereits $CURRENT_COUNT Guide-Routen; Zustand erst neu bewerten"

echo
echo "[3/12] Bestehende Guide-Runtime live verifizieren ..."
REQUIRED_FILES=(
  app/Http/Controllers/Api/V1/ApiGuidesController.php
  app/Http/Controllers/Api/V1/ApiGuideEditorController.php
  app/Http/Controllers/Api/V1/ApiMyGuidesController.php
  app/Http/Controllers/Guides/GuideBookmarkController.php
  app/Http/Controllers/Guides/GuideCommentController.php
  app/Http/Controllers/Guides/GuideHelpfulController.php
  app/Http/Controllers/Guides/GuideMediaController.php
  app/Http/Requests/Guides/SaveGuideRevisionRequest.php
  app/Http/Requests/Guides/UploadGuideMediaRequest.php
  app/Http/Resources/Api/GuideOverviewResource.php
  app/Models/Guide.php
  app/Models/GuideBookmark.php
  app/Models/GuideCategory.php
  app/Models/GuideComment.php
  app/Models/GuideHelpfulVote.php
  app/Models/GuideMedia.php
  app/Models/GuideRevision.php
  app/Policies/GuidePolicy.php
  app/Services/Guides/GuideDeletionService.php
  app/Services/Guides/GuideReputationService.php
  app/Services/Guides/GuideWorkflowService.php
  app/Services/Guides/GuideContentService.php
  app/Services/MediaService.php
  app/Services/UserPrivacyService.php
  config/guides.php
)
for rel in "${REQUIRED_FILES[@]}"; do
  [[ -f "$LIVE/$rel" ]] || die "bestehende Guide-Runtime fehlt live: $rel"
  case "$rel" in *.php) php -l "$LIVE/$rel" >/dev/null || die "PHP-Syntaxfehler in $rel" ;; esac
done
echo "Guide-Runtime-Dateien: OK (${#REQUIRED_FILES[@]} geprueft)"

echo
echo "[4/12] Guide-Klassen und Produktionsschema READ-ONLY pruefen ..."
cd "$LIVE"
php <<'PHP' || exit 22
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classes = [
    App\Http\Controllers\Api\V1\ApiGuidesController::class,
    App\Http\Controllers\Api\V1\ApiGuideEditorController::class,
    App\Http\Controllers\Api\V1\ApiMyGuidesController::class,
    App\Models\Guide::class,
    App\Models\GuideRevision::class,
    App\Services\Guides\GuideWorkflowService::class,
];
foreach ($classes as $class) {
    if (! class_exists($class)) {
        fwrite(STDERR, "CLASS_MISSING=$class\n");
        exit(21);
    }
}
$tables = [
    'guides', 'guide_revisions', 'guide_categories', 'guide_media',
    'guide_bookmarks', 'guide_helpful_votes', 'guide_comments',
];
foreach ($tables as $table) {
    if (! Illuminate\Support\Facades\Schema::hasTable($table)) {
        fwrite(STDERR, "TABLE_MISSING=$table\n");
        exit(22);
    }
}
echo "GUIDE_CLASSES=OK\nGUIDE_TABLES=OK\n";
PHP
[[ $? -eq 0 ]] || die "Guide-Klassen/DB-Schema nicht vollstaendig; keine Migration wird automatisch ausgefuehrt"
cd "$HUB"

echo
echo "[5/12] Isolierten Feature-Worktree aus current main erzeugen ..."
mkdir -p "$(dirname "$WT")"
git -C "$REPO" worktree add --detach "$WT" "$MAIN_SHA"
[[ "$(git -C "$WT" rev-parse HEAD)" == "$MAIN_SHA" ]] || die "Worktree steht nicht auf main"

python3 - "$WT/$ROUTES" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text(encoding='utf-8')

imports_anchor = 'use App\\Http\\Controllers\\Api\\V1\\AppRemoteConfigController;\n'
imports = '''use App\\Http\\Controllers\\Api\\V1\\ApiGuideEditorController;\nuse App\\Http\\Controllers\\Api\\V1\\ApiGuidesController;\nuse App\\Http\\Controllers\\Api\\V1\\ApiMyGuidesController;\nuse App\\Http\\Controllers\\Guides\\GuideBookmarkController;\nuse App\\Http\\Controllers\\Guides\\GuideCommentController;\nuse App\\Http\\Controllers\\Guides\\GuideHelpfulController;\nuse App\\Http\\Controllers\\Guides\\GuideMediaController;\n'''
if text.count(imports_anchor) != 1:
    raise SystemExit('405: Import-Anker nicht eindeutig')
text = text.replace(imports_anchor, imports + imports_anchor, 1)

group_anchor = "    Route::middleware('api.token')->group(function (): void {\n"
routes = '''        // Existing community Guides API contract restored from codex/guides-mine-api.\n        // Keep static routes before /guides/{guide:slug} to avoid slug collisions.\n        Route::get('/guides', [ApiGuidesController::class, 'index'])->name('guides.index');\n        Route::get('/guides/media/{media}', [ApiGuidesController::class, 'media'])->name('guides.media.show');\n        Route::get('/guides/mine', [ApiMyGuidesController::class, 'index'])->name('guides.mine.index');\n        Route::get('/guides/editor/options', [ApiGuideEditorController::class, 'options'])->name('guides.editor.options');\n        Route::post('/guides/drafts', [ApiGuideEditorController::class, 'store'])->name('guides.drafts.store');\n        Route::get('/guides/drafts/media/{media}', [GuideMediaController::class, 'show'])->name('guides.drafts.media.show');\n        Route::get('/guides/drafts/{guide:slug}', [ApiGuideEditorController::class, 'show'])->name('guides.drafts.show');\n        Route::patch('/guides/drafts/{guide:slug}', [ApiGuideEditorController::class, 'update'])->name('guides.drafts.update');\n        Route::post('/guides/drafts/{guide:slug}/media', [ApiGuideEditorController::class, 'media'])->name('guides.drafts.media.store');\n        Route::post('/guides/drafts/{guide:slug}/submit', [ApiGuideEditorController::class, 'submit'])->name('guides.drafts.submit');\n        Route::post('/guides/drafts/{guide:slug}/withdraw', [ApiMyGuidesController::class, 'withdraw'])->name('guides.drafts.withdraw');\n        Route::delete('/guides/drafts/{guide:slug}', [ApiMyGuidesController::class, 'destroy'])->name('guides.drafts.destroy');\n        Route::get('/guides/{guide:slug}', [ApiGuidesController::class, 'show'])->name('guides.show');\n        Route::get('/guides/{guide:slug}/comments', [ApiGuidesController::class, 'comments'])->name('guides.comments.index');\n        Route::post('/guides/{guide:slug}/helpful', [GuideHelpfulController::class, 'toggle'])->name('guides.helpful.toggle');\n        Route::post('/guides/{guide:slug}/bookmark', [GuideBookmarkController::class, 'toggle'])->name('guides.bookmark.toggle');\n        Route::post('/guides/{guide:slug}/comments', [GuideCommentController::class, 'store'])->name('guides.comments.store');\n        Route::patch('/guides/comments/{comment}', [GuideCommentController::class, 'update'])->name('guides.comments.update');\n        Route::delete('/guides/comments/{comment}', [GuideCommentController::class, 'destroy'])->name('guides.comments.destroy');\n\n'''
if text.count(group_anchor) != 1:
    raise SystemExit('405: api.token-Gruppenanker nicht eindeutig')
text = text.replace(group_anchor, group_anchor + routes, 1)
path.write_text(text, encoding='utf-8')
PY

php -l "$WT/$ROUTES" >/dev/null || die "gepatchte routes/api.php hat Syntaxfehler"
git -C "$WT" diff --check
mapfile -t CHANGED < <(git -C "$WT" diff --name-only)
[[ ${#CHANGED[@]} -eq 1 && "${CHANGED[0]}" == "$ROUTES" ]] || die "405 darf nur routes/api.php aendern"
echo "Feature-Scope: NUR routes/api.php"

echo
echo "[6/12] Route-Order und Flutter-Vertrag statisch pruefen ..."
ROUTE_TEXT="$WT/$ROUTES"
for marker in \
  "Route::get('/guides'," \
  "Route::get('/guides/mine'," \
  "Route::get('/guides/editor/options'," \
  "Route::post('/guides/drafts'," \
  "Route::get('/guides/drafts/{guide:slug}'," \
  "Route::post('/guides/drafts/{guide:slug}/submit'," \
  "Route::post('/guides/drafts/{guide:slug}/withdraw'," \
  "Route::get('/guides/{guide:slug}'," \
  "Route::get('/guides/{guide:slug}/comments'," \
  "Route::post('/guides/{guide:slug}/helpful'," \
  "Route::post('/guides/{guide:slug}/bookmark'," \
  "Route::patch('/guides/comments/{comment}'," \
  "Route::delete('/guides/comments/{comment}',"; do
  grep -Fq "$marker" "$ROUTE_TEXT" || die "Route-Marker fehlt: $marker"
done
python3 - "$ROUTE_TEXT" <<'PY'
from pathlib import Path
import sys
text=Path(sys.argv[1]).read_text()
positions={
 'mine': text.index("Route::get('/guides/mine',"),
 'editor': text.index("Route::get('/guides/editor/options',"),
 'dynamic': text.index("Route::get('/guides/{guide:slug}',"),
}
if not (positions['mine'] < positions['dynamic'] and positions['editor'] < positions['dynamic']):
    raise SystemExit('405: statische Guide-Routen stehen nicht vor dynamischer Slug-Route')
print('ROUTE_ORDER=OK')
PY

echo
echo "[7/12] Feature-Commit erzeugen und NUR Feature-Branch pushen ..."
cd "$WT"
git add "$ROUTES"
git -c user.name="HNT.ROCKS Agent" -c user.email="agent@hnt.rocks" commit -m "405: Restore existing Guides API routes"
FEATURE_HEAD="$(git rev-parse HEAD)"
git push --force-with-lease origin "HEAD:$FEATURE_BRANCH"
echo "Feature-Branch: $FEATURE_BRANCH"
echo "Feature-Head:   $FEATURE_HEAD"
cd "$HUB"

echo
echo "[8/12] Vollbackup und atomaren Live-Deploy vorbereiten ..."
mkdir -p "$BACKUP/routes"
cp -a "$LIVE/$ROUTES" "$BACKUP/$ROUTES"
echo "$ROUTES" > "$BACKUP/deployed-files.txt"
echo "Backup: $BACKUP"
atomic_copy "$WT/$ROUTES" "$LIVE/$ROUTES"
DEPLOYED=1
php -l "$LIVE/$ROUTES" >/dev/null || die "Live routes/api.php Syntaxfehler nach Copy"

echo
echo "[9/12] Laravel Route Cache korrekt invalidieren/neu aufbauen ..."
cd "$LIVE"
php artisan route:clear >/dev/null || die "route:clear fehlgeschlagen"
if [[ "$ROUTES_WERE_CACHED" -eq 1 ]]; then
  php artisan route:cache >/dev/null || die "route:cache fehlgeschlagen"
  echo "Route-Cache: erneuert"
else
  echo "Route-Cache: war vorher nicht aktiv; bleibt uncached"
fi
cd "$HUB"

echo
echo "[10/12] Live Route-Tabelle und HTTP-Routing pruefen ..."
ROUTE_JSON="$(cd "$LIVE" && php artisan route:list --path=api/v1/guides --json)"
ROUTE_COUNT="$(printf '%s' "$ROUTE_JSON" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo is_array($j)?count($j):0;')"
echo "Live Guide-Routen: $ROUTE_COUNT"
[[ "$ROUTE_COUNT" -eq 19 ]] || die "Erwartet 19 Guide-Routen, gefunden $ROUTE_COUNT"
for route_name in \
  api.v1.guides.index api.v1.guides.mine.index api.v1.guides.editor.options \
  api.v1.guides.drafts.store api.v1.guides.drafts.show api.v1.guides.drafts.update \
  api.v1.guides.drafts.media.store api.v1.guides.drafts.media.show \
  api.v1.guides.drafts.submit api.v1.guides.drafts.withdraw api.v1.guides.drafts.destroy \
  api.v1.guides.show api.v1.guides.comments.index api.v1.guides.helpful.toggle \
  api.v1.guides.bookmark.toggle api.v1.guides.comments.store \
  api.v1.guides.comments.update api.v1.guides.comments.destroy api.v1.guides.media.show; do
  printf '%s' "$ROUTE_JSON" | grep -Fq "\"name\":\"$route_name\"" || die "Live Route fehlt: $route_name"
done

http_code() { curl -sS -o /tmp/${PATCH_NO}-http.$$ -w '%{http_code}' "$1" || true; }
GUIDES_HTTP="$(http_code 'https://hnt.rocks/api/v1/guides')"; rm -f /tmp/${PATCH_NO}-http.$$
MINE_HTTP="$(http_code 'https://hnt.rocks/api/v1/guides/mine')"; rm -f /tmp/${PATCH_NO}-http.$$
EDITOR_HTTP="$(http_code 'https://hnt.rocks/api/v1/guides/editor/options')"; rm -f /tmp/${PATCH_NO}-http.$$
echo "HTTP ohne Token /guides:                $GUIDES_HTTP"
echo "HTTP ohne Token /guides/mine:           $MINE_HTTP"
echo "HTTP ohne Token /guides/editor/options: $EDITOR_HTTP"
[[ "$GUIDES_HTTP" == "401" ]] || die "/guides ohne Token erwartet 401, erhalten $GUIDES_HTTP"
[[ "$MINE_HTTP" == "401" ]] || die "/guides/mine ohne Token erwartet 401, erhalten $MINE_HTTP"
[[ "$EDITOR_HTTP" == "401" ]] || die "/guides/editor/options ohne Token erwartet 401, erhalten $EDITOR_HTTP"

echo
echo "[11/12] Controller + echte Produktionsdaten READ-ONLY smoken ..."
cd "$LIVE"
php <<'PHP' || exit 23
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::query()->orderBy('id')->first();
if (! $user) {
    fwrite(STDERR, "SMOKE_FAIL=no_user\n");
    exit(23);
}
Illuminate\Support\Facades\Auth::setUser($user);

$makeRequest = static function (string $uri) use ($user): Illuminate\Http\Request {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $request->setUserResolver(static fn () => $user);
    app()->instance('request', $request);
    return $request;
};

$guidesRequest = $makeRequest('/api/v1/guides');
$guides = app(App\Http\Controllers\Api\V1\ApiGuidesController::class)->index($guidesRequest);
if ($guides->getStatusCode() !== 200) { exit(24); }
$guidesPayload = $guides->getData(true);
echo 'SMOKE_GUIDES=OK count='.count($guidesPayload['data']['guides'] ?? [])."\n";

$mineRequest = $makeRequest('/api/v1/guides/mine');
$mine = app(App\Http\Controllers\Api\V1\ApiMyGuidesController::class)->index(
    $mineRequest,
    app(App\Services\Guides\GuideReputationService::class),
);
if ($mine->getStatusCode() !== 200) { exit(25); }
$minePayload = $mine->getData(true);
echo 'SMOKE_MINE=OK count='.count($minePayload['data']['guides'] ?? [])."\n";

$editor = app(App\Http\Controllers\Api\V1\ApiGuideEditorController::class)->options();
if ($editor->getStatusCode() !== 200) { exit(26); }
$editorPayload = $editor->getData(true);
echo 'SMOKE_EDITOR_OPTIONS=OK categories='.count($editorPayload['data']['categories'] ?? [])."\n";

$published = App\Models\Guide::query()->published()->first();
if ($published) {
    $detailRequest = $makeRequest('/api/v1/guides/'.$published->slug);
    $detail = app(App\Http\Controllers\Api\V1\ApiGuidesController::class)->show(
        $detailRequest,
        $published,
        app(App\Services\Guides\GuideReputationService::class),
        app(App\Services\UserPrivacyService::class),
    );
    if ($detail->getStatusCode() !== 200) { exit(27); }
    echo 'SMOKE_DETAIL=OK slug='.$published->slug."\n";
} else {
    echo "SMOKE_DETAIL=SKIP_NO_PUBLISHED_GUIDES\n";
}
PHP
[[ $? -eq 0 ]] || die "READ-ONLY Guide-Controller-Smoke fehlgeschlagen"
cd "$HUB"

echo
echo "[12/12] main unveraendert bestaetigen und Abschluss ..."
git -C "$REPO" fetch origin main --prune
MAIN_AFTER="$(git -C "$REPO" rev-parse origin/main)"
[[ "$MAIN_AFTER" == "$MAIN_SHA" ]] || die "origin/main wurde veraendert"
DEPLOYED=0

cat <<EOF
============================================================
PATCH 405 ZUSAMMENFASSUNG
============================================================
Ergebnis:                         ERFOLGREICH / LIVE
Ursache:                          Guide-Controller/Modelle waren vorhanden, aber routes/api.php registrierte KEINE Guide-Routen
Feature-Branch:                   $FEATURE_BRANCH
Feature-Head:                     $FEATURE_HEAD
React/Flutter:                    UNVERAENDERT
Backend-Datei deployed:           routes/api.php (1 Datei)
Neue Architektur:                 NEIN - historischer bestehender Guide-Vertrag wiederhergestellt
Guide-Routen live:                $ROUTE_COUNT
/guides ohne Token:               $GUIDES_HTTP (Route vorhanden, Auth greift)
/guides/mine ohne Token:          $MINE_HTTP (Route vorhanden, Auth greift)
/guides/editor/options ohne Token:$EDITOR_HTTP (Route vorhanden, Auth greift)
Route-Order mine/editor vor slug: JA
Auth-Middleware:                  api.token (wie bestehende App-API)
DB-Aenderung/Migration:           NEIN
Route-Cache:                      $([[ "$ROUTES_WERE_CACHED" -eq 1 ]] && echo 'NEU AUFGEBAUT' || echo 'NICHT AKTIV')
Main-Push/Merge:                  NEIN
Backup:                           $BACKUP
Log-Datei:                        $LOG
============================================================

ERFOLGREICH / LIVE
EOF
