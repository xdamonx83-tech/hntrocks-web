#!/usr/bin/env python3
"""Offline blind editorial benchmark. No API, Laravel bootstrap, key, or DB access.

Usage:
  python3 scripts/ai_community_blind_benchmark.py sheet PRIVATE_GENERATION.json --out BLIND_LABELS.csv
  # Have a human edit ONLY human_label/human_reason; do NOT show model reviews yet.
  python3 scripts/ai_community_blind_benchmark.py score BLIND_LABELS.csv PRIVATE_REVIEW.json --gate

Human labels are editorial judgments, not game-truth certificates. Samples and
labels are deliberately separate from any AI review.
"""
from __future__ import annotations
import argparse
import csv
import hashlib
import json
import sys
from collections import Counter
from pathlib import Path

DECISIONS = {"pass", "revise", "reject"}
COLUMNS = ["id", "language", "post_kind", "style_key", "body_sha256",
           "body", "human_label", "human_reason"]


def text_hash(value: str) -> str:
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


def load_preview(path: Path) -> list[dict]:
    data = json.loads(path.read_text(encoding="utf-8"))
    records = data.get("posts") if isinstance(data, dict) else None
    if not isinstance(records, list) or len(records) != 20:
        raise ValueError("Expected exactly 20 private, new-generation posts")
    seen = set()
    for rec in records:
        if not isinstance(rec, dict) or not all(rec.get(k) for k in
            ("id", "language", "post_kind", "style_key", "body")):
            raise ValueError("Missing preview metadata")
        if rec["id"] in seen or rec["language"] not in ("de", "en"):
            raise ValueError("Duplicate post ID or invalid language")
        seen.add(rec["id"])
        if not isinstance(rec["body"], str) or len(rec["body"]) > 700:
            raise ValueError("Invalid preview body")
    if sum(x["language"] == "de" for x in records) != 10:
        raise ValueError("Expected exactly 10 DE and 10 EN")
    return records


def write_sheet(preview: Path, target: Path) -> None:
    rows = load_preview(preview)
    # Do not overwrite labels: a person's independent scores are irreplaceable.
    with target.open("x", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=COLUMNS)
        writer.writeheader()
        for row in rows:
            writer.writerow({
                "id": row["id"], "language": row["language"], "post_kind": row["post_kind"],
                "style_key": row["style_key"], "body_sha256": text_hash(row["body"]),
                "body": row["body"], "human_label": "", "human_reason": ""
            })
    print(f"BLIND_LABEL_SHEET_CREATED {target} POSTS {len(rows)}")
    print("Human: fill human_label as pass/revise/reject BEFORE seeing AI reviews.")


def evaluate(labels_csv: Path, reviews_json: Path) -> dict:
    with labels_csv.open("r", encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        if reader.fieldnames != COLUMNS:
            raise ValueError("Blind score-sheet header changed")
        labels = list(reader)
    if len(labels) != 20 or len(set(x["id"] for x in labels)) != 20:
        raise ValueError("Expected 20 unique human labels")
    for row in labels:
        if row["human_label"] not in DECISIONS:
            raise ValueError("Missing/invalid human label on " + row["id"])
        if text_hash(row["body"]) != row["body_sha256"]:
            raise ValueError("Body edited after human labeling on " + row["id"])

    data = json.loads(reviews_json.read_text(encoding="utf-8"))
    reviews = data.get("reviews") if isinstance(data, dict) else None
    if not isinstance(reviews, list) or len(reviews) != 20:
        raise ValueError("Expected 20 private model reviews")
    seen: dict[str, dict] = {}
    for review in reviews:
        if not isinstance(review, dict) or review.get("decision") not in DECISIONS:
            raise ValueError("Missing or invalid review")
        id_ = review.get("id")
        if not isinstance(id_, str) or id_ in seen:
            raise ValueError("Duplicate or invalid model review ID")
        if review.get("body_sha256") is None:
            raise ValueError("Unverifiable reviewer text identity")
        seen[id_] = review

    counts = Counter()
    details = []
    for row in labels:
        pred = seen.get(row["id"])
        if not pred or pred["body_sha256"] != row["body_sha256"]:
            raise ValueError("Review/label mismatch or different text: " + row["id"])
        expected, actual = row["human_label"], pred["decision"]
        counts["count"] += 1
        counts[f"human_{expected}"] += 1
        counts[f"verdict_{actual}"] += 1
        if expected == actual:
            counts["exact"] += 1
        if actual == "pass":
            counts["model_pass"] += 1
            if expected == "pass":
                counts["correct_pass"] += 1
            else:
                counts["false_pass"] += 1
                if expected == "reject":
                    counts["rejected_but_passed"] += 1
        if expected != actual:
            details.append({"id": row["id"], "human": expected, "model": actual,
                            "reason": row["human_reason"][:160]})
    if set(seen) != {x["id"] for x in labels}:
        raise ValueError("Model did not review exact human-labeled sample")
    precision = counts["correct_pass"] / counts["model_pass"] if counts["model_pass"] else 0
    report = {
        "n": 20, "human_pass": counts["human_pass"],
        "model_pass": counts["model_pass"], "correct_pass": counts["correct_pass"],
        "false_pass": counts["false_pass"],
        "human_reject_model_pass": counts["rejected_but_passed"],
        "pass_precision": round(precision, 4),
        "exact_agreement": counts["exact"], "disagreements": details
    }
    return report


def main() -> int:
    parser = argparse.ArgumentParser(description="Blind private AI community quality evaluation, offline only")
    sub = parser.add_subparsers(dest="command", required=True)
    sheet = sub.add_parser("sheet")
    sheet.add_argument("preview", type=Path)
    sheet.add_argument("--out", type=Path, required=True)
    score = sub.add_parser("score")
    score.add_argument("labels", type=Path)
    score.add_argument("reviews", type=Path)
    score.add_argument("--gate", action="store_true")
    args = parser.parse_args()
    try:
        if args.command == "sheet":
            write_sheet(args.preview, args.out)
            return 0
        report = evaluate(args.labels, args.reviews)
    except (ValueError, OSError, KeyError, json.JSONDecodeError) as exc:
        print("FAIL_CLOSED:", str(exc), file=sys.stderr)
        return 2
    print("HNT_AI_BLIND_REVIEW_OFFLINE NO_API NO_DATABASE")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    accepted = report["model_pass"] > 0 and report["pass_precision"] >= .80 and report["human_reject_model_pass"] == 0
    print("CALIBRATION_GATE", "PASS" if accepted else "FAIL")
    print("NOTE: Human manual publication approval is always separately required.")
    return 0 if accepted or not args.gate else 3


if __name__ == "__main__":
    sys.exit(main())
