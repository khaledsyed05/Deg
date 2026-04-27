#!/usr/bin/env python3
"""Phase 19.1: Final Postman Collection Update — adds missing Phase 15-19 endpoints."""

import json
import shutil
from datetime import datetime
from pathlib import Path

COLLECTION_PATH = Path("docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json")


# ---------- helpers ----------

def _build_url(path: str) -> dict:
    parts = path.split("/")
    return {"raw": "{{base_url}}/" + path, "host": ["{{base_url}}"], "path": parts}


def simple_get(name, path, auth_optional=False):
    req = {"method": "GET", "header": [], "url": _build_url(path)}
    if auth_optional:
        req["auth"] = {"type": "noauth"}
    return {"name": name, "request": req}


def simple_get_with_query(name, path, query_params, auth_optional=False):
    url = _build_url(path)
    url["query"] = query_params
    raw_query = "&".join(f"{p['key']}={p['value']}" for p in query_params if p["value"])
    if raw_query:
        url["raw"] = f"{url['raw']}?{raw_query}"
    req = {"method": "GET", "header": [], "url": url}
    if auth_optional:
        req["auth"] = {"type": "noauth"}
    return {"name": name, "request": req}


def simple_post_json(name, path, body):
    return {
        "name": name,
        "request": {
            "method": "POST",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {"mode": "raw", "raw": json.dumps(body, indent=2, ensure_ascii=False)},
            "url": _build_url(path),
        },
    }


def simple_put_json(name, path, body):
    return {
        "name": name,
        "request": {
            "method": "PUT",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {"mode": "raw", "raw": json.dumps(body, indent=2, ensure_ascii=False)},
            "url": _build_url(path),
        },
    }


def simple_delete(name, path):
    return {"name": name, "request": {"method": "DELETE", "header": [], "url": _build_url(path)}}


def simple_delete_with_body(name, path, body):
    return {
        "name": name,
        "request": {
            "method": "DELETE",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {"mode": "raw", "raw": json.dumps(body, indent=2, ensure_ascii=False)},
            "url": _build_url(path),
        },
    }


def admin_get(name, path):
    return simple_get(name, f"admin/v1/{path}")


def admin_get_with_query(name, path, q):
    return simple_get_with_query(name, f"admin/v1/{path}", q)


def admin_post_json(name, path, body):
    return simple_post_json(name, f"admin/v1/{path}", body)


def admin_put_json(name, path, body):
    return simple_put_json(name, f"admin/v1/{path}", body)


def club_get(name, path):
    return simple_get(name, f"club/v1/{path}")


def club_get_with_query(name, path, q):
    return simple_get_with_query(name, f"club/v1/{path}", q)


def club_post_json(name, path, body):
    return simple_post_json(name, f"club/v1/{path}", body)


def club_put_json(name, path, body):
    return simple_put_json(name, f"club/v1/{path}", body)


def club_delete(name, path):
    return simple_delete(name, f"club/v1/{path}")


def club_post_form(name, path):
    return {
        "name": name,
        "request": {
            "method": "POST",
            "header": [],
            "body": {"mode": "formdata", "formdata": [{"key": "photos[]", "type": "file", "src": []}]},
            "url": _build_url(f"club/v1/{path}"),
        },
    }


def find_folder(items, name_pattern):
    pat = name_pattern.lower()
    for it in items:
        if "item" in it and pat in it.get("name", "").lower():
            return it
    return None


def add_to_folder(folder, endpoints):
    existing = {it.get("name", "").strip() for it in folder.get("item", [])}
    existing_clean = {n.split("✨")[0].strip() for n in existing}
    for ep in endpoints:
        name = ep.get("name", "").strip()
        clean = name.split("✨")[0].strip()
        if clean and clean not in existing_clean and name not in existing:
            folder["item"].append(ep)
            existing.add(name)
            existing_clean.add(clean)


def count_endpoints(items):
    n = 0
    for it in items:
        if "request" in it:
            n += 1
        if "item" in it:
            n += count_endpoints(it["item"])
    return n


# ---------- Phase 15 ----------

