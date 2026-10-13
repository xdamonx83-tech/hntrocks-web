"""Offline tests for 20-post blind evaluation (no real OpenAI/DB)."""
import importlib.util
import json
import tempfile
import unittest
from pathlib import Path

PATH = Path(__file__).resolve().parents[2] / "scripts" / "ai_community_blind_benchmark.py"
SPEC = importlib.util.spec_from_file_location("blind_eval", PATH)
mod = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(mod)


def fake_preview():
    return {"posts": [
        {
            "id": f"V8-{i:02}", "language": "de" if i < 11 else "en",
            "post_kind": "open_question", "style_key": "headline_question",
            "body": f"Which Hunter design is best? Example {i}?"
        } for i in range(1, 21)
    ]}


class BlindBenchmarkTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.dir = Path(self.temp.name)
        self.preview = self.dir / "private.json"
        self.sheet = self.dir / "labels.csv"
        self.reviews = self.dir / "reviews.json"
        self.preview.write_text(json.dumps(fake_preview()), encoding="utf-8")
        mod.write_sheet(self.preview, self.sheet)

    def test_empty_human_labels_never_score(self):
        self.reviews.write_text(json.dumps({"reviews": []}), encoding="utf-8")
        with self.assertRaises(ValueError):
            mod.evaluate(self.sheet, self.reviews)

    def test_sheet_cannot_overwrite_human_work(self):
        with self.assertRaises(FileExistsError):
            mod.write_sheet(self.preview, self.sheet)

    def test_full_matching_reviews_and_scores(self):
        import csv
        with self.sheet.open(newline="", encoding="utf-8") as stream:
            rows = list(csv.DictReader(stream))
        for i, r in enumerate(rows):
            r["human_label"] = "pass" if i < 5 else "reject"
        with self.sheet.open("w", newline="", encoding="utf-8") as stream:
            wr = csv.DictWriter(stream, fieldnames=mod.COLUMNS)
            wr.writeheader()
            wr.writerows(rows)
        reviews = [{"id": r["id"], "decision": "pass" if i < 8 else "reject",
                    "body_sha256": r["body_sha256"]} for i, r in enumerate(rows)]
        self.reviews.write_text(json.dumps({"reviews": reviews}), encoding="utf-8")
        result = mod.evaluate(self.sheet, self.reviews)
        self.assertEqual(result["n"], 20)
        self.assertEqual(result["model_pass"], 8)
        self.assertEqual(result["correct_pass"], 5)
        self.assertEqual(result["false_pass"], 3)
        self.assertEqual(result["human_reject_model_pass"], 3)

    def test_review_must_match_body_identity(self):
        import csv
        with self.sheet.open(newline="", encoding="utf-8") as stream:
            rows = list(csv.DictReader(stream))
        for r in rows:
            r["human_label"] = "pass"
        with self.sheet.open("w", newline="", encoding="utf-8") as stream:
            wr = csv.DictWriter(stream, fieldnames=mod.COLUMNS)
            wr.writeheader()
            wr.writerows(rows)
        reviews = [{"id": r["id"], "decision": "pass",
                    "body_sha256": "0" * 64} for r in rows]
        self.reviews.write_text(json.dumps({"reviews": reviews}), encoding="utf-8")
        with self.assertRaisesRegex(ValueError, "mismatch"):
            mod.evaluate(self.sheet, self.reviews)

    def test_no_duplicate_ids(self):
        items = fake_preview()
        items["posts"][1]["id"] = items["posts"][0]["id"]
        self.preview.write_text(json.dumps(items), encoding="utf-8")
        with self.assertRaises(ValueError):
            mod.load_preview(self.preview)


if __name__ == "__main__":
    unittest.main()
