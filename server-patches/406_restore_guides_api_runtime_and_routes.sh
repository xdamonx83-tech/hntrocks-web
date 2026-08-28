#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="406"
HUB="/home/users/hunthub"
LIVE="$HUB/www/hnt.rocks"
REPO="$LIVE"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
LEGACY_SHA="3025a6d9812e9a8e3bf4eb748484b4b7e7085a4b"
LEGACY_REF="codex/guides-mine-api"
FEATURE_BRANCH="agent/guides-api-runtime-restore"
STAMP="$(date +%Y%m%d-%H%M%S)"
WT="$HUB/.worktrees/${PATCH_NO}-guides-api-runtime-$STAMP"
BACKUP="$LIVE/storage/app/deploy-backups/guides-api-runtime/$STAMP"
LOG="$HUB/${PATCH_NO}_restore_guides_api_runtime_${STAMP}.log"
DEPLOYED=0
ROUTES_WERE_CACHED=0
CONFIG_WAS_CACHED=0
FEATURE_HEAD=""

RESTORE_FILES=(
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
  app/Models/Concerns/HidesBlockedUsers.php
  app/Models/Guide.php
  app/Models/GuideBookmark.php
  app/Models/GuideCategory.php
  app/Models/GuideComment.php
  app/Models/GuideHelpfulVote.php
  app/Models/GuideMedia.php
  app/Models/GuideModerationEvent.php
  app/Models/GuideReputationEntry.php
  app/Models/GuideRevision.php
  app/Policies/GuidePolicy.php
  app/Services/Guides/GuideContentService.php
  app/Services/Guides/GuideDeletionService.php
  app/Services/Guides/GuideReputationService.php
  app/Services/Guides/GuideWorkflowService.php
  app/Services/UserBlockService.php
  app/Services/UserPrivacyService.php
  config/guides.php
  database/migrations/2026_07_18_000001_create_community_guides.php
  database/migrations/2026_07_18_000002_add_guides_notification_setting.php
  database/migrations/2026_07_19_235900_add_show_in_profile_to_guides_table.php
  routes/api-guides.php
  resources/lang/de/guides.php
  resources/lang/en/guides.php
  resources/lang/de/guides_mine.php
  resources/lang/en/guides_mine.php
)

INTEGRATION_FILES=(
  routes/api.php
  app/Providers/AppServiceProvider.php
  app/Models/UserNotificationSetting.php
  app/Http/Controllers/Reports/ReportController.php
)

EXPECTED_FILES=("${RESTORE_FILES[@]}" "${INTEGRATION_FILES[@]}")

mkdir -p "$(dirname "$LOG")"
exec > >(tee -a "$LOG") 2>&1

cleanup() {
  set +e
  git -C "$REPO" worktree remove --force "$WT" >/dev/null 2>&1 || true
  git -C "$REPO" worktree prune >/dev/null 2>&1 || true
  set -e
}

APP_UID="$(stat -c %u "$LIVE/routes/api.php")"
APP_GID="$(stat -c %g "$LIVE/routes/api.php")"

atomic_copy() {
  local src="$1" dst="$2" dir tmp
  dir="$(dirname "$dst")"
  mkdir -p "$dir"
  chown "$APP_UID:$APP_GID" "$dir" 2>/dev/null || true
  tmp="$dir/.${PATCH_NO}-$(basename "$dst").tmp.$$"
  cp "$src" "$tmp"
  chown "$APP_UID:$APP_GID" "$tmp"
  if [[ -e "$dst" ]]; then
    chmod --reference="$dst" "$tmp" 2>/dev/null || chmod 0644 "$tmp"
  else
    chmod 0644 "$tmp"
  fi
  mv -f "$tmp" "$dst"
}

restore_caches() {
  set +e
  (cd "$LIVE" && php artisan route:clear >/dev/null 2>&1) || true
  (cd "$LIVE" && php artisan config:clear >/dev/null 2>&1) || true
  if [[ "$CONFIG_WAS_CACHED" -eq 1 ]]; then
    (cd "$LIVE" && php artisan config:cache >/dev/null 2>&1) || true
  fi
  if [[ "$ROUTES_WERE_CACHED" -eq 1 ]]; then
    (cd "$LIVE" && php artisan route:cache >/dev/null 2>&1) || true
  fi
  set -e
}