def update_phase_15(collection):
    booking = find_folder(collection["item"], "booking")
    if booking:
        add_to_folder(booking, [
            simple_put_json("Reschedule Booking ✨ NEW (Phase 15)",
                "bookings/{{booking_id}}/reschedule",
                {"new_slot_date": "2026-04-30", "new_start_time": "18:00",
                 "new_end_time": "20:00", "reason": "تعارض مع عمل"}),
            simple_post_json("Request Refund ✨ NEW (Phase 15)",
                "bookings/{{booking_id}}/refund",
                {"reason": "تعارض مع موعد طارئ", "refund_method": "wallet"}),
        ])
        print(f"  Booking: +2 (Phase 15)")

    support = find_folder(collection["item"], "support")
    if support:
        add_to_folder(support, [
            simple_get("Get Ticket Details ✨ NEW (Phase 15)", "support/tickets/{{ticket_id}}"),
            simple_post_json("Reply to Ticket ✨ NEW (Phase 15)",
                "support/tickets/{{ticket_id}}/reply",
                {"message": "حصلت المشكلة لما حاولت أدفع", "attachments": []}),
        ])
        print(f"  Support: +2 (Phase 15)")

    venues = find_folder(collection["item"], "venue")
    if venues:
        add_to_folder(venues, [
            simple_post_json("Report Venue ✨ NEW (Phase 15)",
                "venues/{{venue_slug}}/report",
                {"reason": "inappropriate_content",
                 "description": "الملعب مهجور", "evidence_urls": []}),
        ])
        print(f"  Venues: +1 (Phase 15)")

    reviews = find_folder(collection["item"], "review")
    if reviews:
        add_to_folder(reviews, [
            simple_post_json("Report Review ✨ NEW (Phase 15)",
                "reviews/{{review_id}}/report",
                {"reason": "offensive_language", "description": "ألفاظ مسيئة"}),
        ])
        print(f"  Reviews: +1 (Phase 15)")


# ---------- Phase 16 ----------

def add_phase_16_folders(collection):
    clubs = {
        "name": "15. Clubs Management 🏆",
        "description": "Browse, follow, and interact with sports clubs.",
        "item": [
            simple_get("Club Details", "clubs/{{club_id}}", auth_optional=True),
            simple_get("Club Venues", "clubs/{{club_id}}/venues", auth_optional=True),
            simple_get("Club Reviews", "clubs/{{club_id}}/reviews", auth_optional=True),
            simple_get("Club Contact Info", "clubs/{{club_id}}/contact", auth_optional=True),
            simple_get_with_query("Club Feed", "clubs/{{club_id}}/feed",
                [{"key": "type", "value": ""}], auth_optional=True),
            simple_post_json("Follow Club", "clubs/{{club_id}}/follow",
                {"notify_updates": True, "notify_events": True, "notify_promotions": True}),
            simple_delete("Unfollow Club", "clubs/{{club_id}}/follow"),
            simple_get("My Followed Clubs", "clubs/followed"),
        ],
    }
    events = {
        "name": "16. Events & Tournaments 🎯",
        "description": "Browse and register for tournaments, training, social events, etc.",
        "item": [
            simple_get_with_query("List Events", "events",
                [{"key": "status", "value": "open"}, {"key": "type", "value": ""},
                 {"key": "city_id", "value": ""}, {"key": "upcoming", "value": "true"}],
                auth_optional=True),
            simple_get("Event Details", "events/{{event_id}}", auth_optional=True),
            simple_post_json("Register for Event", "events/{{event_id}}/register",
                {"team_id": None,
                 "participant_info": {"age": 25, "skill_level": "intermediate"}}),
            simple_get("My Event Registrations", "events/registered"),
            simple_delete_with_body("Cancel Event Registration",
                "events/{{event_id}}/registration", {"reason": "تعارض مع موعد"}),
            simple_get("Event Participants", "events/{{event_id}}/participants", auth_optional=True),
            simple_get("Event Results", "events/{{event_id}}/results", auth_optional=True),
        ],
    }
    if not find_folder(collection["item"], "Clubs Management"):
        collection["item"].append(clubs)
    if not find_folder(collection["item"], "Events & Tournaments"):
        collection["item"].append(events)
    print(f"  Phase 16 folders added: Clubs (8) + Events (7)")


