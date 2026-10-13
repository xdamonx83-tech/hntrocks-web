#!/usr/bin/env python3
"""Resolve four known AI community merge conflicts in the private overlay.

Preserves LIVE code and admin security. Never writes to production. Explicitly
SKIPS the hourly generator scheduler for the initial manual-publish pilot.
"""
from __future__ import annotations
import hashlib
import json
from pathlib import Path

LIVE=Path("/home/users/hunthub/www/hnt.rocks")
SOURCE=Path(__file__).resolve().parents[1]
OVERLAY=Path("/home/codex/hnt-ai-community-overlay-20261013")

def h(b:bytes)->str:return hashlib.sha256(b).hexdigest()

def merge(rel:str, callback):
    original=(LIVE/rel).read_bytes()
    file=OVERLAY/rel
    if file.read_bytes()!=original:
        raise RuntimeError("STOP staged file differs from live preimage: "+rel)
    src=original.decode("utf-8")
    final=callback(src).encode("utf-8")
    if final==original:
        raise RuntimeError("STOP: no semantic change for "+rel)
    file.write_bytes(final)
    return dict(path=rel,result="MANUAL_SCOPED_MERGE",deployed_sha256=h(original),staged_sha256=h(final))

def profile(src:str)->str:
    for signature in ("public function edit(Request $request): View",
                      "public function update(Request $request, MediaService $mediaService, GamificationService $gamification, ReferralService $referrals): RedirectResponse|JsonResponse"):
        needle=signature+"\n    {\n"
        if src.count(needle)!=1:
            raise RuntimeError("STOP: unexpected live profile method declaration")
        src=src.replace(needle,needle+"        abort_if($request->user()?->is_ai_character, 403);\n",1)
    return src

def nav(src:str)->str:
    anchor="            ['label' => 'Hunt-News',"
    if src.count(anchor)!=1 or "admin.ai-community.index" in src:
        raise RuntimeError("STOP: unexpected admin nav structure")
    return src.replace(anchor,"            ['label' => 'KI Community', 'route' => 'admin.ai-community.index', 'active' => 'admin.ai-community.*', 'icon' => 'profile-2user'],\n"+anchor,1)

def web(src:str)->str:
    original_routes=(SOURCE/"routes/web.php").read_text("utf-8")
    imports="use App\\Http\\Controllers\\Admin\\AdminAiCommunityController;\n"
    anchor="use App\\Http\\Controllers\\Admin\\AdminContentController;\n"
    if src.count(anchor)!=1 or imports in src:
        raise RuntimeError("STOP unexpected web route import")
    src=src.replace(anchor,anchor+imports,1)
    # Critical: live must continue to gate this route group through hnt.admin.
    guard="Route::prefix('admin')->middleware('hnt.admin')->name('admin.')->group(function (): void {"
    if src.count(guard)!=1:
        raise RuntimeError("STOP live admin middleware missing/changed")
    group_index=src.index(guard)
    anchor="        Route::get('/', [AdminDashboardController::class, 'index'])->name('index');"
    if src.count(anchor)!=1 or src.index(anchor)<group_index:
        raise RuntimeError("STOP admin dashboard anchor outside hnt.admin group")
    routes=[line for line in original_routes.splitlines()
            if line.startswith("        Route::") and "'/ai-community" in line]
    if len(routes)!=9 or len({line for line in routes})!=9:
        raise RuntimeError("STOP source AI route list changed")
    if "admin.ai-community.index" in src or "'/ai-community'" in src:
        raise RuntimeError("STOP live AI route collision")
    src=src.replace(anchor,"\n".join(routes)+"\n"+anchor,1)
    if src.count(guard)!=1:
        raise RuntimeError("STOP admin middleware lost")
    return src

def main():
    f=OVERLAY/"overlay-inventory.json"
    if not f.is_file():raise RuntimeError("STOP missing private overlay")
    report=json.loads(f.read_text("utf-8"))
    filemap={r["path"]:r for r in report["files"]}
    specs=[
        ("app/Http/Controllers/Profile/ProfileController.php",profile),
        ("resources/views/admin/partials/nav.blade.php",nav),
        ("routes/web.php",web),
    ]
    for rel, func in specs:
        if filemap[rel]["result"]!="CONFLICT_REQUIRES_MANUAL_MERGE":
            raise RuntimeError("STOP unexpected conflict state "+rel)
        result=merge(rel,func)
        filemap[rel].update(result)
        print("STAGED",rel)
    rel="routes/console.php"
    if filemap[rel]["result"]!="CONFLICT_REQUIRES_MANUAL_MERGE":
        raise RuntimeError("STOP unexpected scheduler conflict state")
    if (OVERLAY/rel).read_bytes()!=(LIVE/rel).read_bytes():
        raise RuntimeError("STOP scheduler staging content diverged")
    filemap[rel].update({"result":"SKIPPED_AUTOMATIC_SCHEDULER",
                         "deployed_sha256":h((LIVE/rel).read_bytes()),
                         "staged_sha256":h((OVERLAY/rel).read_bytes())})
    f.write_text(json.dumps(report,indent=2)+"\n","utf-8")
    print("ISOLATED_MANUAL_MERGE_COMPLETE")
    print("LIVE_HNT_ADMIN_MIDDLEWARE_PRESERVED")
    print("HOURLY_AUTO_GENERATION_NOT_INCLUDED")
    print("NO_LIVE_CHANGES_NO_DATABASE_ACTIONS")

if __name__=="__main__":
    main()
