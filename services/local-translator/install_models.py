#!/usr/bin/env python3
"""Install only the German/English Argos model packages needed by HNT.ROCKS."""

from __future__ import annotations

from pathlib import Path

import argostranslate.package
import argostranslate.translate

PAIRS = [("de", "en"), ("en", "de")]


def installed(source: str, target: str) -> bool:
    languages = argostranslate.translate.get_installed_languages()
    source_language = next((language for language in languages if language.code == source), None)
    target_language = next((language for language in languages if language.code == target), None)

    if source_language is None or target_language is None:
        return False

    try:
        translator = source_language.get_translation(target_language)
        return translator is not None
    except Exception:
        return False


def main() -> None:
    argostranslate.package.update_package_index()
    packages = argostranslate.package.get_available_packages()

    for source, target in PAIRS:
        if installed(source, target):
            print(f"already installed: {source}->{target}")
            continue

        package = next(
            (
                candidate
                for candidate in packages
                if candidate.from_code == source and candidate.to_code == target
            ),
            None,
        )

        if package is None:
            raise SystemExit(f"No Argos package found for {source}->{target}")

        print(f"downloading: {source}->{target}")
        downloaded = Path(package.download())
        try:
            argostranslate.package.install_from_path(downloaded)
        finally:
            downloaded.unlink(missing_ok=True)

        if not installed(source, target):
            raise SystemExit(f"Installation verification failed for {source}->{target}")

        print(f"installed: {source}->{target}")

    print("all HNT translation packages are ready")


if __name__ == "__main__":
    main()