# ---------- Phase 17 ----------

def add_phase_17_folders(collection):
    content = {
        "name": "17. Content & Media 📰",
        "description": "Banners, blog articles, tips, videos, featured content.",
        "item": [
            simple_get_with_query("Get Banners", "content/banners",
                [{"key": "position", "value": "home_top"}], auth_optional=True),
            simple_get_with_query("Featured Content", "content/featured",
                [{"key": "section", "value": "home_featured"}], auth_optional=True),
            simple_get_with_query("Blog Articles", "content/blog",
                [{"key": "category", "value": ""}, {"key": "featured", "value": "false"}],
                auth_optional=True),
            simple_get_with_query("Tips & Guides", "content/tips",
                [{"key": "category", "value": ""}], auth_optional=True),
            simple_get_with_query("Videos", "content/videos",
                [{"key": "category", "value": ""}], auth_optional=True),
        ],
    }
    emergency = {
        "name": "18. Emergency & Safety 🚨",
        "description": "Emergency reporting, safety contacts, live location sharing.",
        "item": [
            simple_post_json("Report Emergency", "emergency/report",
                {"type": "medical", "severity": "high",
                 "description": "إصابة في الملعب",
                 "location": {"latitude": 33.5138, "longitude": 36.2765}}),
            simple_get_with_query("Emergency Contacts", "emergency/contacts",
                [{"key": "city_id", "value": ""}], auth_optional=True),
            simple_post_json("Share Live Location", "emergency/share-location",
                {"duration_minutes": 60,
                 "recipient_phones": ["+963944123456"],
                 "message_to_recipients": "أنا في طريقي إلى الملعب"}),
            simple_get("Safety Guide", "emergency/safety-guide", auth_optional=True),
        ],
    }
    if not find_folder(collection["item"], "Content & Media"):
        collection["item"].append(content)
    if not find_folder(collection["item"], "Emergency & Safety"):
        collection["item"].append(emergency)

    venues = find_folder(collection["item"], "venue")
    if venues:
        add_to_folder(venues, [
            simple_get_with_query("Popular Venues ✨ NEW (Phase 17)", "venues/popular",
                [{"key": "city_id", "value": ""}, {"key": "limit", "value": "20"}],
                auth_optional=True),
            simple_get_with_query("Recently Viewed ✨ NEW (Phase 17)", "venues/recently-viewed",
                [{"key": "limit", "value": "20"}]),
            simple_get("Similar Venues ✨ NEW (Phase 17)",
                "venues/{{venue_slug}}/similar", auth_optional=True),
            simple_get("Venue Photos Gallery ✨ NEW (Phase 17)",
                "venues/{{venue_slug}}/photos", auth_optional=True),
        ])
    print(f"  Phase 17 folders added: Content (5) + Emergency (4) + Venue Extras (4)")


# ---------- Phase 18 ----------

