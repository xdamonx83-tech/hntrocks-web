"""Offline regression tests for provisional V6/V7 human-labelled calibration."""
import importlib.util
import unittest
from pathlib import Path

MODULE_PATH = Path(__file__).resolve().parents[2] / "scripts" / "ai_community_reviewer_calibration.py"
spec = importlib.util.spec_from_file_location("ai_reviewer_calibration", MODULE_PATH)
cal = importlib.util.module_from_spec(spec)
spec.loader.exec_module(cal)


class CalibrationTest(unittest.TestCase):
    def test_reconstructed_v7_false_positive_counts(self):
        rows = [
            {"name": name, "decision": "revise" if name == "CrowBait" else "pass"}
            for name in cal.MANUAL_LABELS
        ]
        report = cal.evaluate(rows)
        self.assertEqual(report["model_pass"], 9)
        self.assertEqual(report["correct_model_pass"], 2)
        self.assertEqual(report["false_pass"], 7)
        self.assertEqual(report["human_rejected_but_model_pass"], 3)
        self.assertEqual(report["pass_precision"], 0.2222)

    def test_exact_match_has_no_false_passes(self):
        rows = [{"name": name, "decision": decision} for name, (decision, _) in cal.MANUAL_LABELS.items()]
        report = cal.evaluate(rows)
        self.assertEqual(report["correct_model_pass"], 2)
        self.assertEqual(report["false_pass"], 0)
        self.assertEqual(report["exact_agreement"], 10)

    def test_missing_or_duplicate_persona_fails_closed(self):
        rows = [{"name": name, "decision": decision} for name, (decision, _) in cal.MANUAL_LABELS.items()]
        with self.assertRaisesRegex(ValueError, "Incomplete"):
            cal.evaluate(rows[:-1])
        with self.assertRaisesRegex(ValueError, "Unknown or duplicate"):
            cal.evaluate(rows + rows[:1])


if __name__ == "__main__":
    unittest.main()
