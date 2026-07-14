#!/usr/bin/env python3
"""Small localhost-only translation service for HNT.ROCKS.

It exposes:
- GET /health
- POST /translate {"q": "...", "source": "de", "target": "en"}

The process uses installed Argos Translate packages and never contacts an
external API while translating.
"""

from __future__ import annotations

import json
import logging
import os
import threading
import time
from http import HTTPStatus
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Any

import argostranslate.translate

HOST = os.getenv("HNT_TRANSLATOR_HOST", "127.0.0.1")
PORT = int(os.getenv("HNT_TRANSLATOR_PORT", "8787"))
API_TOKEN = os.getenv("HNT_TRANSLATOR_TOKEN", "").strip()
MAX_CHARS = max(100, min(50000, int(os.getenv("HNT_TRANSLATOR_MAX_CHARS", "12000"))))
SUPPORTED = {"de", "en"}

logging.basicConfig(
    level=os.getenv("HNT_TRANSLATOR_LOG_LEVEL", "INFO").upper(),
    format="%(asctime)s %(levelname)s %(message)s",
)
LOGGER = logging.getLogger("hnt-local-translator")
TRANSLATE_LOCK = threading.Lock()


def _installed_pair(source: str, target: str):
    languages = argostranslate.translate.get_installed_languages()
    source_language = next((language for language in languages if language.code == source), None)
    target_language = next((language for language in languages if language.code == target), None)

    if source_language is None or target_language is None:
        return None

    try:
        return source_language.get_translation(target_language)
    except Exception:
        return None


TRANSLATORS = {
    ("de", "en"): _installed_pair("de", "en"),
    ("en", "de"): _installed_pair("en", "de"),
}


def _ready_pairs() -> list[str]:
    return [f"{source}-{target}" for (source, target), translator in TRANSLATORS.items() if translator is not None]


class Handler(BaseHTTPRequestHandler):
    server_version = "HNTLocalTranslator/1.0"

    def log_message(self, fmt: str, *args: Any) -> None:
        LOGGER.info("%s - %s", self.address_string(), fmt % args)

    def _json(self, status: HTTPStatus, payload: dict[str, Any]) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status.value)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "no-store")
        self.end_headers()
        self.wfile.write(body)

    def _authorized(self) -> bool:
        if API_TOKEN == "":
            return True

        return self.headers.get("X-HNT-Translator-Token", "") == API_TOKEN

    def do_GET(self) -> None:
        if self.path.rstrip("/") != "/health":
            self._json(HTTPStatus.NOT_FOUND, {"ok": False, "error": "not_found"})
            return

        pairs = _ready_pairs()
        ready = len(pairs) == 2
        self._json(
            HTTPStatus.OK if ready else HTTPStatus.SERVICE_UNAVAILABLE,
            {
                "ok": ready,
                "provider": "local_argos",
                "pairs": pairs,
            },
        )

    def do_POST(self) -> None:
        if self.path.rstrip("/") != "/translate":
            self._json(HTTPStatus.NOT_FOUND, {"ok": False, "error": "not_found"})
            return

        if not self._authorized():
            self._json(HTTPStatus.UNAUTHORIZED, {"ok": False, "error": "unauthorized"})
            return

        try:
            content_length = int(self.headers.get("Content-Length", "0"))
        except ValueError:
            content_length = 0

        if content_length <= 0 or content_length > 262144:
            self._json(HTTPStatus.BAD_REQUEST, {"ok": False, "error": "invalid_content_length"})
            return

        try:
            payload = json.loads(self.rfile.read(content_length).decode("utf-8"))
        except (UnicodeDecodeError, json.JSONDecodeError):
            self._json(HTTPStatus.BAD_REQUEST, {"ok": False, "error": "invalid_json"})
            return

        text = str(payload.get("q", "")).strip()
        source = str(payload.get("source", "")).lower()[:2]
        target = str(payload.get("target", "")).lower()[:2]

        if text == "":
            self._json(HTTPStatus.UNPROCESSABLE_ENTITY, {"ok": False, "error": "empty_text"})
            return

        if len(text) > MAX_CHARS:
            self._json(HTTPStatus.UNPROCESSABLE_ENTITY, {"ok": False, "error": "text_too_long"})
            return

        if source not in SUPPORTED or target not in SUPPORTED or source == target:
            self._json(HTTPStatus.UNPROCESSABLE_ENTITY, {"ok": False, "error": "unsupported_direction"})
            return

        translator = TRANSLATORS.get((source, target))
        if translator is None:
            self._json(HTTPStatus.SERVICE_UNAVAILABLE, {"ok": False, "error": "model_not_installed"})
            return

        started = time.perf_counter()

        try:
            with TRANSLATE_LOCK:
                translated = translator.translate(text).strip()
        except Exception as exc:
            LOGGER.exception("Translation failed: %s", exc)
            self._json(HTTPStatus.INTERNAL_SERVER_ERROR, {"ok": False, "error": "translation_failed"})
            return

        if translated == "":
            self._json(HTTPStatus.INTERNAL_SERVER_ERROR, {"ok": False, "error": "empty_translation"})
            return

        self._json(
            HTTPStatus.OK,
            {
                "ok": True,
                "translatedText": translated,
                "provider": "local_argos",
                "source": source,
                "target": target,
                "elapsed_ms": round((time.perf_counter() - started) * 1000, 1),
            },
        )


def main() -> None:
    missing = [pair for pair, translator in TRANSLATORS.items() if translator is None]
    if missing:
        formatted = ", ".join(f"{source}->{target}" for source, target in missing)
        raise SystemExit(f"Missing Argos translation packages: {formatted}. Run install_models.py first.")

    server = ThreadingHTTPServer((HOST, PORT), Handler)
    LOGGER.info("HNT local translator listening on http://%s:%s", HOST, PORT)
    LOGGER.info("Ready language pairs: %s", ", ".join(_ready_pairs()))

    try:
        server.serve_forever(poll_interval=0.5)
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == "__main__":
    main()