def update_phase_18(collection):
    auth = find_folder(collection["item"], "authentication")
    if auth:
        add_to_folder(auth, [
            simple_delete("Delete Avatar ✨ NEW (Phase 18)", "profile/avatar"),
            simple_put_json("Initiate Phone Change ✨ NEW (Phase 18)",
                "profile/phone-number/initiate",
                {"new_phone_number": "+963944999888"}),
            simple_put_json("Verify Phone Change ✨ NEW (Phase 18)",
                "profile/phone-number/verify",
                {"phone_change_request_id": "{{phone_change_request_id}}", "otp": "123456"}),
            simple_get("My Active Sessions ✨ NEW (Phase 18)", "auth/sessions"),
            simple_delete("Revoke Session ✨ NEW (Phase 18)", "auth/sessions/{{session_id}}"),
        ])
        print(f"  Auth: +5 (Phase 18)")

    notifs = find_folder(collection["item"], "notification")
    if notifs:
        add_to_folder(notifs, [
            simple_delete("Unregister Device ✨ NEW (Phase 18)", "devices/{{device_id}}"),
        ])
        print(f"  Notifications: +1 (Phase 18)")

    promos = find_folder(collection["item"], "promotion")
    if promos:
        add_to_folder(promos, [
            simple_post_json("QR Promotion Redeem ✨ NEW (Phase 18)", "promotions/qr-redeem",
                {"qr_code": "DAQ-PROMO-X8Y7Z6W5", "booking_id": "{{booking_id}}"}),
            simple_delete_with_body("Remove Applied Promo ✨ NEW (Phase 18)",
                "promotions/applied", {"booking_id": "{{booking_id}}"}),
        ])
        print(f"  Promotions: +2 (Phase 18)")

    reviews = find_folder(collection["item"], "review")
    if reviews:
        add_to_folder(reviews, [
            {
                "name": "Upload Review Photos ✨ NEW (Phase 18)",
                "request": {
                    "method": "POST",
                    "header": [],
                    "body": {"mode": "formdata",
                             "formdata": [{"key": "photos[]", "type": "file", "src": []}]},
                    "url": _build_url("reviews/{{review_id}}/photos"),
                },
            },
            simple_get("Review Guidelines ✨ NEW (Phase 18)", "reviews/guidelines",
                auth_optional=True),
        ])
        print(f"  Reviews: +2 (Phase 18)")

    booking = find_folder(collection["item"], "booking")
    if booking:
        add_to_folder(booking, [
            simple_post_json("Split Payment ✨ NEW (Phase 18)",
                "bookings/{{booking_id}}/split-payment",
                {"split_method": "equal",
                 "splits": [{"user_id": 5, "amount": 30}, {"user_id": 6, "amount": 30}]}),
        ])
        print(f"  Bookings: +1 (Phase 18 split)")

    teams = find_folder(collection["item"], "team") or find_folder(collection["item"], "group")
    if teams:
        add_to_folder(teams, [
            simple_put_json("Leave Team ✨ NEW (Phase 18)", "teams/{{team_id}}/leave",
                {"reason": "غادرت المدينة"}),
        ])
        print(f"  Teams: +1 (Phase 18)")


# ---------- Admin Dashboard ----------

