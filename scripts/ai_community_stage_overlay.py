#!/usr/bin/env python3
"""Stage candidate AI community changes against currently deployed file bytes.

STRICTLY NON-PRODUCTION: copies files to /home/codex, dry-runs focused
patches without fuzzy matching; never touches live .git, source, .env or DB.
Manual review and backup still required before any deployment.
"""
from __future__ import annotations
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

SOURCE = Path(__file__).resolve().parents[1]
LIVE = Path("/home/users/hunthub/www/hnt.rocks")
STAGE = Path("/home/codex/hnt-ai-community-overlay-20261013")
FEATURE = "feature/ai-community-characters-20261012"


def run(args: list[str], *, cwd: Path | None = None, input_data: bytes | None = None):
    return subprocess.run(args, cwd=cwd, input=input_data, stdout=subprocess.PIPE,
                          stderr=subprocess.PIPE, timeout=35)


def sha(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def main() -> int:
    branch = run(["git", "-C", str(SOURCE), "branch", "--show-current"])
    if branch.returncode or branch.stdout.strip().decode() != FEATURE:
        raise RuntimeError("STOP: wrong source branch")
    if STAGE.exists():
        raise RuntimeError("STOP: isolated overlay already exists; never overwrite")
    diff = run(["git", "-C", str(SOURCE), "diff", "--name-status",
                "origin/main", "HEAD"])
    if diff.returncode:
        raise RuntimeError("STOP: missing source diff")
    changes = []
    for entry in diff.stdout.decode().splitlines():
        action, *paths = entry.split("\t")
        if len(paths) != 1:
            continue
        rel = paths[0]
        if not rel.startswith(("app/", "config/", "database/", "resources/", "routes/")):
            continue
        if action not in ("A", "M"):
            raise RuntimeError("STOP: unanticipated source action")
        changes.append((action, rel))
    STAGE.mkdir(mode=0o700)
    report = []
    for action, rel in changes:
        target = STAGE / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        live_file = LIVE / rel
        source_file = SOURCE / rel
        if not source_file.is_file() or source_file.is_symlink():
            raise RuntimeError("STOP: source path missing or linked: "+rel)
        if action == "A":
            if live_file.exists():
                report.append({"path":rel, "result":"COLLISION", "deployed_sha256":sha(live_file.read_bytes())})
                continue
            shutil.copyfile(source_file, target)
            report.append({"path":rel, "result":"STAGED_NEW", "staged_sha256":sha(target.read_bytes())})
            continue
        if not live_file.is_file() or live_file.is_symlink():
            report.append({"path":rel,"result":"UNREADABLE_OR_MISSING_LIVE"})
            continue
        original = live_file.read_bytes()
        target.write_bytes(original)
        patch = run(["git","-C",str(SOURCE),"diff","--binary","origin/main","HEAD","--",rel])
        if patch.returncode or not patch.stdout:
            report.append({"path":rel,"result":"SOURCE_PATCH_FAILED"})
            continue
        dry = run(["patch","--batch","--forward","--fuzz=0","--dry-run","-p1"],
                  cwd=STAGE,input_data=patch.stdout)
        if dry.returncode:
            report.append({"path":rel,"result":"CONFLICT_REQUIRES_MANUAL_MERGE",
                           "deployed_sha256":sha(original)})
            continue
        apply = run(["patch","--batch","--forward","--fuzz=0","-p1"],
                    cwd=STAGE,input_data=patch.stdout)
        if apply.returncode:
            raise RuntimeError("STOP: dry run and actual staged patch disagree for "+rel)
        report.append({"path":rel,"result":"STAGED_HUNK_MERGE",
                       "deployed_sha256":sha(original),"staged_sha256":sha(target.read_bytes())})
    (STAGE/"overlay-inventory.json").write_text(json.dumps({
        "source_branch": FEATURE,
        "source_revision": run(["git","-C",str(SOURCE),"rev-parse","HEAD"]).stdout.decode().strip(),
        "production_writes":False,"database_writes":False,
        "files":report
    },indent=2)+"\n",encoding="utf-8")
    print("HNT_AI_COMMUNITY_ISOLATED_OVERLAY_CREATED")
    print("STAGE",STAGE)
    for r in report:
        print(r["result"],r["path"])
    print("SUMMARY", {x: sum(r["result"]==x for r in report) for x in sorted({r["result"] for r in report})})
    print("NO_PRODUCTION_CHANGES / REVIEW_AND_BACKUP_REQUIRED")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
