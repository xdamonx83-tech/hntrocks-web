#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/openai-news-key-cleanup-2026-09-29"
BASE_HEAD="14269a830bbcb217f3880db72204a784b1cdf983"
WORKDIR="/home/users/hunthub/www/hnt.rocks"

cd "$WORKDIR"

CURRENT_BRANCH="$(git branch --show-current)"
if [ "$CURRENT_BRANCH" != "$EXPECTED_BRANCH" ]; then
  echo "ABBRUCH: Falscher Branch: $CURRENT_BRANCH"
  exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "ABBRUCH: Lokale TRACKED Änderungen vorhanden:"
  git status --short
  exit 1
fi

git fetch origin "$EXPECTED_BRANCH"
LOCAL_HEAD="$(git rev-parse HEAD)"
REMOTE_HEAD="$(git rev-parse "origin/$EXPECTED_BRANCH")"

if [ "$LOCAL_HEAD" != "$REMOTE_HEAD" ]; then
  echo "ABBRUCH: Lokaler HEAD ist nicht der aktuelle Remote-HEAD."
  echo "Lokal:  $LOCAL_HEAD"
  echo "Remote: $REMOTE_HEAD"
  exit 1
fi

if ! git merge-base --is-ancestor "$BASE_HEAD" HEAD; then
  echo "ABBRUCH: Erwartete News-Translation-Basis fehlt."
  exit 1
fi

if [ ! -f .env ]; then
  echo "ABBRUCH: Server-.env wurde nicht gefunden."
  exit 1
fi

echo "Branch: $CURRENT_BRANCH"
echo "HEAD: $LOCAL_HEAD"

git diff --check "$BASE_HEAD..HEAD"
php -l config/hunthub.php
php -l app/Services/Translation/FeedTranslationService.php
php -l app/Services/Cups/CupSubmissionAnalysisService.php
php artisan about >/dev/null
php artisan list --raw | grep -q '^hnt:news-translate '
php artisan route:list --path='api/v1/admin/news/articles' | grep -q 'translate'

echo
echo "OpenAI-Key-Variablen vor Cleanup (nur Namen, keine Werte):"
awk -F= '
  /^[[:space:]]*[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=/ {
    name=$1
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
    if (name ~ /OPENAI/ && name ~ /API_KEY$/) print " - " name
  }
' .env | sort -u || true

echo
read -rsp "Neuen HH_TRANSLATION_OPENAI_API_KEY eingeben: " NEW_OPENAI_KEY
echo

if [ -z "$NEW_OPENAI_KEY" ]; then
  echo "ABBRUCH: Kein Key eingegeben."
  exit 1
fi

case "$NEW_OPENAI_KEY" in
  sk-*) ;;
  *)
    echo "ABBRUCH: Der eingegebene Wert sieht nicht wie ein OpenAI API-Key aus."
    unset NEW_OPENAI_KEY
    exit 1
    ;;
esac

TMP_ENV="$(mktemp "$WORKDIR/.env.openai-cleanup.XXXXXX")"
cleanup_tmp() {
  rm -f "$TMP_ENV" 2>/dev/null || true
  unset NEW_OPENAI_KEY 2>/dev/null || true
}
trap cleanup_tmp EXIT

printf '%s' "$NEW_OPENAI_KEY" | php -r '
$path = ".env";
$tmp = $argv[1];
$newKey = trim(stream_get_contents(STDIN));

if ($newKey === "") {
    fwrite(STDERR, "Kein Translation-Key erhalten.\n");
    exit(1);
}

$lines = file($path, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    fwrite(STDERR, ".env konnte nicht gelesen werden.\n");
    exit(1);
}

$disabled = [
    "HH_CUP_AI_ENABLED" => "false",
    "HH_AI_CONTENT_DISCLOSURE_ENABLED" => "false",
    "HH_MEDIA_MODERATION_ENABLED" => "false",
];

$out = [];
foreach ($lines as $line) {
    if (preg_match("/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/", $line, $m)) {
        $name = $m[1];

        if ($name === "HH_TRANSLATION_OPENAI_API_KEY") {
            continue;
        }

        if (str_contains($name, "OPENAI") && str_ends_with($name, "API_KEY")) {
            continue;
        }

        if (array_key_exists($name, $disabled)) {
            continue;
        }
    }

    $out[] = $line;
}

while ($out !== [] && trim((string) end($out)) === "") {
    array_pop($out);
}

$out[] = "";
$out[] = "# OpenAI: News-Uebersetzung only";
$out[] = "HH_TRANSLATION_OPENAI_API_KEY=".$newKey;
foreach ($disabled as $name => $value) {
    $out[] = $name."=".$value;
}

$payload = implode(PHP_EOL, $out).PHP_EOL;
if (file_put_contents($tmp, $payload, LOCK_EX) === false) {
    fwrite(STDERR, "Neue .env konnte nicht geschrieben werden.\n");
    exit(1);
}
chmod($tmp, 0600);
' "$TMP_ENV"

unset NEW_OPENAI_KEY

mv "$TMP_ENV" .env
chmod 0600 .env
trap - EXIT

echo
echo "OpenAI-Key-Variablen nach Cleanup (nur Namen, keine Werte):"
REMAINING_KEYS="$(awk -F= '
  /^[[:space:]]*[A-Za-z_][A-Za-z0-9_]*[[:space:]]*=/ {
    name=$1
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
    if (name ~ /OPENAI/ && name ~ /API_KEY$/) print name
  }
' .env | sort -u)"
printf '%s\n' "$REMAINING_KEYS" | sed '/^$/d;s/^/ - /'

if [ "$REMAINING_KEYS" != "HH_TRANSLATION_OPENAI_API_KEY" ]; then
  echo "ABBRUCH: Es existiert noch mehr als der erlaubte Translation-Key."
  exit 1
fi

php artisan optimize:clear
php artisan config:cache

php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = trim((string) config("hunthub.news_translation.openai_api_key", ""));
if ($key === "") {
    fwrite(STDERR, "ABBRUCH: News-Translation-Key ist im Laravel-Config-Cache nicht gesetzt.\n");
    exit(1);
}

if ((bool) config("hunthub.ai_content_disclosure.enabled", true)) {
    fwrite(STDERR, "ABBRUCH: AI Content Disclosure ist noch aktiviert.\n");
    exit(1);
}

if ((bool) config("hunthub.media_moderation.enabled", true)) {
    fwrite(STDERR, "ABBRUCH: Media Moderation ist noch aktiviert.\n");
    exit(1);
}

echo "Laravel OpenAI-Konfiguration: OK\n";
'

php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = $app->make(App\Services\Translation\FeedTranslationService::class);
$result = $service->translateMap(
    ["smoke" => "HNT.ROCKS news translation smoke test."],
    "en",
    "de"
);

if (!isset($result["smoke"]) || trim((string) $result["smoke"]) === "") {
    fwrite(STDERR, "NEWS TRANSLATION SMOKE: FEHLER\n");
    exit(1);
}

echo "NEWS TRANSLATION SMOKE: OK\n";
'

echo
echo "OPENAI NEWS-ONLY CLEANUP LIVE"
echo "Aktiver Key: HH_TRANSLATION_OPENAI_API_KEY (Wert wird nicht ausgegeben)"
echo "Cup-KI: deaktiviert"
echo "AI Content Disclosure/Post-KI-Flag: deaktiviert"
echo "Media Moderation: deaktiviert"
echo "Feed-/Kommentar-OpenAI-Uebersetzung: deaktiviert"
echo "HEAD: $(git rev-parse HEAD)"