def add_admin_folder(collection):
    if find_folder(collection["item"], "Admin Dashboard"):
        print("  Admin Dashboard already present, skipping")
        return

    folder = {
        "name": "30. Admin Dashboard 🛠️",
        "description": ("Admin-only endpoints for platform management.\n\n"
                        "**Required Role:** admin\n**Base:** {{base_url}}/admin/v1"),
        "item": [
            {"name": "30.1 Dashboard & Analytics", "item": [
                admin_get("Dashboard Stats", "dashboard/stats"),
                admin_get_with_query("Revenue Chart", "dashboard/revenue",
                    [{"key": "period", "value": "monthly"},
                     {"key": "from", "value": "2026-01-01"},
                     {"key": "to", "value": "2026-12-31"}]),
                admin_get_with_query("Bookings Trend", "dashboard/bookings-trend",
                    [{"key": "days", "value": "30"}]),
                admin_get("Top Venues", "dashboard/top-venues"),
                admin_get_with_query("User Growth", "dashboard/user-growth",
                    [{"key": "days", "value": "30"}]),
            ]},
            {"name": "30.2 User Management", "item": [
                admin_get_with_query("List Users", "users",
                    [{"key": "search", "value": ""}, {"key": "role", "value": ""},
                     {"key": "status", "value": ""}]),
                admin_get("User Details", "users/{{user_id}}"),
                admin_put_json("Update User", "users/{{user_id}}",
                    {"name": "Updated Name", "account_status": "active"}),
                admin_post_json("Ban User", "users/{{user_id}}/ban",
                    {"reason": "Multiple policy violations"}),
                admin_post_json("Unban User", "users/{{user_id}}/unban", {}),
            ]},
            {"name": "30.3 Venue Approval", "item": [
                admin_get("Pending Venues", "venues/pending-approval"),
                admin_post_json("Approve Venue", "venues/{{venue_id}}/approve", {}),
                admin_post_json("Reject Venue", "venues/{{venue_id}}/reject",
                    {"reason": "Photos don't match the actual venue"}),
                admin_put_json("Toggle Featured", "venues/{{venue_id}}/feature", {}),
            ]},
            {"name": "30.4 Refund Management", "item": [
                admin_get("Pending Refunds", "refunds/pending"),
                admin_post_json("Approve Refund", "refunds/{{refund_request_id}}/approve",
                    {"adjusted_amount": 30000}),
                admin_post_json("Reject Refund", "refunds/{{refund_request_id}}/reject",
                    {"reason": "Booking already used"}),
            ]},
            {"name": "30.5 Reports Moderation", "item": [
                admin_get_with_query("Venue Reports", "reports/venues",
                    [{"key": "status", "value": "pending"}]),
                admin_post_json("Action on Venue Report",
                    "reports/venues/{{report_id}}/take-action",
                    {"action": "hide_venue", "notes": "Verified complaints"}),
                admin_get_with_query("Review Reports", "reports/reviews",
                    [{"key": "status", "value": "pending"}]),
                admin_post_json("Action on Review Report",
                    "reports/reviews/{{report_id}}/take-action",
                    {"action": "delete_review", "notes": "Offensive content"}),
            ]},
            {"name": "30.6 Support Tickets", "item": [
                admin_get_with_query("List All Tickets", "tickets",
                    [{"key": "status", "value": ""}, {"key": "category", "value": ""}]),
                admin_post_json("Assign Ticket", "tickets/{{ticket_id}}/assign",
                    {"agent_id": 5}),
                admin_post_json("Reply to Ticket", "tickets/{{ticket_id}}/reply",
                    {"message": "شكراً لتواصلك. سنحلّ المشكلة قريباً.",
                     "close_ticket": False}),
            ]},
            {"name": "30.7 Content Management", "item": [
                admin_post_json("Create Banner", "banners",
                    {"title": "New offer", "title_ar": "عرض جديد",
                     "image_url": "https://placehold.co/1080x540/png",
                     "position": "home_top",
                     "starts_at": "2026-04-26", "ends_at": "2026-05-26"}),
                admin_put_json("Update Banner", "banners/{{banner_id}}",
                    {"is_active": True}),
                admin_post_json("Create Blog Article", "blog",
                    {"title": "Tips", "title_ar": "نصائح للاعبين",
                     "content": "...", "content_ar": "...",
                     "category": "tips", "is_published": True}),
                admin_post_json("Create Promotion", "promotions",
                    {"code": "SUMMER20",
                     "name": {"ar": "خصم 20%", "en": "20% off"},
                     "type": "percentage", "value": 20,
                     "is_qr_promotion": True, "qr_redemption_limit": 100,
                     "valid_to": "2026-06-30"}),
            ]},
            {"name": "30.8 Financial Reports", "item": [
                admin_get_with_query("Revenue Report", "reports/revenue",
                    [{"key": "from", "value": "2026-01-01"},
                     {"key": "to", "value": "2026-12-31"},
                     {"key": "venue_id", "value": ""},
                     {"key": "club_id", "value": ""}]),
                admin_get_with_query("Payments Report", "reports/payments",
                    [{"key": "provider", "value": ""}, {"key": "status", "value": ""}]),
                admin_get_with_query("Wallet Flow", "reports/wallet-flow",
                    [{"key": "from", "value": "2026-01-01"},
                     {"key": "to", "value": "2026-12-31"}]),
            ]},
            {"name": "30.9 System Management", "item": [
                admin_put_json("Toggle Maintenance Mode", "app/maintenance",
                    {"enabled": False, "message_ar": "صيانة مجدولة",
                     "ends_at": "2026-04-30T04:00:00Z"}),
                admin_post_json("Toggle Feature Flag", "app/feature-flags/wallet_topup",
                    {"enabled": True}),
                admin_post_json("Broadcast Notification", "notifications/broadcast",
                    {"title": "Update", "title_ar": "تحديث جديد",
                     "message": "New version available", "message_ar": "إصدار جديد متاح!",
                     "target_segment": "all_users"}),
                admin_get_with_query("Audit Log", "audit-log",
                    [{"key": "user_id", "value": ""}, {"key": "action", "value": ""}]),
            ]},
        ],
    }
    collection["item"].append(folder)
    print(f"  Admin Dashboard folder added: 9 sub-folders, 35 endpoints")


# ---------- Club Dashboard ----------

