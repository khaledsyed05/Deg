#!/usr/bin/env python3
"""Capture real responses from the running API and inject them into the Postman collection.

Strategy:
  1. For every request in the collection, render a concrete URL by substituting
     Postman variables ({{base_url}}, {{venue_slug}}, etc.).
  2. Make the actual HTTP call with the right token (admin/club/user/none).
  3. Capture the live response body.
  4. Replace the corresponding 200/201 success example in the saved responses
     with the captured one.
  5. Optionally capture 401 (no token), 403 (wrong role), 422 (empty body).

Skips destructive verbs by default unless --include-destructive is set.
"""

from __future__ import annotations

import argparse
import json
import re
import shutil
import sys
import time
from datetime import datetime
from pathlib import Path

import requests

COLLECTION_PATH = Path("docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json")
TOKENS_PATH = Path("/tmp/tokens.json")
BASE_HOST = "http://localhost:8001/api"

# Resolve common Postman variables to live IDs/slugs.
# `base_url` is intentionally a placeholder — `render_url()` chooses /api or /api/v1
# based on the path itself.
VARS = {
    "base_url": BASE_HOST,
    "venue_slug": "al-jaish-stadium",
    "venue_id": "1",
    "booking_id": "1",
    "review_id": "1",
    "club_id": "1",
    "event_id": "1",
    "category_id": "1",
    "city_id": "1",
    "user_id": "1",
    "team_id": "1",
    "ticket_id": "1",
    "promo_code": "WELCOME10",
    "code": "WELCOME10",
    "iso2": "SY",
    "stateId": "1",
    "id": "1",
    "fixtureId": "1",
    "leagueId": "1",
    "teamId": "1",
    "league_code": "PL",
    "report_id": "1",
    "refund_request_id": "1",
    "phone_change_request_id": "1",
    "registration_id": "1",
    "device_id": "1",
    "session_id": "1",
    "banner_id": "1",
    "block_id": "1",
    "photo_id": "1",
    "update_id": "1",
    "settlement": "1",
    "club": "1",
    "venue": "al-jaish-stadium",
    "review": "1",
    "waitlist": "1",
    "category": "football-pitches",
    "payment": "1",
    "paymentId": "1",
    "instanceId": "1",
    "slug": "al-jaish-stadium",
    "userId": "121",
    "key": "wallet_topup",
    "externalId": "1",
    "fixture_id": "1",
    "match_external_id": "1",
    "wallet_topup_payment_id": "1",
}

# Verbs we'll skip by default (don't want side-effects on shared dev DB).
DESTRUCTIVE_VERBS = {"DELETE"}
# Endpoints whose POST is essentially read-only or has guarded validation we want examples of:
SAFE_POST_PATTERNS = (
    "auth/otp",
    "auth/refresh",
    "auth/google",
    "venues/compare",
    "search/saved",
    "share/booking",
    "feedback",
    "support/ticket",
    "support/faq",
    "promotions/qr-redeem",
    "promotions/applied",
    "/check-availability",
    "/calculate-price",
    "/respond",
    "/reply",
    "/take-action",
    "broadcast",
    "block-slot",
    "feature-flags",
    "maintenance",
    "report",
)


def render_url(raw: str) -> str:
    """Substitute Postman variables in a raw URL."""
    out = raw
    # 1) Resolve {{base_url}} first, picking /api or /api/v1 by what follows.
    if out.startswith("{{base_url}}"):
        tail = out[len("{{base_url}}"):]
        if tail.startswith("/admin/v1") or tail.startswith("/club/v1") or tail.startswith("/v1"):
            out = BASE_HOST + tail
        else:
            out = BASE_HOST + "/v1" + tail
    # 2) Substitute the remaining `{{var}}` placeholders.
    for key, val in VARS.items():
        if key == "base_url":
            continue
        out = out.replace("{{" + key + "}}", val)
    # 3) Anything still in `{name}` form gets replaced with "1".
    out = re.sub(r"\{(?!\{)([a-zA-Z_]+)\}", "1", out)
    return out


