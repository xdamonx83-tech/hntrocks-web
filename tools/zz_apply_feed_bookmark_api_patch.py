from pathlib import Path
import re


def read(path: str) -> str:
    return Path(path).read_text()


def write(path: str, text: str) -> None:
    Path(path).write_text(text)


routes = "routes/api.php"
text = read(routes)
if "use App\\Http\\Controllers\\Feed\\FeedBookmarkController;" not in text:
    text = text.replace(
        "use App\\Http\\Controllers\\Feed\\FeedTranslationController;",
        "use App\\Http\\Controllers\\Feed\\FeedBookmarkController;\n"
        "use App\\Http\\Controllers\\Feed\\FeedTranslationController;",
        1,
    )
if "/feed/{post}/bookmark" not in text:
    pattern = re.compile(
        r"^(\s*Route::post\('/feed/\{post\}/reaction'.*?;\s*)$",
        re.M,
    )
    match = pattern.search(text)
    if not match:
        raise SystemExit("routes/api.php: feed reaction route not found")
    indent = re.match(r"\s*", match.group(1)).group(0)
    bookmark_route = (
        f"{indent}Route::post('/feed/{{post}}/bookmark', "
        "[FeedBookmarkController::class, 'toggle'])"
        "->name('feed.bookmarks.toggle');"
    )
    text = text[: match.end()] + bookmark_route + text[match.end() :]
write(routes, text)

model = "app/Models/FeedPost.php"
text = read(model)
if "public function recentReactions(): HasMany" not in text:
    marker = "    public function translations(): HasMany"
    index = text.find(marker)
    if index < 0:
        raise SystemExit("FeedPost.php: translations relation not found")
    method = """    public function recentReactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class)->latest()->limit(3);
    }

"""
    text = text[:index] + method + text[index:]
write(model, text)

resource = "app/Http/Resources/Api/FeedPostResource.php"
text = read(resource)
if "'reaction_preview'" not in text:
    line_pattern = re.compile(
        r"^(\s*)'reactions_count' => .*?,\s*$",
        re.M,
    )
    match = line_pattern.search(text)
    if not match:
        raise SystemExit("FeedPostResource.php: reactions_count line not found")
    indent = match.group(1)
    payload = """
{indent}'reaction_preview' => $this->whenLoaded(
{indent}    'recentReactions',
{indent}    fn () => UserResource::collection(
{indent}        $this->recentReactions->pluck('user')->filter()->values()
{indent}    )
{indent}),""".format(indent=indent)
    text = text[: match.end()] + payload + text[match.end() :]
write(resource, text)

for path in [
    "app/Http/Controllers/Api/V1/ApiFeedController.php",
    "app/Http/Controllers/Api/V1/ApiFeedEngagementController.php",
]:
    text = read(path)
    if "recentReactions.user.profile" not in text:
        pattern = re.compile(r"^(\s*)'viewerBookmark',\s*$", re.M)
        match = pattern.search(text)
        if not match:
            raise SystemExit(f"{path}: viewerBookmark relation not found")
        indent = match.group(1)
        text = pattern.sub(
            lambda item: (
                f"{item.group(1)}'viewerBookmark',\n"
                f"{item.group(1)}'recentReactions.user.profile',"
            ),
            text,
        )
    write(path, text)

checks = {
    routes: ["FeedBookmarkController", "/feed/{post}/bookmark"],
    model: ["recentReactions"],
    resource: ["reaction_preview", "recentReactions"],
}
for path, tokens in checks.items():
    text = read(path)
    for token in tokens:
        if token not in text:
            raise SystemExit(f"{path}: missing patched token {token!r}")