def add_club_folder(collection):
    if find_folder(collection["item"], "Club Dashboard"):
        print("  Club Dashboard already present, skipping")
        return

    folder = {
        "name": "31. Club Dashboard 🏟️",
        "description": ("Club admin/staff endpoints for managing club data.\n\n"
                        "**Required Role:** club_manager / club_admin / club_staff\n"
                        "**Middleware:** club.access (EnsureClubAccess)\n"
                        "**Base:** {{base_url}}/club/v1"),
        "item": [
            {"name": "31.1 Dashboard", "item": [
                club_get("Club Stats", "dashboard/stats"),
                club_get_with_query("Bookings Trend", "dashboard/bookings-trend",
                    [{"key": "days", "value": "30"}]),
                club_get("Top Venues", "dashboard/top-venues"),
                club_get_with_query("Recent Activity", "dashboard/recent-activity",
                    [{"key": "limit", "value": "20"}]),
            ]},
            {"name": "31.2 Venue Management", "item": [
                club_get("My Venues", "venues"),
                club_post_json("Create Venue", "venues",
                    {"name": {"ar": "ملعب جديد", "en": "New Venue"},
                     "category_id": 1, "address": "دمشق",
                     "latitude": 33.5024, "longitude": 36.2447,
                     "capacity": 22, "price_per_hour": 60000,
                     "amenities": ["parking", "showers", "lighting"]}),
                club_put_json("Update Venue", "venues/{{venue_id}}",
                    {"price_per_hour": 70000}),
                club_post_form("Upload Venue Photos", "venues/{{venue_id}}/photos"),
                club_delete("Delete Photo", "venues/{{venue_id}}/photos/{{photo_id}}"),
            ]},
            {"name": "31.3 Booking Management", "item": [
                club_get_with_query("List Bookings", "bookings",
                    [{"key": "venue_id", "value": ""}, {"key": "status", "value": ""},
                     {"key": "from", "value": ""}, {"key": "to", "value": ""}]),
                club_get("Booking Details", "bookings/{{booking_id}}"),
                club_post_json("Check-in Booking", "bookings/{{booking_id}}/check-in", {}),
                club_post_json("Mark No-Show", "bookings/{{booking_id}}/no-show", {}),
                club_post_json("Complete Booking", "bookings/{{booking_id}}/complete", {}),
            ]},
            {"name": "31.4 Schedule Management", "item": [
                club_get_with_query("Venue Schedule", "venues/{{venue_id}}/schedule",
                    [{"key": "from", "value": "2026-04-26"},
                     {"key": "to", "value": "2026-05-03"}]),
                club_post_json("Block Slot", "venues/{{venue_id}}/block-slot",
                    {"blocked_date": "2026-05-01", "start_time": "10:00",
                     "end_time": "14:00", "reason": "maintenance",
                     "notes": "صيانة دورية"}),
                club_delete("Unblock Slot", "venues/{{venue_id}}/block-slot/{{block_id}}"),
            ]},
            {"name": "31.5 Event Management", "item": [
                club_get("My Events", "events"),
                club_post_json("Create Event", "events",
                    {"title": "Friday tournament", "title_ar": "بطولة الجمعة",
                     "type": "tournament",
                     "starts_at": "2026-05-10 18:00:00",
                     "ends_at": "2026-05-10 22:00:00",
                     "registration_closes_at": "2026-05-08 23:59:59",
                     "max_participants": 16, "registration_fee": 25000,
                     "participant_type": "team", "team_size": 7}),
                club_put_json("Update Event", "events/{{event_id}}",
                    {"title_ar": "بطولة محدّثة"}),
                club_post_json("Publish Results", "events/{{event_id}}/results",
                    {"results": [
                        {"registration_id": 1, "rank": 1, "prize_amount": 100000},
                        {"registration_id": 2, "rank": 2, "prize_amount": 50000}]}),
            ]},
            {"name": "31.6 Promotions", "item": [
                club_get("My Promotions", "promotions"),
                club_post_json("Create Club Promotion", "promotions",
                    {"code": "CLUBVIP15",
                     "name": {"ar": "خصم خاص بالنادي"},
                     "type": "percentage", "value": 15,
                     "valid_to": "2026-05-31"}),
            ]},
            {"name": "31.7 Club Feed", "item": [
                club_post_json("Post Club Update", "updates",
                    {"title": "Announcement", "title_ar": "إعلان مهم",
                     "content_ar": "تم تجديد الملاعب",
                     "type": "announcement"}),
                club_delete("Delete Update", "updates/{{update_id}}"),
            ]},
            {"name": "31.8 Reviews Management", "item": [
                club_get("My Reviews", "reviews"),
                club_post_json("Respond to Review", "reviews/{{review_id}}/respond",
                    {"response": "شكراً لتقييمك. سنعمل على التحسين."}),
            ]},
            {"name": "31.9 Financial Summary", "item": [
                club_get_with_query("Financial Summary", "financial/summary",
                    [{"key": "from", "value": "2026-01-01"},
                     {"key": "to", "value": "2026-12-31"}]),
            ]},
        ],
    }
    collection["item"].append(folder)
    print(f"  Club Dashboard folder added: 9 sub-folders, 28 endpoints")


