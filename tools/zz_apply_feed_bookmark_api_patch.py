from pathlib import Path
import re


def replace_once(path: str, old: str, new: str) -> None:
    file = Path(path)
    text = file.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(
            f"{path}: expected one match, found {count}: {old[:90]!r}"
        )
    file.write_text(text.replace(old, new, 1))


routes = "routes/api.php"
replace_once(
    routes,
    "use App\\Http\\Controllers\\Feed\\FeedTranslationController;\n",
    "use App\\Http\\Controllers\\Feed\\FeedBookmarkController;\n"
    "use App\\Http\\Controllers\\Feed\\FeedTranslationController;\n",
)
replace_once(
    routes,
    "        Route::post('/feed/{post}/reaction', "
    "[ApiFeedEngagementController::class, 'toggleReaction'])"
    "->name('feed.reactions.toggle');\n",
    "        Route::post('/feed/{post}/reaction', "
    "[ApiFeedEngagementController::class, 'toggleReaction'])"
    "->name('feed.reactions.toggle');\n"
    "        Route::post('/feed/{post}/bookmark', "
    "[FeedBookmarkController::class, 'toggle'])"
    "->name('feed.bookmarks.toggle');\n",
)

model = "app/Models/FeedPost.php"
replace_once(
    model,
    """    public function reactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class);
    }


    public function translations(): HasMany
""",
    """    public function reactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class);
    }

    public function recentReactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class)->latest()->limit(3);
    }

    public function translations(): HasMany
""",
)

resource = "app/Http/Resources/Api/FeedPostResource.php"
replace_once(
    resource,
    "            'reactions_count' => (int) ($this->reactions_count ?? 0),\n"
    "            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),\n",
    """            'reactions_count' => (int) ($this->reactions_count ?? 0),
            'reaction_preview' => $this->whenLoaded(
                'recentReactions',
                fn () => UserResource::collection(
                    $this->recentReactions->pluck('user')->filter()->values()
                )
            ),
            'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
""",
)

controller_paths = [
    "app/Http/Controllers/Api/V1/ApiFeedController.php",
    "app/Http/Controllers/Api/V1/ApiFeedEngagementController.php",
]
relation_pattern = re.compile(r"(?m)^(\s*)'viewerBookmark',\s*$")
for path in controller_paths:
    file = Path(path)
    text = file.read_text()
    updated, count = relation_pattern.subn(
        lambda match: (
            f"{match.group(1)}'viewerBookmark',\n"
            f"{match.group(1)}'recentReactions.user.profile',"
        ),
        text,
    )
    if count < 1:
        raise SystemExit(f"{path}: no viewerBookmark relation found")
    file.write_text(updated)

expected = {
    routes: ["FeedBookmarkController", "/feed/{post}/bookmark"],
    model: ["recentReactions"],
    resource: ["reaction_preview", "recentReactions"],
}
for path, tokens in expected.items():
    text = Path(path).read_text()
    for token in tokens:
        if token not in text:
            raise SystemExit(f"{path}: missing patched token {token!r}")
