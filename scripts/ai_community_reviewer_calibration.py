#!/usr/bin/env python3
"""Offline calibration of AI editorial-review advice against a human-labelled sample.

Reads a PRIVATE JSON benchmark file passed by the operator. No Laravel bootstrap,
OpenAI calls, credential access, file writes, or database connection.

Labels below are explicitly provisional human editorial judgments, not factual
game truth. The samples overlap the prompt's calibration examples, so good
performance here is NOT sufficient: independent held-out review is still required.
"""
from __future__ import annotations

import argparse
import json
from collections import Counter
from pathlib import Path

# Labels follow the first manual V6 review: 2 immediately usable,
# 4 needing light edits, 4 not suitable. Names are synthetic personas.
# Never reinterpret a model's "pass" as actual publishing permission.
MANUAL_LABELS: dict[str, tuple[str, str]] = {
    "BayouBasti": ("pass", "Short natural question"),
    "Nebelkraehe": ("reject", "Expository 'Hunt-Audio-Signal' rather than a community comment"),
    "CashCowboy": ("revise", "Useful weapon comparison, slightly stiff"),
    "FallenFuchs": ("reject", "Over-formal UI feature request"),
    "Schrotkoenig": ("reject", "Synthetic shotgun metaphor and Bayou/Bucht translation"),
    "MudRunner": ("pass", "Direct and natural map preference question"),
    "CrowBait": ("reject", "Awkward English synthetic atmosphere description"),
    "OldVetterli": ("revise", "Plausible tool question, language needs polish"),
    "BayouMechanic": ("revise", "Understandable feature wish, can be shorter"),
    "LastExtract": ("revise", "Acceptable preference but polished phrasing"),
}
VALID = {"pass", "revise", "reject"}


def evaluate(records: list[dict]) -> dict:
    seen: set[str] = set()
    counts = Counter()
    mismatches: list[dict[str, str]] = []
    for r in records:
        if not isinstance(r, dict) or not isinstance(r.get("name"), str):
            raise ValueError("Missing reviewed persona")
        name = r["name"]
        if name not in MANUAL_LABELS or name in seen:
            raise ValueError("Unknown or duplicate benchmark persona: " + name)
        seen.add(name)
        predicted = r.get("decision")
        if predicted not in VALID:
            raise ValueError("Unexpected reviewer decision for " + name)
        expected, why = MANUAL_LABELS[name]
        counts["total"] += 1
        counts["verdict_" + predicted] += 1
        counts["manual_" + expected] += 1
        if predicted == expected:
            counts["exact_agreement"] += 1
        else:
            mismatches.append({"name": name, "model": predicted, "manual": expected, "human_note": why})
        if predicted == "pass":
            counts["model_pass"] += 1
            if expected == "pass":
                counts["safe_pass"] += 1
            else:
                counts["false_pass"] += 1
                if expected == "reject":
                    counts["human_rejected_but_model_pass"] += 1

    if seen != set(MANUAL_LABELS):
        raise ValueError("Incomplete comparison set, expected exactly ten cases")
    precision = counts["safe_pass"] / counts["model_pass"] if counts["model_pass"] else 0.0
    return {
        "cases": counts["total"],
        "human_ready": counts["manual_pass"],
        "model_pass": counts["model_pass"],
        "correct_model_pass": counts["safe_pass"],
        "false_pass": counts["false_pass"],
        "human_rejected_but_model_pass": counts["human_rejected_but_model_pass"],
        "pass_precision": round(precision, 4),
        "exact_agreement": counts["exact_agreement"],
        "mismatches": mismatches,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Offline human-vs-model editor calibration (no API/DB access)")
    parser.add_argument("results_json", type=Path, help="Private reviewer result JSON")
    parser.add_argument("--gate", action="store_true",
                        help="Exit nonzero unless model PASS precision >=80% and no human-rejected posts get PASS")
    args = parser.parse_args()
    data = json.loads(args.results_json.read_text(encoding="utf-8"))
    results = data.get("reviews") if isinstance(data, dict) else None
    if not isinstance(results, list):
        raise SystemExit("ERROR: Expected reviewer results JSON with 'reviews' list")
    try:
        report = evaluate(results)
    except ValueError as e:
        raise SystemExit("ERROR: " + str(e))
    print("HNT_AI_REVIEWER_CALIBRATION_OFFLINE")
    print("MANUAL_LABELS provisional/subjective; NO_API / NO_DATABASE / NO_FEED_POSTS")
    print(json.dumps(report, indent=2, ensure_ascii=False))
    good = report["model_pass"] > 0 and report["pass_precision"] >= 0.8 and report["human_rejected_but_model_pass"] == 0
    print("CALIBRATION_GATE", "PASS" if good else "FAIL")
    print("NOTE: Even PASS on this set needs an independent, unseen human-labelled test set.")
    return 0 if good or not args.gate else 3


if __name__ == "__main__":
    raise SystemExit(main())