# ---------- Variables ----------

def add_variables(collection):
    new_vars = [
        {"key": "ticket_id", "value": "1", "type": "default"},
        {"key": "review_id", "value": "1", "type": "default"},
        {"key": "venue_slug", "value": "stadium-al-fayhaa", "type": "default"},
        {"key": "club_id", "value": "1", "type": "default"},
        {"key": "event_id", "value": "1", "type": "default"},
        {"key": "team_id", "value": "1", "type": "default"},
        {"key": "device_id", "value": "1", "type": "default"},
        {"key": "session_id", "value": "1", "type": "default"},
        {"key": "phone_change_request_id", "value": "1", "type": "default"},
        {"key": "refund_request_id", "value": "1", "type": "default"},
        {"key": "registration_id", "value": "1", "type": "default"},
        {"key": "admin_token", "value": "", "type": "secret"},
        {"key": "user_id", "value": "1", "type": "default"},
        {"key": "venue_id", "value": "1", "type": "default"},
        {"key": "report_id", "value": "1", "type": "default"},
        {"key": "banner_id", "value": "1", "type": "default"},
        {"key": "club_token", "value": "", "type": "secret"},
        {"key": "booking_id", "value": "1", "type": "default"},
        {"key": "block_id", "value": "1", "type": "default"},
        {"key": "photo_id", "value": "1", "type": "default"},
        {"key": "update_id", "value": "1", "type": "default"},
    ]
    if "variable" not in collection:
        collection["variable"] = []
    existing = {v.get("key") for v in collection["variable"]}
    added = 0
    for v in new_vars:
        if v["key"] not in existing:
            collection["variable"].append(v)
            existing.add(v["key"])
            added += 1
    print(f"  Variables: +{added}")


# ---------- main ----------

def main():
    print(f"📂 Reading {COLLECTION_PATH}")
    with open(COLLECTION_PATH, encoding="utf-8") as f:
        collection = json.load(f)

    before = count_endpoints(collection["item"])
    print(f"📊 Before: {before} endpoints")

    backup = COLLECTION_PATH.parent / (
        COLLECTION_PATH.stem + f".backup_{datetime.now():%Y%m%d_%H%M%S}.json"
    )
    shutil.copy(COLLECTION_PATH, backup)
    print(f"✅ Backup: {backup.name}")

    update_phase_15(collection)
    add_phase_16_folders(collection)
    add_phase_17_folders(collection)
    update_phase_18(collection)
    add_admin_folder(collection)
    add_club_folder(collection)
    add_variables(collection)

    after = count_endpoints(collection["item"])

    info = collection.setdefault("info", {})
    info["description"] = (
        f"Daq-Ehjizli Complete API Collection — {after} endpoints across "
        f"Mobile API, Admin Dashboard, and Club Dashboard.\n\n"
        f"Last updated: {datetime.now():%Y-%m-%d}"
    )

    with open(COLLECTION_PATH, "w", encoding="utf-8") as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    print(f"✅ Updated: {after} endpoints (+{after - before})")
    print(f"   Top-level folders: {len(collection['item'])}")
    print(f"   Variables: {len(collection.get('variable', []))}")


if __name__ == "__main__":
    main()
