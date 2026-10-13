#!/usr/bin/env python3
"""Read-only preflight for HNT AI Community manual release.

Never writes to production, reads .env contents, runs migrations, or publishes.
This is an inventory, NOT a deployment procedure. Run from isolated feature
checkout: python3 scripts/ai_community_release_preflight.py
"""
from __future__ import annotations

import hashlib
import os
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[1]
LIVE = Path("/home/users/hunthub/www/hnt.rocks")
REACT = Path("/home/users/hunthub/react/hntrocks-react-redesign")
TARGET = "feature/ai-community-characters-20261012"


def run(*argv: str, cwd: Path | None = None) -> tuple[int, str]:
    try:
        p = subprocess.run(argv, cwd=cwd, stdout=subprocess.PIPE,
                           stderr=subprocess.PIPE, text=True, timeout=20)
        return p.returncode, p.stdout.strip()
    except (OSError, subprocess.TimeoutExpired):
        return 99, ""


def digest(p: Path) -> str:
    try:
        if not p.is_file() or p.is_symlink():
            return "missing"
        return hashlib.sha256(p.read_bytes()).hexdigest()
    except (PermissionError, OSError):
        return "not-readable"


def main() -> int:
    rc, rev = run("git", "-C", str(ROOT), "rev-parse", "HEAD")
    if rc or len(rev) != 40:
        print("STOP: cannot establish feature revision")
        return 2
    rc, branch = run("git", "-C", str(ROOT), "branch", "--show-current")
    if rc or branch != TARGET:
        print("STOP: unexpected source branch")
        return 2
    rc, changes = run("git", "-C", str(ROOT), "diff", "--name-status", "origin/main", "HEAD")
    if rc:
        print("STOP: cannot establish source changes")
        return 2

    print("HNT_AI_COMMUNITY_RELEASE_PREFLIGHT READ_ONLY")
    print("FEATURE_REV", rev)
    print("PRODUCTION_BACKEND", str(LIVE))
    print("PRODUCTION_REACT", str(REACT))
    print("NO_FILE_WRITES NO_DATABASE_CONNECTION NO_PUBLISH")
    statuses: dict[str, int] = {}
    unsafe: list[str] = []
    for line in changes.splitlines():
        if not line or "\t" not in line:
            continue
        action, rel = line.split("\t", 1)
        if not rel.startswith(("app/", "config/", "database/", "resources/", "routes/")):
            continue
        staged = ROOT / rel
        deployed = LIVE / rel
        stage_sum = digest(staged)
        live_sum = digest(deployed)
        if action == "A":
            state = "NEW" if live_sum == "missing" else "COLLISION"
        elif action == "M":
            if live_sum == "missing":
                state = "LIVE_MISSING"
            elif stage_sum == live_sum:
                state = "MATCHES_STAGE"
            elif live_sum == "not-readable":
                state = "UNKNOWN_UNREADABLE"
            else:
                base_rc, base_data = run("git", "-C", str(ROOT), "show", f"origin/main:{rel}")
                base_sum = hashlib.sha256(base_data.encode()).hexdigest() if base_rc == 0 else None
                state = "LIVE_MATCHES_BASE" if base_sum and live_sum == base_sum else "LIVE_MODIFIED_OR_DIVERGED"
        else:
            state = "OTHER"
        statuses[state] = statuses.get(state, 0) + 1
        if state in ("COLLISION", "LIVE_MISSING", "UNKNOWN_UNREADABLE", "LIVE_MODIFIED_OR_DIVERGED"):
            unsafe.append(rel)
        print(f"{state:25s} {action:2s} {rel}")
    print("FILES", statuses)
    if unsafe:
        print("HOLD_CONFLICT_COUNT", len(unsafe))
        for rel in unsafe:
            print("HOLD", rel)
    rcrc, react_diff = run("git", "-C", str(REACT), "status", "--porcelain")
    react_dirty = len(react_diff.splitlines()) if rcrc == 0 and react_diff else 0
    print("REACT_DIRTY_FILES", react_dirty if rcrc == 0 else "UNKNOWN")
    for rel in ("src/pages/app/design-preview/feed/components/redesign-feed-post.tsx",
                "src/pages/app/design-preview/feed/components/redesign-feed-list.tsx",
                "index.html"):
        print("REACT_PROTECT", rel, "MODIFIED" if any(x.endswith(rel) for x in react_diff.splitlines()) else "unmodified")
    disk = shutil.disk_usage(str(ROOT))
    print("STAGE_FREE_GIB", round(disk.free / 1024**3, 2))
    print("DB_DUMP_BIN", shutil.which("mariadb-dump") or "missing")
    print("PRODUCTION_ENV_EXISTS", (LIVE / ".env").is_file())
    print("FEATURE_MIGRATION_EXISTS",
          (ROOT / "database/migrations/2026_10_12_000001_create_ai_community_tables.php").is_file())
    print("LIVE_MIGRATION_EXISTS",
          (LIVE / "database/migrations/2026_10_12_000001_create_ai_community_tables.php").is_file())
    print("PRODUCTION_DEPLOY_IS_NOT_AUTHORIZED_BY_THIS_REPORT")
    if unsafe or react_dirty or not (LIVE / ".env").is_file():
        print("DECISION HOLD_FOR_SAFE_MERGE_AND_VERIFIED_BACKUP")
    else:
        print("DECISION READ_ONLY_PREFLIGHT_COMPLETE__BACKUP_AND_DB_SCHEMA_CHECK_STILL_REQUIRED")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
