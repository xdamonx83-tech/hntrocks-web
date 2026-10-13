#!/usr/bin/env python3
"""Read-only SHA256 and syntax validation of separately staged LIVE-aware overlay.

Checks exact current live preimages against recorded hashes to refuse stale
deployment, and never accesses DB, keys, PHP app bootstrap or production writes.
"""
from __future__ import annotations

import hashlib
import json
from pathlib import Path
import shutil
import subprocess

LIVE=Path("/home/users/hunthub/www/hnt.rocks")
STAGE=Path("/home/codex/hnt-ai-community-overlay-20261013")
ALLOWED={"STAGED_NEW","STAGED_HUNK_MERGE","MANUAL_SCOPED_MERGE","SKIPPED_AUTOMATIC_SCHEDULER"}


def sha(path:Path)->str:return hashlib.sha256(path.read_bytes()).hexdigest()

def main()->int:
    inventory=json.loads((STAGE/"overlay-inventory.json").read_text("utf-8"))
    statuses={}
    files=inventory["files"]
    if len(files)!=27:raise RuntimeError("STOP: expected exactly 27 overlay paths")
    for row in files:
        rel=row["path"]
        target=STAGE/rel
        live=LIVE/rel
        status=row["result"]
        if status not in ALLOWED or not target.is_file():
            raise RuntimeError("STOP: incomplete candidate overlay "+rel)
        if sha(target)!=row["staged_sha256"]:
            raise RuntimeError("STOP: stage file hash mismatch "+rel)
        if status=="STAGED_NEW":
            if live.exists():
                raise RuntimeError("STOP: new path collision "+rel)
        elif sha(live)!=row["deployed_sha256"]:
            raise RuntimeError("STOP: live files changed since staged merge: "+rel)
        statuses[status]=statuses.get(status,0)+1
        if target.suffix==".php":
            lint=subprocess.run(["php","-l",str(target)],capture_output=True,text=True,timeout=10)
            if lint.returncode:
                raise RuntimeError("STOP: PHP lint failed "+rel+": "+lint.stderr[:100])

    routes=(STAGE/"routes/web.php").read_text()
    console=(STAGE/"routes/console.php").read_text()
    if routes.count("Route::prefix('admin')->middleware('hnt.admin')")!=1:
        raise RuntimeError("STOP: lost live admin middleware")
    if routes.count("AdminAiCommunityController::class")!=9:
        raise RuntimeError("STOP: unexpected number of AI routes")
    if "Schedule::command('ai-community" in console:
        raise RuntimeError("STOP: autonomous schedule unexpectedly included")
    if not (LIVE/".env").is_file():
        raise RuntimeError("STOP: missing production app settings")
    if not shutil.which("mariadb-dump"):
        raise RuntimeError("STOP: no database backup program")
    if shutil.disk_usage(str(STAGE)).free < 2*1024**3:
        raise RuntimeError("STOP: less than 2GiB backup headroom")
    print("HNT_AI_OVERLAY_PREFLIGHT_VERIFIED")
    print("FILE_COUNT",len(files),"BY_STATE",statuses)
    print("LINT_ALL_PHP_OK")
    print("LIVE_SHA256_UNCHANGED")
    print("ADMIN_HNT_ADMIN_MIDDLEWARE_PRESENT")
    print("NO_SCHEDULER_PRESENT")
    print("NO_DB_OR_PRODUCTION_WRITES")
    print("NEXT_REQUIRED: ROOT_DB_SCHEMA_CHECK_AND_VERIFIED_BACKUP")
    return 0

if __name__=="__main__":
    raise SystemExit(main())
