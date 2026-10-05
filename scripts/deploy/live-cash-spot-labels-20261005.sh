#!/usr/bin/env bash
set -euo pipefail
umask 077

LIVE=/home/users/hunthub/www/hnt.rocks
FILE=app/Http/Controllers/Admin/AdminMapController.php
REF=origin/fix/maps-cash-spot-image-description-20261005
OLD=326a1f46b4f426d04f286452f72ca0367920755a
BACKUP=/home/users/hunthub/backups/cashspot-labels-$(date +%Y%m%d-%H%M%S)
mkdir -p "$BACKUP"

git -C "$LIVE" rev-parse --verify "$REF^{commit}" >/dev/null
EXPECTED_NEW=$(git -C "$LIVE" rev-parse "$REF:$FILE")
ACTUAL=$(git -C "$LIVE" hash-object "$LIVE/$FILE")
if [[ "$ACTUAL" != "$OLD" && "$ACTUAL" != "$EXPECTED_NEW" ]]; then
  echo "STOPP: AdminMapController stimmt nicht mit dem letzten Fix überein."
  echo "Aktuell: $ACTUAL"
  exit 1
fi

TMP=$(mktemp)
trap 'rm -f "$TMP"' EXIT
git -C "$LIVE" show "$REF:$FILE" > "$TMP"
php -l "$TMP" >/dev/null
cp -a "$LIVE/$FILE" "$BACKUP/AdminMapController.php"

echo "=== Bereits freigegebene Spots korrigieren ==="
export HNT_LIVE="$LIVE" HNT_BACKUP="$BACKUP"
php <<'PHP'
<?php
chdir(getenv('HNT_LIVE'));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\HntMapCashSpotSubmission as Submission;
use App\Models\HntMapMarker as Marker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$rows = Submission::query()
    ->where('status', Submission::STATUS_APPROVED)
    ->whereNotNull('description')
    ->whereNotNull('hnt_map_marker_id')
    ->with('marker')
    ->get()
    ->filter(function ($submission): bool {
        $marker = $submission->marker;
        return $marker !== null
            && $marker->type === 'cash'
            && $marker->legacy_key === 'submission:'.$submission->id
            && $marker->label_de === 'Kassenspot'
            && $marker->label_en === 'Cash spot'
            && trim((string) $submission->description) !== '';
    })->values();

$before = $rows->map(fn ($s) => [
    'submission_id' => $s->id,
    'marker_id' => $s->hnt_map_marker_id,
    'old_label_de' => $s->marker->label_de,
    'old_label_en' => $s->marker->label_en,
    'description' => $s->description,
])->all();

file_put_contents(
    getenv('HNT_BACKUP').'/affected-labels.json',
    json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
);

$count = DB::transaction(function () use ($rows): int {
    $changed = 0;
    foreach ($rows as $s) {
        $marker = Marker::query()->lockForUpdate()->find($s->hnt_map_marker_id);
        if (!$marker || $marker->legacy_key !== 'submission:'.$s->id
            || $marker->type !== 'cash'
            || $marker->label_de !== 'Kassenspot'
            || $marker->label_en !== 'Cash spot') {
            continue;
        }
        $label = Str::limit(trim((string) $s->description), 120, '');
        $marker->forceFill(['label_de' => $label, 'label_en' => $label])->save();
        ++$changed;
    }
    return $changed;
});
echo "Korrigierte vorhandene Labels: {$count}\n";
PHP

echo "=== Freigabe zukünftiger Spots korrigieren ==="
if [[ "$ACTUAL" != "$EXPECTED_NEW" ]]; then
  chown --reference="$LIVE/$FILE" "$TMP"
  chmod --reference="$LIVE/$FILE" "$TMP"
  cp -p "$TMP" "$LIVE/$FILE.new"
  mv -f "$LIVE/$FILE.new" "$LIVE/$FILE"
fi

echo "CASH-SPOT-LABELS KORRIGIERT"
echo "Backup: $BACKUP"
