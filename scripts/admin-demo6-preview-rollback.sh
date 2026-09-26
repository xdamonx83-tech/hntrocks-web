#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_ROOT="/home/users/hunthub/admin-demo6-preview-backups"
STATE_FILE="$BACKUP_ROOT/.active-preview"

if [[ ! -f "$STATE_FILE" ]]; then
  echo "Keine aktive Demo-6-Preview gefunden."
  exit 0
fi

BACKUP_DIR="$(cat "$STATE_FILE")"
[[ -x "$BACKUP_DIR/rollback.sh" ]] || {
  echo "Rollback-Script fehlt: $BACKUP_DIR/rollback.sh" >&2
  exit 1
}

exec bash "$BACKUP_DIR/rollback.sh"
