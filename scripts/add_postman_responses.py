#!/usr/bin/env python3
"""Phase 19.2: Add saved example responses (success + failure) to every endpoint.

For each request in the collection, generate a `response` array with the
common cases inferred from the request itself:
  - 200/201 success
  - 401 unauthenticated  (added unless auth is explicitly noauth)
  - 403 forbidden        (admin/v1 + club/v1)
  - 404 not found        (when path has {id}, {slug}, etc.)
  - 422 validation       (when method has body)
  - 429 rate-limited     (always)
  - 500 server error     (always)
"""

import json
import re
import shutil
from datetime import datetime
from pathlib import Path

COLLECTION_PATH = Path("docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json")


def has_path_param(url: dict | str) -> bool:
    raw = url.get("raw", "") if isinstance(url, dict) else str(url)
    return bool(re.search(r"\{\{[a-zA-Z_]+_id\}\}|\{\{[a-zA-Z_]+_slug\}\}|/\{[a-zA-Z]+\}", raw))


def is_admin(url: dict) -> bool:
    return "admin/v1" in url.get("raw", "")


def is_club(url: dict) -> bool:
    return "club/v1" in url.get("raw", "")


def is_noauth(req: dict) -> bool:
    auth = req.get("auth")
    return isinstance(auth, dict) and auth.get("type") == "noauth"


def has_body(req: dict) -> bool:
    return req.get("method", "GET").upper() in {"POST", "PUT", "PATCH"} and bool(req.get("body"))


def make_response(name: str, status: int, code_text: str, payload: dict, original: dict) -> dict:
    """Build a Postman saved-response example."""
    method = original.get("method", "GET").upper()
    url = original.get("url", {})
    raw_url = url.get("raw", "") if isinstance(url, dict) else str(url)

    return {
        "name": name,
        "originalRequest": {
            "method": method,
            "header": original.get("header", []),
            "body": original.get("body"),
            "url": url,
        },
        "status": code_text,
        "code": status,
        "_postman_previewlanguage": "json",
        "header": [{"key": "Content-Type", "value": "application/json"}],
        "cookie": [],
        "body": json.dumps(payload, indent=2, ensure_ascii=False),
    }


def build_responses_for(item: dict) -> list[dict]:
    """Generate the saved-response examples for one endpoint."""
    req = item.get("request", {})
    method = req.get("method", "GET").upper()
    url = req.get("url", {}) if isinstance(req.get("url"), dict) else {"raw": str(req.get("url", ""))}

    responses: list[dict] = []

    # Success
    if method == "POST":
        responses.append(make_response(
            "✅ 201 Created",
            201,
            "Created",
            {"success": True, "message": "Created successfully", "data": {"id": 1}},
            req,
        ))
    elif method == "DELETE":
        responses.append(make_response(
            "✅ 200 OK (deleted)",
            200,
            "OK",
            {"success": True, "message": "Deleted successfully", "data": []},
            req,
        ))
    else:
        responses.append(make_response(
            "✅ 200 OK",
            200,
            "OK",
            {"success": True, "message": None, "data": {}},
            req,
        ))

    # 401 unless explicitly noauth
    if not is_noauth(req):
        responses.append(make_response(
            "❌ 401 Unauthenticated",
            401,
            "Unauthorized",
            {"success": False, "message": "Unauthenticated", "errors": None},
            req,
        ))

    # 403 for admin/club routes
    if is_admin(url):
        responses.append(make_response(
            "❌ 403 Forbidden (admin role required)",
            403,
            "Forbidden",
            {"success": False, "message": "User does not have the right roles.", "errors": None},
            req,
        ))
    elif is_club(url):
        responses.append(make_response(
            "❌ 403 Forbidden (club access required)",
            403,
            "Forbidden",
            {
                "success": False,
                "message": "Unauthorized. Club access required.",
                "errors": None,
            },
            req,
        ))

    # 404 if path has an id/slug
    if has_path_param(url):
        responses.append(make_response(
            "❌ 404 Not Found",
            404,
            "Not Found",
            {"success": False, "message": "Resource not found", "errors": None},
            req,
        ))

    # 422 if request has a body
    if has_body(req):
        responses.append(make_response(
            "❌ 422 Validation Failed",
            422,
            "Unprocessable Entity",
            {
                "success": False,
                "message": "The given data was invalid.",
                "errors": {
                    "field_name": ["The field name is required."],
                },
            },
            req,
        ))

    # 429 rate-limited
    responses.append(make_response(
        "❌ 429 Too Many Requests",
        429,
        "Too Many Requests",
        {
            "success": False,
            "message": "Too Many Attempts.",
            "errors": None,
        },
        req,
    ))

    # 500 server error
    responses.append(make_response(
        "❌ 500 Server Error",
        500,
        "Internal Server Error",
        {"success": False, "message": "Server error", "errors": None},
        req,
    ))

    return responses


def walk(items: list[dict], stats: dict) -> None:
    for item in items:
        if "item" in item:
            walk(item["item"], stats)
        elif "request" in item:
            stats["total"] += 1
            existing = item.get("response") or []
            if existing:
                stats["skipped_with_existing"] += 1
                continue
            item["response"] = build_responses_for(item)
            stats["updated"] += 1


def main() -> None:
    print(f"📂 Reading {COLLECTION_PATH}")
    with open(COLLECTION_PATH, encoding="utf-8") as f:
        collection = json.load(f)

    backup = COLLECTION_PATH.parent / (
        COLLECTION_PATH.stem + f".backup_responses_{datetime.now():%Y%m%d_%H%M%S}.json"
    )
    shutil.copy(COLLECTION_PATH, backup)
    print(f"✅ Backup: {backup.name}")

    stats = {"total": 0, "updated": 0, "skipped_with_existing": 0}
    walk(collection["item"], stats)

    with open(COLLECTION_PATH, "w", encoding="utf-8") as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    total_examples = sum(
        len(item.get("response", []))
        for item in _iter_requests(collection["item"])
    )

    print(
        f"✅ Done.\n"
        f"   Endpoints scanned   : {stats['total']}\n"
        f"   Endpoints updated   : {stats['updated']}\n"
        f"   Skipped (had examples): {stats['skipped_with_existing']}\n"
        f"   Total saved examples: {total_examples}"
    )


def _iter_requests(items):
    for it in items:
        if "request" in it:
            yield it
        if "item" in it:
            yield from _iter_requests(it["item"])


if __name__ == "__main__":
    main()