def auth_for(url: str) -> str | None:
    """Pick which token (admin / club / user) to use for a given URL."""
    if "/admin/v1/" in url:
        return "admin"
    if "/club/v1/" in url:
        return "club_manager"
    return "user"


def is_safe(method: str, raw_url: str) -> bool:
    method = method.upper()
    if method in {"GET", "HEAD"}:
        return True
    if method in DESTRUCTIVE_VERBS:
        return False
    if method == "POST":
        return any(pat in raw_url for pat in SAFE_POST_PATTERNS)
    if method == "PUT" or method == "PATCH":
        # Allow only safe-ish PUTs
        return any(pat in raw_url for pat in (
            "/maintenance", "settings", "language", "privacy",
            "phone-number/initiate", "phone-number/verify",
        ))
    return False


def parse_body(req: dict) -> dict | None:
    body = req.get("body") or {}
    if body.get("mode") != "raw":
        return None
    raw = body.get("raw", "")
    if not raw:
        return None
    try:
        return json.loads(raw)
    except Exception:
        return None


def make_request(req: dict, tokens: dict, want_status: str = "success") -> dict | None:
    """want_status: 'success' | 'unauth' | 'forbidden' | 'invalid'."""
    method = req.get("method", "GET").upper()
    url_obj = req.get("url", {})
    raw = url_obj.get("raw", "") if isinstance(url_obj, dict) else str(url_obj)
    if not raw:
        return None

    rendered = render_url(raw)

    # Skip the literal {{base_url}} guard:
    if rendered.startswith("{{"):
        return None

    role = auth_for(rendered)
    headers = {"Accept": "application/json"}
    body = None

    if want_status == "success":
        if role and tokens.get(role):
            headers["Authorization"] = f"Bearer {tokens[role]['token']}"
        body = parse_body(req)
        if body and method in ("POST", "PUT", "PATCH"):
            headers["Content-Type"] = "application/json"
    elif want_status == "unauth":
        body = None
    elif want_status == "forbidden":
        # Use plain user token on admin/club endpoints to trigger 403.
        if tokens.get("user"):
            headers["Authorization"] = f"Bearer {tokens['user']['token']}"
    elif want_status == "invalid":
        if role and tokens.get(role):
            headers["Authorization"] = f"Bearer {tokens[role]['token']}"
        headers["Content-Type"] = "application/json"
        body = {}

    try:
        resp = requests.request(
            method,
            rendered,
            headers=headers,
            json=body if body is not None else None,
            timeout=10,
        )
    except requests.RequestException as e:
        return {"error": str(e)}

    payload_text = resp.text
    payload_json = None
    try:
        payload_json = resp.json()
    except Exception:
        pass

    return {
        "status": resp.status_code,
        "headers": dict(resp.headers),
        "body": payload_text,
        "json": payload_json,
    }


def replace_response(
    item: dict,
    captured: dict,
    label: str,
):
    """Merge a live example into `item.response` without removing templated examples."""
    code = captured["status"]
    body_pretty = ""
    if captured.get("json") is not None:
        body_pretty = json.dumps(captured["json"], indent=2, ensure_ascii=False)
    else:
        body_pretty = captured.get("body", "") or ""

    new = {
        "name": label,
        "originalRequest": _strip_request(item.get("request", {})),
        "status": _status_text(code),
        "code": code,
        "_postman_previewlanguage": "json",
        "header": [{"key": "Content-Type", "value": "application/json"}],
        "cookie": [],
        "body": body_pretty,
    }

    responses = item.get("response", []) or []

    # Replace an existing live example with the same label (idempotent re-runs);
    # otherwise append. We never touch templated examples (those without "(live)").
    for i, ex in enumerate(responses):
        if ex.get("name") == label:
            responses[i] = new
            item["response"] = responses
            return

    responses.append(new)
    item["response"] = responses


def _strip_request(req: dict) -> dict:
    return {
        "method": req.get("method", "GET"),
        "header": req.get("header", []),
        "body": req.get("body"),
        "url": req.get("url"),
    }