rollback() {
  set +e
  echo
  echo "ROLLBACK: Guide-Runtime und Integrationen wiederherstellen ..."
  if [[ -f "$BACKUP/.existing-files" ]]; then
    while IFS= read -r rel; do
      [[ -n "$rel" ]] || continue
      [[ -f "$BACKUP/files/$rel" ]] && atomic_copy "$BACKUP/files/$rel" "$LIVE/$rel"
    done < "$BACKUP/.existing-files"
  fi
  if [[ -f "$BACKUP/.new-files" ]]; then
    while IFS= read -r rel; do
      [[ -n "$rel" ]] || continue
      rm -f "$LIVE/$rel"
    done < "$BACKUP/.new-files"
  fi
  restore_caches
  echo "ROLLBACK: abgeschlossen. Backup bleibt unter $BACKUP"
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
HNT.ROCKS PATCH 406 - GUIDES API RUNTIME + ROUTES RESTORE
============================================================
- basiert auf der READ-ONLY-Inventur 405b
- DB-Schema ist bereits vollstaendig vorhanden: 9 Guide-Tabellen, 2 Guides, 8 Kategorien, alle 3 Migrationen registriert
- Ursache: Guide-Runtime + API-Routen wurden aus dem aktuellen Code entfernt, DB blieb bestehen
- stellt den letzten vorhandenen vollstaendigen Flutter-Vertrag aus codex/guides-mine-api wieder her
- echte DB-Daten, KEINE Demo-Guides
- Flutter-Vertrag wird nicht erfunden: createDraft ist POST /guides/drafts; kein GET /guides/drafts
- /guides/mine und /guides/editor/options stehen vor /guides/{slug}
- alle Guide-Endpunkte bleiben wie historisch hinter api.token
- GuidePolicy, Guide-Benachrichtigungseinstellung und guide_comment-Reporting werden wieder angebunden
- fehlende alte Web-Guide-Routennamen werden NICHT wieder eingefuehrt; Notification-Deep-Links werden als stabile /guides/... URLs erzeugt
- Migration-Dateien werden fuer Repository-Konsistenz wiederhergestellt, aber KEINE Migration wird ausgefuehrt
- isolierter Worktree -> Syntax/Route-Checks -> authentifizierter Kernel-Smoke in DB-Transaktion/Rollback
- Feature-Branch -> Vollbackup -> atomarer Live-Deploy -> Cache-Rebuild -> externe + authentifizierte Live-Smokes
- main wird NICHT veraendert / KEIN MERGE / KEIN MAIN-PUSH
- KEINE Flutter-Aenderung
============================================================
HEAD

echo
echo "[1/14] Git-Basis, Legacy-Quelle und Live-Basis exakt verifizieren ..."
git -C "$REPO" fetch origin main "$LEGACY_REF" agent/server-patches --prune
MAIN_NOW="$(git -C "$REPO" rev-parse origin/main)"
LEGACY_NOW="$(git -C "$REPO" rev-parse "origin/$LEGACY_REF")"
echo "origin/main:      $MAIN_NOW"
echo "Guide-Legacy:     $LEGACY_NOW"
echo "Feature-Ziel:     $FEATURE_BRANCH"
[[ "$MAIN_NOW" == "$MAIN_SHA" ]] || die "origin/main ist $MAIN_NOW, erwartet $MAIN_SHA"
[[ "$LEGACY_NOW" == "$LEGACY_SHA" ]] || die "$LEGACY_REF ist $LEGACY_NOW, erwartet $LEGACY_SHA"

for rel in "${INTEGRATION_FILES[@]}"; do
  main_blob="$(git -C "$REPO" rev-parse "origin/main:$rel")"
  live_blob="$(git -C "$REPO" hash-object "$LIVE/$rel")"
  echo "BASIS $rel: $live_blob"
  [[ "$live_blob" == "$main_blob" ]] || die "Live-Datei $rel weicht von current main ab; kein blindes Patchen"
done

for rel in "${RESTORE_FILES[@]}"; do
  git -C "$REPO" cat-file -e "$LEGACY_SHA:$rel" 2>/dev/null || die "Legacy-Datei fehlt: $rel"
  if [[ -f "$LIVE/$rel" ]]; then
    legacy_blob="$(git -C "$REPO" rev-parse "$LEGACY_SHA:$rel")"
    live_blob="$(git -C "$REPO" hash-object "$LIVE/$rel")"
    [[ "$live_blob" == "$legacy_blob" ]] || die "Live hat unerwartete abweichende Altdatei: $rel"
  fi
done
echo "Git-/Live-Basis: OK"

echo
echo "[2/14] Produktionsschema und bestehende Daten READ-ONLY bestaetigen ..."
cd "$LIVE"
php <<'PHP' || exit 22
<?php
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tables = [
    'guide_categories', 'guides', 'guide_revisions', 'guide_media',
    'guide_moderation_events', 'guide_comments', 'guide_helpful_votes',
    'guide_bookmarks', 'guide_reputation_entries',
];
foreach ($tables as $table) {
    if (! Illuminate\Support\Facades\Schema::hasTable($table)) {
        fwrite(STDERR, "TABLE_MISSING=$table\n");
        exit(21);
    }
}
foreach (['show_in_profile','current_published_revision_id','working_revision_id','helpful_count','bookmarks_count','comments_count'] as $column) {
    if (! Illuminate\Support\Facades\Schema::hasColumn('guides', $column)) {
        fwrite(STDERR, "COLUMN_MISSING=guides.$column\n");
        exit(22);
    }
}
if (! Illuminate\Support\Facades\Schema::hasColumn('user_notification_settings', 'guides')) {
    fwrite(STDERR, "COLUMN_MISSING=user_notification_settings.guides\n");
    exit(23);
}
$migrations = [
    '2026_07_18_000001_create_community_guides',
    '2026_07_18_000002_add_guides_notification_setting',
    '2026_07_19_235900_add_show_in_profile_to_guides_table',
];
foreach ($migrations as $migration) {
    if (! Illuminate\Support\Facades\DB::table('migrations')->where('migration', $migration)->exists()) {
        fwrite(STDERR, "MIGRATION_ENTRY_MISSING=$migration\n");
        exit(24);
    }
}
echo 'GUIDE_ROWS='.Illuminate\Support\Facades\DB::table('guides')->count()."\n";
echo 'GUIDE_CATEGORIES='.Illuminate\Support\Facades\DB::table('guide_categories')->count()."\n";
echo "GUIDE_SCHEMA=OK\n";
PHP
[[ $? -eq 0 ]] || die "Produktionsschema passt nicht zum historischen Guide-System"
cd "$HUB"

echo
echo "[3/14] Isolierten Worktree aus current main anlegen und 36 historische Guide-Dateien wiederherstellen ..."
mkdir -p "$(dirname "$WT")"
git -C "$REPO" worktree add --detach "$WT" "$MAIN_SHA"
for rel in "${RESTORE_FILES[@]}"; do
  mkdir -p "$WT/$(dirname "$rel")"
  git -C "$REPO" show "$LEGACY_SHA:$rel" > "$WT/$rel"
done
echo "Historische Dateien wiederhergestellt: ${#RESTORE_FILES[@]}"

echo
echo "[4/14] Current-main Integrationen minimal/additiv wieder anbinden ..."
python3 - "$WT" <<'PY'
from pathlib import Path
import sys

root = Path(sys.argv[1])

def replace_exact(path: Path, old: str, new: str, count: int = 1):
    text = path.read_text(encoding='utf-8')
    found = text.count(old)
    if found != count:
        raise SystemExit(f"406: {path}: erwartet {count} Treffer, gefunden {found}: {old[:80]!r}")
    path.write_text(text.replace(old, new), encoding='utf-8')

# routes/api.php: historischen api-guides.php Vertrag innerhalb des bestehenden api.token Blocks laden.
p = root / 'routes/api.php'
replace_exact(
    p,
    "    Route::middleware('api.token')->group(function (): void {\n",
    "    Route::middleware('api.token')->group(function (): void {\n        require __DIR__.'/api-guides.php';\n\n",
)

# GuidePolicy + UserBlockService in den aktuellen, bewusst kleinen AppServiceProvider integrieren.
p = root / 'app/Providers/AppServiceProvider.php'
replace_exact(p, "use App\\Models\\UserProfile;\n", "use App\\Models\\Guide;\nuse App\\Models\\UserProfile;\n")
replace_exact(p, "use App\\Observers\\UserProfileObserver;\n", "use App\\Observers\\UserProfileObserver;\nuse App\\Policies\\GuidePolicy;\nuse App\\Services\\UserBlockService;\nuse Illuminate\\Support\\Facades\\Gate;\n")
replace_exact(
    p,
    "    public function register(): void\n    {\n        // Register Hunthub services module by module.\n    }",
    "    public function register(): void\n    {\n        $this->app->scoped(UserBlockService::class);\n    }",
)
replace_exact(
    p,
    "    public function boot(): void\n    {\n        UserProfile::observe(UserProfileObserver::class);",
    "    public function boot(): void\n    {\n        Gate::policy(Guide::class, GuidePolicy::class);\n        UserProfile::observe(UserProfileObserver::class);",
)

# Bereits vorhandene DB-Spalte guides wieder im Notification-Setting-Modell aktivieren.
p = root / 'app/Models/UserNotificationSetting.php'
replace_exact(p, "        'cups',\n        'referrals',", "        'cups',\n        'guides',\n        'referrals',", count=2)
replace_exact(
    p,
    "        if (str_starts_with($type, 'cup_')) {\n            return 'cups';\n        }\n\n        if (str_starts_with($type, 'referral_')) {",
    "        if (str_starts_with($type, 'cup_')) {\n            return 'cups';\n        }\n\n        if (str_starts_with($type, 'guide_')) {\n            return 'guides';\n        }\n\n        if (str_starts_with($type, 'referral_')) {",
)

# Flutter meldet Guide-Kommentare ueber den bestehenden /reports Endpunkt.
p = root / 'app/Http/Controllers/Reports/ReportController.php'
replace_exact(p, "use App\\Models\\FeedPost;\nuse App\\Models\\LfgPost;", "use App\\Models\\FeedPost;\nuse App\\Models\\Guide;\nuse App\\Models\\GuideComment;\nuse App\\Models\\LfgPost;")
replace_exact(
    p,
    "            $reportable instanceof FeedComment => (int) $reportable->user_id === (int) $user->id,\n            $reportable instanceof LfgPost =>",
    "            $reportable instanceof FeedComment => (int) $reportable->user_id === (int) $user->id,\n            $reportable instanceof Guide => (int) $reportable->author_id === (int) $user->id,\n            $reportable instanceof GuideComment => (int) $reportable->user_id === (int) $user->id,\n            $reportable instanceof LfgPost =>",
)
replace_exact(
    p,
    "            $reportable instanceof FeedComment => 'feed_comment',\n            $reportable instanceof LfgPost =>",
    "            $reportable instanceof FeedComment => 'feed_comment',\n            $reportable instanceof Guide => 'guide',\n            $reportable instanceof GuideComment => 'guide_comment',\n            $reportable instanceof LfgPost =>",
)
replace_exact(
    p,
    "            'feed_comment' => FeedComment::class,\n            'team' => Team::class,",
    "            'feed_comment' => FeedComment::class,\n            'guide' => Guide::class,\n            'guide_comment' => GuideComment::class,\n            'team' => Team::class,",
)

# Die alte Web-Guide-UI ist nicht mehr vorhanden. Mutation-Notifications duerfen deshalb
# nicht ueber nicht existente route('guides.*') Namen crashen. Die Deep-Links bleiben /guides/...
compat_files = [
    root / 'app/Services/Guides/GuideWorkflowService.php',
    root / 'app/Http/Controllers/Guides/GuideCommentController.php',
    root / 'app/Http/Controllers/Guides/GuideHelpfulController.php',
    root / 'app/Http/Controllers/Guides/GuideMediaController.php',
]
replacements = {
    "route('guides.mine')": "url('/guides/mine')",
    "route('guides.show', $guide)": "url('/guides/'.$guide->slug)",
    "route('guides.show', $locked)": "url('/guides/'.$locked->slug)",
    "route('guides.edit', $guide)": "url('/guides/'.$guide->slug.'/edit')",
    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
}
for path in compat_files:
    text = path.read_text(encoding='utf-8')
    for old, new in replacements.items():
        text = text.replace(old, new)
    if "route('guides." in text:
        raise SystemExit(f"406: fehlende Web-Guide-Route bleibt in {path}")
    path.write_text(text, encoding='utf-8')
PY

echo "Guide-Integrationen: OK"

echo
echo "[5/14] Exakten 40-Datei-Scope, PHP-Syntax und Route-Order pruefen ..."
git -C "$WT" diff --check
mapfile -t ACTUAL < <(git -C "$WT" diff --name-only | sort)
printf '%s\n' "${EXPECTED_FILES[@]}" | sort > "$WT/.406-expected"
printf '%s\n' "${ACTUAL[@]}" > "$WT/.406-actual"
if ! diff -u "$WT/.406-expected" "$WT/.406-actual"; then
  rm -f "$WT/.406-expected" "$WT/.406-actual"
  die "Feature-Scope ist nicht exakt der erwartete 40-Datei-Restore"
fi
rm -f "$WT/.406-expected" "$WT/.406-actual"
[[ ${#ACTUAL[@]} -eq 40 ]] || die "Erwartet 40 Dateien, gefunden ${#ACTUAL[@]}"

for rel in "${ACTUAL[@]}"; do
  case "$rel" in *.php) php -l "$WT/$rel" >/dev/null || die "PHP-Syntaxfehler: $rel" ;; esac
done

python3 - "$WT/routes/api-guides.php" <<'PY'
from pathlib import Path
import sys
s = Path(sys.argv[1]).read_text(encoding='utf-8')
need = [
    "Route::get('/guides/mine'",
    "Route::get('/guides/editor/options'",
    "Route::post('/guides/drafts'",
    "Route::get('/guides/{guide:slug}'",
]
for marker in need:
    if marker not in s:
        raise SystemExit(f"406: Route fehlt: {marker}")
if not s.index("Route::get('/guides/mine'") < s.index("Route::get('/guides/{guide:slug}'"):
    raise SystemExit('406: /guides/mine steht nicht vor dynamischer Slug-Route')
if not s.index("Route::get('/guides/editor/options'") < s.index("Route::get('/guides/{guide:slug}'"):
    raise SystemExit('406: /guides/editor/options steht nicht vor dynamischer Slug-Route')
if "Route::get('/guides/drafts'" in s:
    raise SystemExit('406: unerwuenschten GET /guides/drafts erfunden')
print('ROUTE_ORDER=OK')
PY

echo "Datei-Scope/Syntax/Order: OK (40 Dateien)"

echo
echo "[6/14] Worktree mit bestehender Laravel-Laufzeit booten und 19 Guide-Routen verifizieren ..."
ln -s "$LIVE/vendor" "$WT/vendor"
ln -s "$LIVE/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs"
(cd "$WT" && php artisan route:clear >/dev/null && php artisan config:clear >/dev/null)
ROUTE_JSON="$WT/.406-routes.json"
(cd "$WT" && php artisan route:list --path=api/v1/guides --json) > "$ROUTE_JSON"
php - "$ROUTE_JSON" <<'PHP'
<?php
$routes = json_decode(file_get_contents($argv[1]), true);
if (! is_array($routes) || count($routes) !== 19) {
    fwrite(STDERR, 'ROUTE_COUNT='.(is_array($routes) ? count($routes) : -1)." expected=19\n");
    exit(1);
}
$checks = [
    ['GET|HEAD', 'api/v1/guides'],
    ['GET|HEAD', 'api/v1/guides/mine'],
    ['GET|HEAD', 'api/v1/guides/editor/options'],
    ['POST', 'api/v1/guides/drafts'],
    ['GET|HEAD', 'api/v1/guides/drafts/{guide}'],
    ['PATCH', 'api/v1/guides/drafts/{guide}'],
    ['POST', 'api/v1/guides/drafts/{guide}/media'],
    ['POST', 'api/v1/guides/drafts/{guide}/submit'],
    ['POST', 'api/v1/guides/drafts/{guide}/withdraw'],
    ['DELETE', 'api/v1/guides/drafts/{guide}'],
    ['GET|HEAD', 'api/v1/guides/{guide}'],
    ['GET|HEAD', 'api/v1/guides/{guide}/comments'],
    ['POST', 'api/v1/guides/{guide}/helpful'],
    ['POST', 'api/v1/guides/{guide}/bookmark'],
    ['POST', 'api/v1/guides/{guide}/comments'],
    ['PATCH', 'api/v1/guides/comments/{comment}'],
    ['DELETE', 'api/v1/guides/comments/{comment}'],
];
foreach ($checks as [$method, $uri]) {
    $found = false;
    foreach ($routes as $route) {
        if (($route['method'] ?? '') === $method && ($route['uri'] ?? '') === $uri) {
            $found = true;
            break;
        }
    }
    if (! $found) {
        fwrite(STDERR, "ROUTE_MISSING=$method $uri\n");
        exit(2);
    }
}
echo "GUIDE_ROUTE_COUNT=19\nGUIDE_ROUTE_CONTRACT=OK\n";
PHP
rm -f "$ROUTE_JSON"

echo
echo "[7/14] Authentifizierten Guide-Kernel-Smoke gegen Produktionsdaten READ-ONLY/transaktional ausfuehren ..."
run_kernel_smoke() {
  local base="$1" label="$2"
  (cd "$base" && SMOKE_LABEL="$label" php <<'PHP')
<?php
$base = getcwd();
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$label = getenv('SMOKE_LABEL') ?: 'SMOKE';

Illuminate\Support\Facades\DB::beginTransaction();
try {
    $authorId = Illuminate\Support\Facades\DB::table('guides')
        ->join('users', 'users.id', '=', 'guides.author_id')
        ->where('users.status', 'active')
        ->value('users.id');
    $user = $authorId
        ? App\Models\User::query()->find($authorId)
        : App\Models\User::query()->where('status', 'active')->first();
    if (! $user) {
        throw new RuntimeException('Kein aktiver User fuer Guide-Smoke vorhanden.');
    }

    $issued = App\Models\ApiAccessToken::createForUser($user, 'Guide API 406 Smoke', ['*'], 1);
    $plain = $issued['access_token'];

    $call = function (string $path, int $expected = 200) use ($kernel, $plain, $label): array {
        $request = Illuminate\Http\Request::create($path, 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Authorization', 'Bearer '.$plain);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $kernel->terminate($request, $response);
        echo $label.' '.str_pad($path, 48).' HTTP '.$status."\n";
        if ($status !== $expected) {
            fwrite(STDERR, substr((string) $body, 0, 1500)."\n");
            throw new RuntimeException("Unerwarteter HTTP-Status fuer $path: $status statt $expected");
        }
        $json = json_decode((string) $body, true);
        if (! is_array($json)) {
            throw new RuntimeException("Kein JSON fuer $path");
        }
        return $json;
    };

    $list = $call('/api/v1/guides');
    $mine = $call('/api/v1/guides/mine');
    $options = $call('/api/v1/guides/editor/options');
    if (! isset($list['data']['guides']) || ! is_array($list['data']['guides'])) {
        throw new RuntimeException('Guide-Liste hat nicht den erwarteten Vertrag.');
    }
    if (! isset($mine['data']['guides']) || ! is_array($mine['data']['guides'])) {
        throw new RuntimeException('Meine Guides hat nicht den erwarteten Vertrag.');
    }
    if (! isset($options['data']['categories']) || ! is_array($options['data']['categories'])) {
        throw new RuntimeException('Editor Options hat nicht den erwarteten Vertrag.');
    }
    echo $label.'_LIST_COUNT='.count($list['data']['guides'])."\n";
    echo $label.'_MINE_COUNT='.count($mine['data']['guides'])."\n";
    echo $label.'_CATEGORY_COUNT='.count($options['data']['categories'])."\n";

    $published = App\Models\Guide::withoutGlobalScopes()
        ->whereNotNull('current_published_revision_id')
        ->whereNull('archived_at')
        ->first();
    if ($published) {
        $call('/api/v1/guides/'.rawurlencode($published->slug));
        $call('/api/v1/guides/'.rawurlencode($published->slug).'/comments');
        echo $label.'_DETAIL_SLUG='.$published->slug."\n";
    } else {
        echo $label."_DETAIL=SKIP_NO_PUBLISHED_GUIDE\n";
    }

    $owned = App\Models\Guide::withoutGlobalScopes()
        ->where('author_id', $user->id)
        ->where(function ($q): void {
            $q->whereNotNull('working_revision_id')->orWhereNotNull('current_published_revision_id');
        })
        ->first();
    if ($owned) {
        $call('/api/v1/guides/drafts/'.rawurlencode($owned->slug));
        echo $label.'_DRAFT_SLUG='.$owned->slug."\n";
    } else {
        echo $label."_DRAFT=SKIP_NO_OWNED_GUIDE\n";
    }

    Illuminate\Support\Facades\DB::rollBack();
    echo $label."_DB_ROLLBACK=OK\n";
} catch (Throwable $e) {
    if (Illuminate\Support\Facades\DB::transactionLevel() > 0) {
        Illuminate\Support\Facades\DB::rollBack();
    }
    fwrite(STDERR, $label.'_FAIL='.$e->getMessage()."\n");
    exit(1);
}
PHP
}

run_kernel_smoke "$WT" "PREDEPLOY" || die "Authentifizierter Predeploy-Guide-Smoke fehlgeschlagen"
echo "Predeploy Guide API: OK"

echo
echo "[8/14] Feature-Commit erstellen und NUR Feature-Branch pushen ..."
git -C "$WT" add -- "${EXPECTED_FILES[@]}"
git -C "$WT" -c user.name="HNT.ROCKS Agent" -c user.email="agent@hnt.rocks" commit -m "406: Restore Guides API runtime and routes" >/dev/null
FEATURE_HEAD="$(git -C "$WT" rev-parse HEAD)"
[[ "$(git -C "$WT" rev-parse HEAD^)" == "$MAIN_SHA" ]] || die "Feature-Commit basiert nicht direkt auf current main"

if git -C "$REPO" ls-remote --exit-code --heads origin "$FEATURE_BRANCH" >/dev/null 2>&1; then
  REMOTE_FEATURE="$(git -C "$REPO" ls-remote --heads origin "$FEATURE_BRANCH" | awk '{print $1}')"
  [[ "$REMOTE_FEATURE" == "$MAIN_SHA" ]] || die "Remote Feature-Branch existiert bereits auf unerwartetem Head $REMOTE_FEATURE"
fi
git -C "$WT" push origin "HEAD:refs/heads/$FEATURE_BRANCH"
git -C "$REPO" fetch origin "$FEATURE_BRANCH"
[[ "$(git -C "$REPO" rev-parse "origin/$FEATURE_BRANCH")" == "$FEATURE_HEAD" ]] || die "Feature-Branch Push nicht verifiziert"
echo "Feature-Head: $FEATURE_HEAD"

echo
echo "[9/14] Vollbackup fuer alle 40 geaenderten Dateien und Cache-Status anlegen ..."
mkdir -p "$BACKUP/files"
: > "$BACKUP/.existing-files"
: > "$BACKUP/.new-files"
for rel in "${EXPECTED_FILES[@]}"; do
  if [[ -f "$LIVE/$rel" ]]; then
    mkdir -p "$BACKUP/files/$(dirname "$rel")"
    cp -p "$LIVE/$rel" "$BACKUP/files/$rel"
    echo "$rel" >> "$BACKUP/.existing-files"
  else
    echo "$rel" >> "$BACKUP/.new-files"
  fi
done
if [[ -f "$LIVE/bootstrap/cache/config.php" ]]; then CONFIG_WAS_CACHED=1; fi
if compgen -G "$LIVE/bootstrap/cache/routes-*.php" >/dev/null; then ROUTES_WERE_CACHED=1; fi
printf 'main=%s\nfeature=%s\nlegacy=%s\nconfig_cached=%s\nroutes_cached=%s\n' "$MAIN_SHA" "$FEATURE_HEAD" "$LEGACY_SHA" "$CONFIG_WAS_CACHED" "$ROUTES_WERE_CACHED" > "$BACKUP/manifest.txt"
echo "Backup: $BACKUP"

echo
echo "[10/14] Exakten Feature-Commit atomar live deployen ..."
for rel in "${EXPECTED_FILES[@]}"; do
  atomic_copy "$WT/$rel" "$LIVE/$rel"
done
DEPLOYED=1
for rel in "${EXPECTED_FILES[@]}"; do
  expected="$(git -C "$WT" hash-object "$WT/$rel")"
  actual="$(git -C "$REPO" hash-object "$LIVE/$rel")"
  [[ "$expected" == "$actual" ]] || die "Live-Hash stimmt nicht: $rel"
done
echo "40 Dateien atomar live: OK"

echo
echo "[11/14] Config-/Route-Cache kontrolliert invalidieren bzw. wieder aufbauen ..."
cd "$LIVE"
php artisan route:clear >/dev/null || die "route:clear fehlgeschlagen"
php artisan config:clear >/dev/null || die "config:clear fehlgeschlagen"
if [[ "$CONFIG_WAS_CACHED" -eq 1 ]]; then php artisan config:cache >/dev/null || die "config:cache fehlgeschlagen"; fi
if [[ "$ROUTES_WERE_CACHED" -eq 1 ]]; then php artisan route:cache >/dev/null || die "route:cache fehlgeschlagen"; fi
php artisan route:list --path=api/v1/guides --json > "$BACKUP/live-guide-routes.json" || die "Live route:list fehlgeschlagen"
LIVE_ROUTE_COUNT="$(php -r '$j=json_decode(file_get_contents($argv[1]),true); echo is_array($j)?count($j):0;' "$BACKUP/live-guide-routes.json")"
[[ "$LIVE_ROUTE_COUNT" -eq 19 ]] || die "Live Guide-Routenzahl $LIVE_ROUTE_COUNT statt 19"
echo "Live Guide-Routen: 19 / OK"
cd "$HUB"

echo
echo "[12/14] Externe Live-HTTP Route-Smokes ohne Token: 401 statt Route-404 ..."
for path in "/api/v1/guides" "/api/v1/guides/mine" "/api/v1/guides/editor/options"; do
  code="$(curl -sS -o "$BACKUP/http$(echo "$path" | tr '/' '_').json" -w '%{http_code}' "https://hnt.rocks$path")" || die "curl fehlgeschlagen: $path"
  body="$(cat "$BACKUP/http$(echo "$path" | tr '/' '_').json")"
  echo "https://hnt.rocks$path -> HTTP $code"
  [[ "$code" == "401" ]] || die "$path liefert ohne Token $code statt historischem Auth-Status 401"
  [[ "$body" != *"route api/v1/guides"* ]] || die "$path liefert weiterhin Route-not-found"
done
echo "Externe Route-Erreichbarkeit: OK"

echo
echo "[13/14] Authentifizierten LIVE-Kernel-Smoke inkl. Liste/Mine/Editor/Detail/Draft ausfuehren ..."
run_kernel_smoke "$LIVE" "LIVE" || die "Authentifizierter Live-Guide-Smoke fehlgeschlagen"
echo "Live Guide API authentifiziert: OK"

echo
echo "[14/14] main unveraendert bestaetigen und Abschluss ausgeben ..."
git -C "$REPO" fetch origin main >/dev/null
MAIN_AFTER="$(git -C "$REPO" rev-parse origin/main)"
[[ "$MAIN_AFTER" == "$MAIN_SHA" ]] || die "origin/main wurde waehrend des Deploys veraendert"
DEPLOYED=0

cat <<EOF
============================================================
PATCH 406 ZUSAMMENFASSUNG
============================================================
Ergebnis:                         ERFOLGREICH / LIVE
Ursache:                          Guide-Code + Guide-Routen aus current main entfernt; DB blieb bestehen
origin/main:                      $MAIN_AFTER / UNVERAENDERT
Historische Quelle:               $LEGACY_REF @ $LEGACY_SHA
Feature-Branch:                   $FEATURE_BRANCH
Feature-Head:                     $FEATURE_HEAD
Runtime/Integration-Dateien:      40
Guide-Routen live:                19
Auth-Modell:                      api.token / historischem Vertrag entsprechend
GET /api/v1/guides ohne Token:    401 / ROUTE VORHANDEN
GET /guides/mine ohne Token:      401 / ROUTE VORHANDEN
GET /guides/editor/options:       401 ohne Token / authentifiziert 200
Authentifizierter Listen-Smoke:   OK
Authentifizierter Mine-Smoke:     OK
Authentifizierter Editor-Smoke:   OK
Detail/Kommentare:                getestet wenn veroeffentlichter Guide vorhanden
Draft-Detail:                     getestet wenn Guide des Smoke-Users vorhanden
GuidePolicy:                      WIEDER AKTIV
Guide Notification Setting:       WIEDER AKTIV
Guide-Comment Reporting:          WIEDER AKTIV
Route-Order mine/editor vor slug: OK
Produktions-DB Schema:            VORHANDEN / NICHT MIGRIERT
Produktions-DB Smoke:             TRANSAKTION / ROLLBACK
Migration ausgefuehrt:            NEIN
Demo-Daten:                       NEIN
Flutter-Aenderung:                NEIN
Git Merge/Main-Push:              NEIN
Backup:                           $BACKUP
Log-Datei:                        $LOG
============================================================

ERFOLGREICH / LIVE
EOF
