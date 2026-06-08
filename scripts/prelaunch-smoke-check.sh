#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://hnt.rocks}"
BASE_URL="${BASE_URL%/}"

PATHS=(
  "/login"
  "/register"
  "/forgot-password"
  "/impressum"
  "/datenschutz"
  "/nutzungsbedingungen"
  "/netiquette"
  "/cups"
  "/cups/bayou-blood-cup"
  "/hall-of-fame"
)

printf 'hnt.rocks public smoke check: %s\n' "$BASE_URL"
printf '%s\n' '----------------------------------------'
FAILED=0

for path in "${PATHS[@]}"; do
  url="${BASE_URL}${path}"
  code="$(curl -ksS -o /dev/null -w '%{http_code}' "$url" || true)"

  if [[ "$code" == "200" ]]; then
    printf '[OK]      %s -> %s\n' "$path" "$code"
  elif [[ "$code" == "301" || "$code" == "302" || "$code" == "303" ]]; then
    printf '[HINWEIS] %s -> %s Redirect\n' "$path" "$code"
  else
    printf '[FEHLER]  %s -> %s\n' "$path" "$code"
    FAILED=1
  fi
done

printf '%s\n' '----------------------------------------'
if [[ "$FAILED" -ne 0 ]]; then
  printf 'Mindestens eine öffentliche Launch-Seite antwortet nicht sauber.\n'
  exit 1
fi

printf 'Öffentliche Smoke-Test-URLs antworten grundsätzlich. Browser-/Login-/DB-Tests bleiben trotzdem nötig.\n'