def _status_text(code: int) -> str:
    return {
        200: "OK", 201: "Created", 204: "No Content",
        400: "Bad Request", 401: "Unauthorized", 402: "Payment Required",
        403: "Forbidden", 404: "Not Found", 405: "Method Not Allowed",
        409: "Conflict", 410: "Gone", 422: "Unprocessable Entity",
        429: "Too Many Requests", 500: "Internal Server Error",
    }.get(code, str(code))


def walk(items, tokens, args, stats):
    for item in items:
        if "item" in item:
            walk(item["item"], tokens, args, stats)
            continue
        if "request" not in item:
            continue

        req = item["request"]
        method = req.get("method", "GET").upper()
        url_obj = req.get("url", {})
        raw = url_obj.get("raw", "") if isinstance(url_obj, dict) else str(url_obj)

        if not is_safe(method, raw) and not args.include_destructive:
            stats["skipped_destructive"] += 1
            continue

        # 1) success
        captured = make_request(req, tokens, "success")
        stats["calls"] += 1
        if captured and captured.get("status"):
            label = f"✅ {captured['status']} (live)"
            replace_response(item, captured, label)
            if 200 <= captured["status"] < 300:
                stats["success"] += 1
            else:
                stats["non_2xx_success_call"] += 1

        # 2) unauth (only if endpoint has no `noauth` already)
        if args.capture_failures and not _is_noauth(req):
            captured = make_request(req, tokens, "unauth")
            if captured and captured.get("status") == 401:
                replace_response(item, captured, "❌ 401 Unauthenticated (live)")
                stats["unauth"] += 1

        # 3) forbidden for admin/club
        if args.capture_failures and ("/admin/v1/" in raw or "/club/v1/" in raw):
            captured = make_request(req, tokens, "forbidden")
            if captured and captured.get("status") in (403, 401):
                replace_response(item, captured, f"❌ {captured['status']} Forbidden (live)")
                stats["forbidden"] += 1

        # 4) invalid (POST/PUT only, empty body to trigger 422)
        if args.capture_failures and method in ("POST", "PUT", "PATCH") and parse_body(req):
            captured = make_request(req, tokens, "invalid")
            if captured and captured.get("status") == 422:
                replace_response(item, captured, "❌ 422 Validation Failed (live)")
                stats["invalid"] += 1

        time.sleep(args.delay)


def _is_noauth(req: dict) -> bool:
    auth = req.get("auth")
    return isinstance(auth, dict) and auth.get("type") == "noauth"


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--include-destructive", action="store_true",
                   help="Also call DELETE/destructive endpoints")
    p.add_argument("--capture-failures", action="store_true",
                   help="Also capture live 401/403/422 examples")
    p.add_argument("--delay", type=float, default=0.05,
                   help="Sleep between calls (s)")
    p.add_argument("--limit", type=int, default=0,
                   help="Stop after N calls (debug)")
    args = p.parse_args()

    if not TOKENS_PATH.exists():
        print(f"Missing {TOKENS_PATH} — seed users first.")
        sys.exit(1)

    tokens = json.loads(TOKENS_PATH.read_text())
    print(f"📂 Reading {COLLECTION_PATH}")
    collection = json.loads(COLLECTION_PATH.read_text(encoding="utf-8"))

    backup = COLLECTION_PATH.parent / (
        COLLECTION_PATH.stem + f".backup_live_{datetime.now():%Y%m%d_%H%M%S}.json"
    )
    shutil.copy(COLLECTION_PATH, backup)
    print(f"✅ Backup: {backup.name}")

    stats = {
        "calls": 0, "success": 0, "non_2xx_success_call": 0,
        "unauth": 0, "forbidden": 0, "invalid": 0,
        "skipped_destructive": 0,
    }

    try:
        walk(collection["item"], tokens, args, stats)
    finally:
        with open(COLLECTION_PATH, "w", encoding="utf-8") as f:
            json.dump(collection, f, indent=2, ensure_ascii=False)

    print(json.dumps(stats, indent=2))


if __name__ == "__main__":
    main()
