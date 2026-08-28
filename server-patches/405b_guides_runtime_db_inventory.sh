#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
LIVE="$HUB/www/hnt.rocks"
REPO="$LIVE"
LEGACY_REF="codex/guides-mine-api"
LEGACY_SHA="3025a6d9812e9a8e3bf4eb748484b4b7e7085a4b"

cat <<'HEAD'
============================================================
HNT.ROCKS 405b - GUIDES RUNTIME / DB INVENTUR (READ-ONLY)
============================================================
- KEIN Deploy
- KEINE Migration
- KEINE DB-Aenderung
- KEINE Flutter-Aenderung
- ermittelt vollstaendig, was vom bestehenden Guide-System live noch da ist
============================================================
HEAD

git -C "$REPO" fetch origin main "$LEGACY_REF" agent/server-patches --prune >/dev/null
MAIN_SHA="$(git -C "$REPO" rev-parse origin/main)"
LEGACY_NOW="$(git -C "$REPO" rev-parse "origin/$LEGACY_REF")"
echo "origin/main:    $MAIN_SHA"
echo "Guide-Historie: $LEGACY_NOW"
[[ "$LEGACY_NOW" == "$LEGACY_SHA" ]] || { echo "ABBRUCH: Guide-Historie hat sich veraendert."; exit 2; }

echo
echo "=== 1. API-ROUTEN ==="
ROUTES="$(cd "$LIVE" && php artisan route:list --path=api/v1/guides --json 2>/dev/null || true)"
printf '%s' "$ROUTES" | php -r '$j=json_decode(stream_get_contents(STDIN),true); echo "Guide-Routen live: ".(is_array($j)?count($j):0).PHP_EOL; if(is_array($j)){foreach($j as $r){echo ($r["method"]??"?")." ".($r["uri"]??"?")." ".($r["name"]??"").PHP_EOL;}}'

echo
echo "=== 2. GUIDE-RUNTIME-DATEIEN ==="
FILES=(
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
)
PRESENT=0
MISSING=0
for rel in "${FILES[@]}"; do
  git -C "$REPO" cat-file -e "$LEGACY_SHA:$rel" 2>/dev/null || { echo "HISTORIE_FEHLT $rel"; continue; }
  if [[ -f "$LIVE/$rel" ]]; then
    echo "LIVE_OK       $rel"
    PRESENT=$((PRESENT+1))
  else
    echo "LIVE_FEHLT    $rel"
    MISSING=$((MISSING+1))
  fi
done
echo "Runtime vorhanden: $PRESENT | fehlend: $MISSING"

echo
echo "=== 3. ABHAENGIGKEITEN IM CURRENT MAIN ==="
for rel in app/Services/MediaService.php app/Services/NotificationService.php app/Models/UserBlock.php app/Models/UserPrivacySetting.php app/Models/UserNotificationSetting.php app/Models/MediaAsset.php; do
  [[ -f "$LIVE/$rel" ]] && echo "OK     $rel" || echo "FEHLT  $rel"
done

echo
echo "=== 4. PRODUKTIONS-DB / MIGRATIONEN (READ-ONLY) ==="
cd "$LIVE"
php <<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = [
 'guide_categories','guides','guide_revisions','guide_media','guide_moderation_events',
 'guide_comments','guide_helpful_votes','guide_bookmarks','guide_reputation_entries'
];
$missingTables = [];
foreach ($tables as $table) {
    $ok = Illuminate\Support\Facades\Schema::hasTable($table);
    echo ($ok ? 'TABLE_OK    ' : 'TABLE_FEHLT ').$table.PHP_EOL;
    if (! $ok) $missingTables[] = $table;
}

if (Illuminate\Support\Facades\Schema::hasTable('guides')) {
    foreach (['show_in_profile','current_published_revision_id','working_revision_id','helpful_count','bookmarks_count','comments_count'] as $column) {
        echo (Illuminate\Support\Facades\Schema::hasColumn('guides', $column) ? 'COLUMN_OK    ' : 'COLUMN_FEHLT ').'guides.'.$column.PHP_EOL;
    }
    echo 'GUIDE_ROWS='.Illuminate\Support\Facades\DB::table('guides')->count().PHP_EOL;
}
if (Illuminate\Support\Facades\Schema::hasTable('guide_categories')) {
    echo 'GUIDE_CATEGORIES='.Illuminate\Support\Facades\DB::table('guide_categories')->count().PHP_EOL;
}
if (Illuminate\Support\Facades\Schema::hasTable('user_notification_settings')) {
    echo (Illuminate\Support\Facades\Schema::hasColumn('user_notification_settings','guides') ? 'COLUMN_OK    ' : 'COLUMN_FEHLT ').'user_notification_settings.guides'.PHP_EOL;
}

$migrations = [
 '2026_07_18_000001_create_community_guides',
 '2026_07_18_000002_add_guides_notification_setting',
 '2026_07_19_235900_add_show_in_profile_to_guides_table',
];
$ran = Illuminate\Support\Facades\DB::table('migrations')->whereIn('migration',$migrations)->pluck('migration')->all();
foreach ($migrations as $m) echo (in_array($m,$ran,true) ? 'MIGRATION_OK    ' : 'MIGRATION_FEHLT ').$m.PHP_EOL;

echo 'DB_CLASS='.(empty($missingTables) ? 'SCHEMA_VORHANDEN' : 'SCHEMA_UNVOLLSTAENDIG').PHP_EOL;
PHP

echo
echo "=== 5. HISTORISCHE WEB-ROUTE-ABHAENGIGKEITEN ==="
php artisan route:list --json 2>/dev/null | php -r '$j=json_decode(stream_get_contents(STDIN),true)?:[]; $names=array_column($j,"name"); foreach(["guides.show","guides.mine","guides.edit"] as $n){echo (in_array($n,$names,true)?"ROUTE_OK    ":"ROUTE_FEHLT ").$n.PHP_EOL;}'

echo
echo "=== 6. GUIDE-INTEGRATIONEN CURRENT MAIN ==="
grep -q "guide_comment" app/Http/Controllers/Reports/ReportController.php && echo "REPORT_GUIDE_COMMENT=JA" || echo "REPORT_GUIDE_COMMENT=NEIN"
grep -q "'guides'" app/Models/UserNotificationSetting.php && echo "NOTIFICATION_GUIDES=JA" || echo "NOTIFICATION_GUIDES=NEIN"
grep -q "GuidePolicy" app/Providers/AppServiceProvider.php && echo "GUIDE_POLICY_REGISTERED=JA" || echo "GUIDE_POLICY_REGISTERED=NEIN"

echo
echo "============================================================"
echo "405b FERTIG - NUR INVENTUR, NICHTS DEPLOYED"
echo "============================================================"
