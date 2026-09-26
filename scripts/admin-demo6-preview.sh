#!/usr/bin/env bash
set -Eeuo pipefail

APP="/home/users/hunthub/www/hnt.rocks"
FEATURE_BRANCH="feature/admin-center-demo6-live-2026-09-26"
BACKUP_ROOT="/home/users/hunthub/admin-demo6-preview-backups"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$STAMP"
STATE_FILE="$BACKUP_ROOT/.active-preview"

FILES=(
  "resources/views/admin/layouts/app.blade.php"
  "resources/views/admin/index.blade.php"
  "public/assets/admin/hnt-acp-dashboard.js"
  "public/assets/admin/hnt-acp-demo6.css"
  "public/assets/admin/hnt-acp-demo6.js"
  "public/assets/admin/hnt-acp-demo6-dashboard.css"
)

die() {
  echo
  echo "ABBRUCH: $*" >&2
  exit 1
}

restore_backup() {
  set +e
  if [[ -d "$BACKUP_DIR/files" ]]; then
    while IFS= read -r -d '' file; do
      rel="${file#"$BACKUP_DIR/files/"}"
      mkdir -p "$APP/$(dirname "$rel")"
      cp -a "$file" "$APP/$rel"
    done < <(find "$BACKUP_DIR/files" -type f -print0)
  fi
  if [[ -f "$BACKUP_DIR/new-files.txt" ]]; then
    while IFS= read -r rel; do
      [[ -n "$rel" ]] && rm -f "$APP/$rel"
    done < "$BACKUP_DIR/new-files.txt"
  fi
  cd "$APP" && php artisan view:clear >/dev/null 2>&1 || true
  rm -f "$STATE_FILE"
}

[[ -d "$APP/.git" ]] || die "Laravel-Repo nicht gefunden: $APP"
mkdir -p "$BACKUP_ROOT"
[[ ! -f "$STATE_FILE" ]] || die "Es ist bereits eine Demo-6-Preview aktiv. Erst Rollback ausführen."

cd "$APP"
echo "== HNT.ROCKS Admin Demo 6 Preview =="
echo "Prüfe aktuellen Serverstand ..."

CURRENT_BRANCH="$(git branch --show-current)"
[[ -n "$CURRENT_BRANCH" ]] || die "Detached HEAD erkannt."

git fetch --quiet origin "$CURRENT_BRANCH" "$FEATURE_BRANCH"

CURRENT_HEAD="$(git rev-parse HEAD)"
REMOTE_CURRENT="$(git rev-parse "origin/$CURRENT_BRANCH")"
REMOTE_FEATURE="$(git rev-parse "origin/$FEATURE_BRANCH")"

[[ "$CURRENT_HEAD" == "$REMOTE_CURRENT" ]] || die "Server-Branch '$CURRENT_BRANCH' ist nicht exakt auf seinem Remote-HEAD. Lokal: $CURRENT_HEAD / Remote: $REMOTE_CURRENT"
[[ -z "$(git status --porcelain)" ]] || die "Backend-Arbeitsverzeichnis ist nicht sauber. Keine fremden Änderungen überschrieben."
git merge-base --is-ancestor "$CURRENT_HEAD" "$REMOTE_FEATURE" || die "Preview-Branch basiert nicht auf dem aktuell laufenden Serverstand '$CURRENT_BRANCH' ($CURRENT_HEAD)."

mkdir -p "$BACKUP_DIR/files"
: > "$BACKUP_DIR/new-files.txt"

echo "Aktiver Server-Branch: $CURRENT_BRANCH"
echo "Server-HEAD: $CURRENT_HEAD"
echo "Preview-HEAD: $REMOTE_FEATURE"
echo "Erstelle Backup: $BACKUP_DIR"

for rel in "${FILES[@]}"; do
  if [[ -e "$APP/$rel" ]]; then
    mkdir -p "$BACKUP_DIR/files/$(dirname "$rel")"
    cp -a "$APP/$rel" "$BACKUP_DIR/files/$rel"
  else
    echo "$rel" >> "$BACKUP_DIR/new-files.txt"
  fi
done

trap 'echo "Fehler beim Preview-Deploy - stelle Backup wieder her ..."; restore_backup' ERR

echo "Spiele ausschließlich Admin-View/CSS/JS aus Preview-Branch ein ..."
for rel in "${FILES[@]}"; do
  mkdir -p "$APP/$(dirname "$rel")"
  tmp="$APP/$rel.demo6-tmp"
  git show "origin/$FEATURE_BRANCH:$rel" > "$tmp"
  mv "$tmp" "$APP/$rel"
done

php artisan view:clear

cat > "$BACKUP_DIR/rollback.sh" <<ROLLBACK
#!/usr/bin/env bash
set -Eeuo pipefail
APP="$APP"
BACKUP_DIR="$BACKUP_DIR"
STATE_FILE="$STATE_FILE"

if [[ -d "\$BACKUP_DIR/files" ]]; then
  while IFS= read -r -d '' file; do
    rel="\${file#"\$BACKUP_DIR/files/"}"
    mkdir -p "\$APP/\$(dirname "\$rel")"
    cp -a "\$file" "\$APP/\$rel"
  done < <(find "\$BACKUP_DIR/files" -type f -print0)
fi

if [[ -f "\$BACKUP_DIR/new-files.txt" ]]; then
  while IFS= read -r rel; do
    [[ -n "\$rel" ]] && rm -f "\$APP/\$rel"
  done < "\$BACKUP_DIR/new-files.txt"
fi

cd "\$APP"
php artisan view:clear
rm -f "\$STATE_FILE"

echo
echo "Demo-6-Preview wurde entfernt. Alter Admin-Stand ist wiederhergestellt."
ROLLBACK
chmod +x "$BACKUP_DIR/rollback.sh"

printf '%s\n' "$BACKUP_DIR" > "$STATE_FILE"
trap - ERR

echo
echo "FERTIG."
echo "Admin Demo 6 ist jetzt temporär unter dem normalen /admin sichtbar."
echo "Server-Branch wurde NICHT gewechselt: $CURRENT_BRANCH"
echo "Backup: $BACKUP_DIR"
echo
echo "Rollback:"
echo "bash $BACKUP_DIR/rollback.sh"
echo
echo "WICHTIG: Solange die Preview aktiv ist, keinen anderen Backend-Deploy in $APP starten."
