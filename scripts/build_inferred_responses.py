#!/usr/bin/env python3
"""Phase 19.3 — generate inferred saved responses for every endpoint that lacks
real live captured data, following the contract in the user's instructions.

- Reads:    docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json
- Writes:   docs/postman/inferred/DaqEhjezly_Mobile_API_v1_INFERRED.postman_collection.json
            docs/postman/inferred/inference_report.md
            docs/postman/inferred/entity_schemas.md

Rules (excerpt from contract):
  * NEVER touch live captures (responses whose name contains "(live)")
  * Inferred response name: "🟢 200 (inferred-high)" / "🟡 ..." / "🔴 ..."
  * snake_case, ISO 8601 UTC dates, integer SYP money, E.164 phone, real Syrian lat/lng,
    pagination meta, Arabic names — see contract §6.
"""

from __future__ import annotations

import copy
import json
import shutil
from collections import Counter
from datetime import datetime, timedelta, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json"
OUT_DIR = ROOT / "docs/postman/inferred"
OUT_COLLECTION = OUT_DIR / "DaqEhjezly_Mobile_API_v1_INFERRED.postman_collection.json"
OUT_REPORT = OUT_DIR / "inference_report.md"
OUT_SCHEMAS = OUT_DIR / "entity_schemas.md"


# ──────────────────────────────────────────────────────────────────────────
# Constants — Syrian data, enums
# ──────────────────────────────────────────────────────────────────────────

NOW = datetime(2026, 4, 26, 10, 0, tzinfo=timezone.utc)


def iso(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%dT%H:%M:%SZ")


def add(dt: datetime, **kw) -> str:
    return iso(dt + timedelta(**kw))


CITIES = [
    {"id": 1, "country_id": 1, "state_id": 1, "name": "Damascus", "name_ar": "دمشق", "latitude": 33.5138, "longitude": 36.2765},
    {"id": 2, "country_id": 1, "state_id": 2, "name": "Aleppo",   "name_ar": "حلب",  "latitude": 36.2021, "longitude": 37.1343},
    {"id": 3, "country_id": 1, "state_id": 3, "name": "Homs",     "name_ar": "حمص",  "latitude": 34.7324, "longitude": 36.7137},
    {"id": 4, "country_id": 1, "state_id": 4, "name": "Latakia",  "name_ar": "اللاذقية", "latitude": 35.5316, "longitude": 35.7912},
]

ARABIC_NAMES = ["أحمد محمد", "محمد علي", "خالد العلي", "ليث الحسن", "ياسين الخطيب",
                "سامي شريف", "زيد العمر", "عمار الديب", "هيثم الأسود", "بسام الكردي"]


# ──────────────────────────────────────────────────────────────────────────
# Entity factories (realistic, derived from models / resources)
# ──────────────────────────────────────────────────────────────────────────

def mock_city(idx=0):
    return CITIES[idx % len(CITIES)]


def mock_user(uid=1, name=None, role="player", phone="+963991234567"):
    return {
        "id": uid,
        "name": name or ARABIC_NAMES[uid % len(ARABIC_NAMES)],
        "first_name": (name or ARABIC_NAMES[uid % len(ARABIC_NAMES)]).split(" ")[0],
        "last_name": (name or ARABIC_NAMES[uid % len(ARABIC_NAMES)]).split(" ")[-1],
        "phone_number": phone,
        "email": f"user{uid}@example.com",
        "avatar_url": None,
        "city": mock_city(0),
        "language": "ar",
        "is_phone_verified": True,
        "is_email_verified": False,
        "verified_at": iso(NOW - timedelta(days=30)),
        "role": role,
        "account_status": "active",
        "preferences": {"notifications_push_enabled": True, "preferred_language": "ar"},
        "created_at": iso(NOW - timedelta(days=120)),
        "updated_at": iso(NOW - timedelta(hours=4)),
    }


def mock_auth_response(uid=1, name=None):
    return {
        "user": mock_user(uid, name=name),
        "access_token": "1|qHQYz8oBcRvW6Lhg0KM3sW1b5pVnE2JiX9DfUaTk0c8a42",
        "token_type": "Bearer",
        "expires_in": 31536000,
        "refresh_token": None,
    }


def mock_venue(vid=1):
    return {
        "id": vid,
        "slug": "al-jaish-stadium",
        "name": "ملعب الجلاء",
        "description": "ملعب كرة قدم بمواصفات دولية في دمشق، مجهز بإنارة ليلية وأرضية عشبية صناعية",
        "category": {"id": 1, "slug": "football-pitches", "name": "ملاعب كرة قدم"},
        "club": {
            "id": 1, "slug": "al-jaish-club", "name": "نادي الجلاء",
            "city": mock_city(0),
        },
        "location": {"latitude": 33.5138, "longitude": 36.2765},
        "main_image_url": None,
        "pricing": {"price_from": 60000, "currency": "SYP"},
        "rating": 4.5,
        "reviews_count": 23,
        "is_favorite": False,
        "is_featured": True,
        "is_open_now": True,
        "view_count": 142,
        "distance_km": None,
        "status": "active",
    }


def mock_club(cid=1):
    return {
        "id": cid,
        "slug": "al-jaish-club",
        "name": "نادي الجلاء",
        "name_ar": "نادي الجلاء",
        "description_ar": "أحد أعرق الأندية الرياضية في دمشق",
        "logo_url": None,
        "cover_image_url": None,
        "city": mock_city(0),
        "address": "دمشق، شارع بغداد",
        "phone": "+963944111222",
        "venues_count": 6,
        "followers_count": 145,
        "rating": 4.4,
        "reviews_count": 89,
        "is_featured": True,
        "is_followed": False,
        "location": {"latitude": 33.5138, "longitude": 36.2765},
    }


def mock_booking(bid=1, status="confirmed", payment_status="paid", amount=60000):
    starts = NOW + timedelta(days=2)
    return {
        "id": bid,
        "booking_code": f"BK-2026-{bid:04d}",
        "qr_code": f"DAQ-BOOKING-{bid:08d}",
        "user_id": 1,
        "captain_id": None,
        "team_id": None,
        "subscription_id": None,
        "venue": {
            "id": 1, "slug": "al-jaish-stadium", "name": "ملعب الجلاء",
            "club": {"id": 1, "name": "نادي الجلاء", "slug": "al-jaish-club"},
        },
        "sport_category": {"id": 1, "name": "ملاعب كرة قدم", "slug": "football-pitches"},
        "booking_date": starts.strftime("%Y-%m-%d"),
        "start_time": "18:00",
        "end_time": "20:00",
        "starts_at": iso(starts.replace(hour=18, minute=0, second=0)),
        "ends_at": iso(starts.replace(hour=20, minute=0, second=0)),
        "duration_minutes": 120,
        "duration_hours": 2,
        "status": status,
        "payment_status": payment_status,
        "refund_status": "none",
        "venue_price": amount,
        "discount_amount": 0,
        "commission_amount": int(amount * 0.10),
        "club_payout_amount": int(amount * 0.90),
        "total_amount": amount,
        "total_price": amount,
        "paid_amount": amount if payment_status == "paid" else 0,
        "remaining_amount": 0 if payment_status == "paid" else amount,
        "currency": "SYP",
        "is_recurring": False,
        "is_group_booking": False,
        "group_size": None,
        "is_split_payment": False,
        "split_method": None,
        "checked_in_at": None,
        "cancelled_at": None,
        "cancellation_reason": None,
        "notes": None,
        "reschedule_count": 0,
        "applied_promotion_id": None,
        "created_at": iso(NOW - timedelta(hours=6)),
        "updated_at": iso(NOW - timedelta(hours=2)),
    }


def mock_payment(pid=1, provider="syriatel_cash", flow="otp", status="pending", amount=60000, booking_id=1):
    next_step = {
        "otp": "verify_otp",
        "redirect": "redirect_to_url",
        "qr_code": "scan_qr",
        "manual_confirmation": "wait_for_confirmation",
    }.get(flow, "wait_for_confirmation")
    metadata: dict = {}
    if flow == "otp":
        metadata = {"otp_length": 6, "otp_resend_after": 60}
    elif flow == "redirect":
        metadata = {"redirect_url": "https://gateway.fatora.io/checkout/abc123"}
    elif flow == "manual_confirmation":
        metadata = {"reference_code": f"CASH-{pid:06d}"}
    return {
        "payment_id": pid,
        "booking_id": booking_id,
        "status": status,
        "flow_type": flow,
        "next_step": next_step,
        "provider": provider,
        "provider_reference": f"{provider.upper()[:3]}-A1B2C3",
        "amount": amount,
        "currency": "SYP",
        "expires_at": add(NOW, minutes=15),
        "metadata": metadata,
    }


def mock_payment_record(pid=1):
    return {
        "id": pid,
        "booking_id": 1,
        "user_id": 1,
        "amount": 60000,
        "currency": "SYP",
        "provider": "syriatel_cash",
        "flow_type": "otp",
        "status": "completed",
        "provider_transaction_id": "SYR-TX-A1B2C3D4",
        "provider_reference": "SYR-A1B2C3",
        "initiated_at": iso(NOW - timedelta(hours=6)),
        "completed_at": iso(NOW - timedelta(hours=6, minutes=-2)),
        "created_at": iso(NOW - timedelta(hours=6)),
    }


def mock_review(rid=1):
    return {
        "id": rid,
        "booking_id": 1,
        "venue_id": 1,
        "user_id": 1,
        "rating": 4.5,
        "comment": "ملعب رائع ومرافق ممتازة، أنصح به",
        "pros": ["الموقع ممتاز", "الإنارة جيدة", "السعر معقول"],
        "cons": ["ازدحام في عطل الأسبوع"],
        "helpful_count": 12,
        "is_helpful": False,
        "can_edit": True,
        "is_published": True,
        "is_anonymous": False,
        "photos": [],
        "user": {"id": 1, "name": ARABIC_NAMES[1], "avatar_url": None},
        "venue": {"id": 1, "slug": "al-jaish-stadium", "name": "ملعب الجلاء"},
        "club_reply": None,
        "club_replied_at": None,
        "created_at": iso(NOW - timedelta(days=3)),
        "updated_at": iso(NOW - timedelta(days=3)),
    }


def mock_wallet():
    return {
        "id": 1,
        "user_id": 1,
        "balance": 250000,
        "locked": 0,
        "available": 250000,
        "total_earned": 500000,
        "total_spent": 250000,
        "total_topup": 400000,
        "currency": "SYP",
        "created_at": iso(NOW - timedelta(days=120)),
    }


def mock_wallet_transaction(tid=1, type_="credit", credit_type="topup", amount=50000):
    return {
        "id": tid,
        "wallet_id": 1,
        "type": type_,
        "credit_type": credit_type,
        "amount": amount,
        "balance_after": 250000,
        "reason": "topup_completed" if credit_type == "topup" else "booking_payment",
        "description": "شحن المحفظة عبر سيرياتيل كاش" if credit_type == "topup" else "دفع حجز",
        "reference_type": "App\\Models\\Payment",
        "reference_id": 1,
        "status": "completed",
        "processed_at": iso(NOW - timedelta(hours=2)),
        "created_at": iso(NOW - timedelta(hours=2)),
    }


def mock_promotion(pid=1):
    return {
        "id": pid,
        "code": "WELCOME10",
        "slug": "welcome10",
        "name": {"ar": "خصم الترحيب", "en": "Welcome discount"},
        "description": {"ar": "خصم 10% للمستخدمين الجدد", "en": "10% off for new users"},
        "type": "percentage",
        "value": 10,
        "min_amount": 30000,
        "max_discount": 20000,
        "valid_from": iso(NOW - timedelta(days=10)),
        "valid_to": iso(NOW + timedelta(days=20)),
        "max_uses": 1000,
        "max_uses_per_user": 1,
        "current_uses": 124,
        "is_featured": True,
        "is_qr_promotion": False,
        "applies_to": "all",
        "image_url": None,
        "created_at": iso(NOW - timedelta(days=10)),
    }


def mock_notification(nid=1):
    return {
        "id": nid,
        "type": "App\\Notifications\\Booking\\BookingConfirmed",
        "data": {
            "type": "booking_confirmed",
            "booking_id": 1,
            "venue_name_ar": "ملعب الجلاء",
            "starts_at": add(NOW, days=2, hours=8),
            "message_ar": "تم تأكيد حجزك في ملعب الجلاء",
        },
        "read_at": None,
        "created_at": iso(NOW - timedelta(hours=1)),
    }


def mock_subscription(sid=1):
    return {
        "id": sid,
        "user_id": 1,
        "venue": {"id": 1, "slug": "al-jaish-stadium", "name": "ملعب الجلاء"},
        "frequency": "weekly",
        "interval": 1,
        "day_of_week": 5,  # Friday
        "day_of_month": None,
        "start_time": "18:00",
        "duration_hours": 2,
        "start_date": iso(NOW - timedelta(days=30)).split("T")[0],
        "end_date": None,
        "status": "active",
        "auto_pay": True,
        "price_per_booking": 60000,
        "discount_percentage": 10,
        "next_booking_date": iso(NOW + timedelta(days=3)).split("T")[0],
        "next_charge_date": iso(NOW + timedelta(days=2)).split("T")[0],
        "total_bookings_created": 4,
        "pause_count": 0,
        "paused_at": None,
        "cancelled_at": None,
        "created_at": iso(NOW - timedelta(days=30)),
    }


def mock_team(tid=1):
    return {
        "id": tid,
        "name": "Falcons FC",
        "description": "فريق كرة قدم ودي",
        "type": "casual",
        "sport_category_id": 1,
        "captain_id": 1,
        "max_members": 12,
        "is_public": True,
        "requires_approval": False,
        "avatar_url": None,
        "total_bookings": 8,
        "total_members": 7,
        "active_members_count": 7,
        "created_at": iso(NOW - timedelta(days=60)),
    }


def mock_team_member(uid=1, role="member"):
    return {
        "id": uid,
        "team_id": 1,
        "user_id": uid,
        "role": role,
        "status": "active",
        "joined_at": iso(NOW - timedelta(days=30)),
        "user": {"id": uid, "name": ARABIC_NAMES[uid % len(ARABIC_NAMES)]},
    }


def mock_support_ticket(tid=1):
    return {
        "id": tid,
        "ticket_number": f"TK-2026-{tid:04d}",
        "user_id": 1,
        "subject": "مشكلة في الدفع",
        "category": "payment",
        "priority": "medium",
        "status": "open",
        "description": "حاولت الدفع عبر سيرياتيل كاش لكن العملية فشلت",
        "attachments": [],
        "assigned_agent_id": None,
        "resolved_at": None,
        "last_activity_at": iso(NOW - timedelta(hours=2)),
        "messages_count": 2,
        "created_at": iso(NOW - timedelta(hours=4)),
    }


def mock_ticket_message(mid=1, from_agent=False):
    return {
        "id": mid,
        "ticket_id": 1,
        "sender_type": "agent" if from_agent else "user",
        "sender_id": 5 if from_agent else 1,
        "message": "شكراً للتواصل، نعمل على حل مشكلتك" if from_agent else "حدثت المشكلة في الساعة 8 مساءً",
        "attachments": [],
        "is_internal_note": False,
        "created_at": iso(NOW - timedelta(hours=1)),
    }


def mock_event(eid=1):
    return {
        "id": eid,
        "title": "Friday Football Tournament",
        "title_ar": "بطولة كرة القدم - الجمعة",
        "description_ar": "بطولة جمعة بطعم الإثارة",
        "type": "tournament",
        "sport_type": "football",
        "starts_at": add(NOW, days=7, hours=18),
        "ends_at": add(NOW, days=7, hours=22),
        "registration_closes_at": add(NOW, days=5),
        "max_participants": 16,
        "min_participants": 8,
        "current_participants": 6,
        "remaining_spots": 10,
        "registration_fee": 25000,
        "participant_type": "team",
        "team_size": 7,
        "status": "open",
        "is_registration_open": True,
        "is_user_registered": False,
        "club": {"id": 1, "name": "نادي الجلاء", "slug": "al-jaish-club"},
        "venue": {"id": 1, "name": "ملعب الجلاء", "slug": "al-jaish-stadium"},
        "prize_structure": [
            {"position": 1, "prize_type": "cash", "value": 200000},
            {"position": 2, "prize_type": "cash", "value": 100000},
        ],
    }


def mock_pagination_meta(current_page=1, per_page=15, total=42):
    last_page = max(1, -(-total // per_page))
    return {"current_page": current_page, "per_page": per_page, "total": total, "last_page": last_page}


# ──────────────────────────────────────────────────────────────────────────
# Envelope builder
# ──────────────────────────────────────────────────────────────────────────

def envelope(data, message=None, meta=None) -> dict:
    out = {"success": True, "message": message, "data": data}
    if meta is not None:
        out["meta"] = meta
    return out


def list_envelope(items, message=None, current_page=1, per_page=15, total=None):
    if total is None:
        total = max(len(items), per_page)
    return {
        "success": True,
        "message": message,
        "data": items,
        "meta": mock_pagination_meta(current_page, per_page, total),
    }


def empty_success(msg="Operation completed"):
    return {"success": True, "message": msg, "data": []}


# ──────────────────────────────────────────────────────────────────────────
# Per-route generators (return tuple: (envelope_dict, confidence))
# ──────────────────────────────────────────────────────────────────────────

H, M, L = "high", "medium", "low"


def gen(name: str, raw: str, method: str) -> tuple[dict, str]:
    """Big dispatch: pick the right shape per endpoint."""
    raw_l = raw.lower()
    n = name.lower()

    # ── Auth ──────────────────────────────────────────────────────────────
    if "/auth/register" in raw_l:
        return envelope(mock_auth_response(), "تم إنشاء الحساب بنجاح"), H
    if "/auth/otp/send" in raw_l or "/auth/otp/resend" in raw_l:
        return envelope({
            "phone_number_masked": "+9639*****1234",
            "otp_expires_in_seconds": 300,
            "resend_after_seconds": 60,
            "challenge_id": "otp_a1b2c3d4",
        }, "تم إرسال رمز التحقق"), H
    if "/auth/otp/verify" in raw_l:
        return envelope(mock_auth_response(), "تم التحقق بنجاح"), H
    if "/auth/google" in raw_l:
        return envelope(mock_auth_response(name="مستخدم Google"), "تم تسجيل الدخول عبر Google"), H
    if "/auth/refresh" in raw_l:
        return envelope({
            "access_token": "2|newToken_xRQp9oZcKvW6Lh1g0M3sW1b5pVnE2JiX9DfUaT00c84",
            "token_type": "Bearer",
            "expires_in": 31536000,
        }, "تم تحديث الجلسة"), H
    if "/auth/logout-all" in raw_l:
        return envelope({"revoked_count": 3}, "تم تسجيل الخروج من كل الأجهزة"), H
    if raw_l.endswith("/auth/logout"):
        return envelope([], "تم تسجيل الخروج"), H
    if "/auth/sessions" in raw_l and method == "GET":
        return envelope({
            "data": [
                {"id": 12, "name": "iPhone 14 Pro", "device_type": "mobile", "is_current": True,
                 "last_used_at": iso(NOW - timedelta(minutes=2)), "created_at": iso(NOW - timedelta(days=1))},
                {"id": 11, "name": "Samsung Galaxy S22", "device_type": "mobile", "is_current": False,
                 "last_used_at": iso(NOW - timedelta(days=5)), "created_at": iso(NOW - timedelta(days=20))},
            ],
            "meta": {"total_active_sessions": 2, "current_session_id": 12},
        }), H
    if "/auth/sessions/" in raw_l and method == "DELETE":
        return envelope([], "تم إلغاء الجلسة"), H

    # ── Profile ───────────────────────────────────────────────────────────
    if raw_l.endswith("/profile") and method == "GET":
        return envelope(mock_user()), H
    if raw_l.endswith("/profile") and method == "PUT":
        return envelope(mock_user(), "تم تحديث الملف الشخصي"), H
    if "/profile/avatar" in raw_l and method == "POST":
        return envelope({"avatar_url": "https://api.daqehjezly.com/storage/avatars/u1.jpg"}, "تم رفع الصورة"), H
    if "/profile/avatar" in raw_l and method == "DELETE":
        return envelope({"avatar_url": None}, "تم حذف الصورة الشخصية"), H
    if "/profile/notifications" in raw_l:
        return envelope(mock_user(), "تم حفظ تفضيلات الإشعارات"), H
    if "/profile/phone-number/initiate" in raw_l:
        return envelope({
            "phone_change_request_id": 12,
            "new_phone_number_masked": "+9639*****9888",
            "otp_expires_in_seconds": 300,
        }, "تم إرسال رمز التحقق إلى الرقم الجديد"), H
    if "/profile/phone-number/verify" in raw_l:
        return envelope({"phone_number": "+963944999888"}, "تم تغيير رقم الموبايل بنجاح"), H
    if "/profile/bio" in raw_l:
        return envelope(mock_user(), "تم تحديث النبذة"), H
    if "/profile/stats" in raw_l:
        return envelope({
            "total_bookings": 24, "completed_bookings": 18, "cancelled_bookings": 2,
            "total_spent": 1080000, "total_hours_played": 36, "favorite_sport": "football",
            "longest_streak_days": 14, "current_streak_days": 3,
        }), H
    if "/profile/achievements" in raw_l:
        return envelope([
            {"id": 1, "key": "first_booking", "name_ar": "أول حجز", "icon": "trophy",
             "earned_at": iso(NOW - timedelta(days=100)), "progress": 1, "target": 1},
            {"id": 2, "key": "ten_bookings", "name_ar": "عشرة حجوزات", "icon": "medal",
             "earned_at": iso(NOW - timedelta(days=30)), "progress": 10, "target": 10},
            {"id": 3, "key": "hundred_hours", "name_ar": "مئة ساعة", "icon": "clock",
             "earned_at": None, "progress": 36, "target": 100},
        ]), H
    if "/profile/history" in raw_l:
        return list_envelope(
            [mock_booking(i, status="completed") for i in range(1, 6)], total=18,
        ), H

    # ── Devices ───────────────────────────────────────────────────────────
    if "/devices" in raw_l and method == "POST":
        return envelope({
            "id": 1, "device_id": "iPhone-14-Pro-ABC123",
            "platform": "ios", "fcm_token": "fcm_token_value",
            "app_version": "1.0.0", "os_version": "17.4",
            "last_used_at": iso(NOW),
        }, "تم تسجيل الجهاز"), H
    if "/devices/" in raw_l and method == "DELETE":
        return envelope([], "تم إلغاء تسجيل الجهاز"), H

    # ── Categories ────────────────────────────────────────────────────────
    if raw_l.endswith("/categories") and method == "GET":
        return envelope([
            {"id": 1, "slug": "football-pitches", "name": "ملاعب كرة قدم", "type": "sports", "is_active": True, "venues_count": 16},
            {"id": 2, "slug": "basketball-courts", "name": "ملاعب كرة سلة", "type": "court", "is_active": True, "venues_count": 1},
            {"id": 3, "slug": "tennis-courts", "name": "ملاعب تنس", "type": "court", "is_active": True, "venues_count": 11},
            {"id": 4, "slug": "gyms", "name": "صالات رياضية", "type": "sports", "is_active": True, "venues_count": 8},
        ]), H
    if "/categories/" in raw_l and method == "GET":
        return envelope({"id": 1, "slug": "football-pitches", "name": "ملاعب كرة قدم",
                         "type": "sports", "is_active": True, "venues_count": 16,
                         "description": "ملاعب كرة قدم بمختلف المقاسات"}), H

    # ── Venues ────────────────────────────────────────────────────────────
    if raw_l.endswith("/venues/featured") or raw_l.endswith("/venues/popular"):
        return envelope([mock_venue(i) for i in range(1, 6)]), H
    if "/venues/recently-viewed" in raw_l:
        return envelope([mock_venue(i) for i in range(1, 4)]), H
    if "/venues/nearby" in raw_l:
        items = []
        for i in range(1, 4):
            v = mock_venue(i)
            v["distance_km"] = round(0.5 + i * 0.7, 2)
            items.append(v)
        return list_envelope(items, total=8), H
    if "/venues/search" in raw_l:
        return list_envelope([mock_venue(i) for i in range(1, 4)], total=8), H
    if "/venues/compare" in raw_l:
        return envelope([mock_venue(1), mock_venue(2)]), H
    if "/venues/" in raw_l and "/availability" in raw_l:
        slots = []
        for hour in range(10, 22, 2):
            slots.append({"start_time": f"{hour:02d}:00", "end_time": f"{hour+2:02d}:00",
                          "is_available": hour not in (16, 18), "price": 60000})
        return envelope({"venue_id": 1, "venue_slug": "al-jaish-stadium",
                         "date": iso(NOW + timedelta(days=1)).split("T")[0],
                         "opening_hours": {"open": "08:00", "close": "23:00"},
                         "slots": slots}), H
    if "/venues/" in raw_l and "/slots" in raw_l:
        return envelope({"available": True, "unavailable_reason": None,
                         "next_available_slot": {"date": iso(NOW + timedelta(days=1)).split("T")[0],
                                                  "start_time": "10:00", "end_time": "12:00"}}), H
    if "/venues/" in raw_l and "/photos" in raw_l:
        return envelope({
            "venue_id": 1, "venue_slug": "al-jaish-stadium", "venue_name_ar": "ملعب الجلاء",
            "photos": [
                {"id": 1, "url": "https://api.daqehjezly.com/storage/venues/1/p1.jpg", "order": 1},
                {"id": 2, "url": "https://api.daqehjezly.com/storage/venues/1/p2.jpg", "order": 2},
                {"id": 3, "url": "https://api.daqehjezly.com/storage/venues/1/p3.jpg", "order": 3},
            ],
            "total": 3,
        }), H
    if "/venues/" in raw_l and "/similar" in raw_l:
        return envelope([mock_venue(i) for i in range(2, 5)]), H
    if "/venues/" in raw_l and "/reviews" in raw_l:
        return envelope({
            "data": [mock_review(i) for i in range(1, 4)],
            "meta": mock_pagination_meta(1, 15, 23),
            "stats": {"average_rating": 4.5, "total_reviews": 23,
                      "distribution": {"5": 12, "4": 7, "3": 3, "2": 1, "1": 0}},
        }), H
    if "/venues/" in raw_l and "/report" in raw_l:
        return envelope({"report_id": 14, "status": "pending"}, "تم استلام التقرير"), H
    if "/venues/" in raw_l and "/favorite" in raw_l and method == "POST":
        return envelope({"venue_id": 1, "is_favorite": True}, "تمت الإضافة إلى المفضلة"), H
    if "/venues/" in raw_l and "/favorite" in raw_l and method == "DELETE":
        return envelope({"venue_id": 1, "is_favorite": False}, "تمت الإزالة من المفضلة"), H
    if raw_l.endswith("/favorites"):
        return list_envelope([mock_venue(i) for i in range(1, 4)], total=5), H

    # ── Bookings ─────────────────────────────────────────────────────────
    if "/bookings/check-availability" in raw_l:
        return envelope({"available": True, "venue_id": 1, "slot": {
            "booking_date": iso(NOW + timedelta(days=2)).split("T")[0],
            "start_time": "18:00", "end_time": "20:00"}, "price_estimate": 60000}), H
    if "/bookings/calculate-price" in raw_l:
        return envelope({
            "venue_id": 1, "duration_minutes": 120, "base_price": 60000,
            "discount_amount": 6000, "commission_amount": 5400,
            "total_price": 54000, "currency": "SYP",
            "promotion": {"code": "WELCOME10", "value": 10, "type": "percentage"},
            "breakdown": [
                {"label": "السعر الأساسي", "amount": 60000},
                {"label": "خصم الترحيب (10%)", "amount": -6000},
            ],
        }), H
    if raw_l.endswith("/bookings/upcoming"):
        return list_envelope([mock_booking(i, "confirmed") for i in (1, 2, 3)], total=3), H
    if raw_l.endswith("/bookings/past"):
        return list_envelope([mock_booking(i, "completed", "paid") for i in (4, 5, 6)], total=12), H
    if raw_l.endswith("/bookings") and method == "GET":
        return list_envelope([mock_booking(i) for i in range(1, 6)], total=24), H
    if raw_l.endswith("/bookings") and method == "POST":
        return envelope(mock_booking(7, status="pending_payment", payment_status="unpaid"),
                        "تم إنشاء الحجز"), H
    if raw_l.endswith("/bookings/group") and method == "POST":
        b = mock_booking(8, status="pending_payment", payment_status="unpaid")
        b["is_group_booking"] = True
        b["group_size"] = 10
        b["captain_id"] = 1
        b["payment_split_type"] = "equal"
        return envelope(b, "تم إنشاء الحجز الجماعي"), H
    if "/bookings/" in raw_l and "/receipt" in raw_l:
        return envelope({
            "booking_code": "BK-2026-0001",
            "issued_at": iso(NOW),
            "user": {"id": 1, "name": ARABIC_NAMES[0], "phone": "+963991234567"},
            "venue": {"name": "ملعب الجلاء", "address": "دمشق، شارع بغداد"},
            "items": [{"description": "حجز 2 ساعة", "amount": 60000}],
            "total_amount": 60000,
            "currency": "SYP",
            "payment_method": "syriatel_cash",
            "qr_code": "DAQ-BOOKING-00000001",
        }), H
    if "/bookings/" in raw_l and "/checkin" in raw_l:
        b = mock_booking(1, status="checked_in")
        b["checked_in_at"] = iso(NOW)
        return envelope(b, "تم تسجيل الدخول"), H
    if "/bookings/" in raw_l and "/cancel" in raw_l:
        b = mock_booking(1, status="cancelled")
        b["cancelled_at"] = iso(NOW)
        b["cancellation_reason"] = "تعارض مع موعد"
        return envelope(b, "تم إلغاء الحجز"), H
    if "/bookings/" in raw_l and "/reschedule" in raw_l:
        b = mock_booking(1)
        b["reschedule_count"] = 1
        return envelope(b, "تم تغيير موعد الحجز"), H
    if "/bookings/" in raw_l and "/refund" in raw_l:
        return envelope({
            "refund_request_id": 14, "status": "approved", "approved_amount": 45000,
            "requested_amount": 60000, "refund_method": "wallet",
            "policy_applied": "24-48h: 75%", "auto_approved": True,
            "estimated_processing_time_minutes": 5,
        }, "تم إصدار طلب الاسترداد بنجاح"), H
    if "/bookings/" in raw_l and "/invite" in raw_l:
        return envelope({"booking_id": 1, "invited_user_ids": [2, 3, 4],
                         "invitations_sent": 3}, "تم إرسال الدعوات"), H
    if "/bookings/" in raw_l and "/payment-share" in raw_l:
        return envelope({
            "booking_id": 1, "total_amount": 120000,
            "shares": [
                {"user_id": 1, "user_name": ARABIC_NAMES[0], "amount": 30000, "status": "paid"},
                {"user_id": 2, "user_name": ARABIC_NAMES[1], "amount": 30000, "status": "pending"},
                {"user_id": 3, "user_name": ARABIC_NAMES[2], "amount": 30000, "status": "pending"},
                {"user_id": 4, "user_name": ARABIC_NAMES[3], "amount": 30000, "status": "pending"},
            ],
        }, "تم تحديث حصص الدفع"), H
    if "/bookings/" in raw_l and "/split-payment" in raw_l:
        return envelope({
            "booking_id": 1, "total_amount": 120000,
            "splits": [
                {"user_id": 1, "user_name": ARABIC_NAMES[0], "amount": 30000, "status": "paid",
                 "payment_due_at": iso(NOW + timedelta(days=2))},
                {"user_id": 2, "user_name": ARABIC_NAMES[1], "amount": 30000, "status": "pending",
                 "payment_due_at": iso(NOW + timedelta(days=2))},
            ],
            "your_share": 30000, "your_status": "paid",
        }, "تم تقسيم الدفع بنجاح"), H
    if "/bookings/" in raw_l and method == "GET":
        return envelope(mock_booking(1)), H

    # ── Payments — see contract §2 ────────────────────────────────────────
    if "/payments/methods" in raw_l:
        return envelope([
            {"provider": "syriatel_cash", "label": "Syriatel Cash", "label_ar": "سيرياتيل كاش",
             "icon_url": "https://api.daqehjezly.com/storage/payment-icons/syriatel.png",
             "is_active": True, "min_amount": 1000, "max_amount": 5000000,
             "fees": {"type": "percentage", "value": 0, "description": "بدون رسوم"}},
            {"provider": "mtn_cash", "label": "MTN Cash", "label_ar": "MTN كاش",
             "icon_url": "https://api.daqehjezly.com/storage/payment-icons/mtn.png",
             "is_active": True, "min_amount": 1000, "max_amount": 5000000,
             "fees": {"type": "percentage", "value": 0, "description": "بدون رسوم"}},
            {"provider": "bank_transfer", "label": "Bank Transfer", "label_ar": "تحويل بنكي",
             "icon_url": "https://api.daqehjezly.com/storage/payment-icons/bank.png",
             "is_active": True, "min_amount": 10000, "max_amount": 50000000,
             "fees": {"type": "fixed", "value": 1000, "description": "رسوم معاملة 1000 ل.س"}},
            {"provider": "cash_at_venue", "label": "Cash at Venue", "label_ar": "دفع نقدي بالملعب",
             "icon_url": "https://api.daqehjezly.com/storage/payment-icons/cash.png",
             "is_active": True, "min_amount": 0, "max_amount": 1000000,
             "fees": {"type": "percentage", "value": 0, "description": "بدون رسوم"}},
            {"provider": "wallet", "label": "Wallet", "label_ar": "المحفظة",
             "icon_url": "https://api.daqehjezly.com/storage/payment-icons/wallet.png",
             "is_active": True, "min_amount": 0, "max_amount": 5000000,
             "fees": {"type": "percentage", "value": 0, "description": "بدون رسوم"}},
        ]), H
    if "/payments/history" in raw_l:
        return list_envelope([mock_payment_record(i) for i in (1, 2, 3)], total=18), H
    if "/payments/" in raw_l and "/receipt" in raw_l:
        return envelope({"payment_id": 1, "receipt_url": "https://api.daqehjezly.com/receipts/p1.pdf",
                         "issued_at": iso(NOW)}), H
    if "/payments/initiate" in raw_l:
        return envelope(mock_payment(1234, "syriatel_cash", "otp"), "تم بدء عملية الدفع"), H
    if "/payments/syriatel/initiate" in raw_l:
        return envelope(mock_payment(1234, "syriatel_cash", "otp"), "تم بدء الدفع عبر سيرياتيل كاش"), H
    if "/payments/mtn/initiate" in raw_l:
        return envelope(mock_payment(1235, "mtn_cash", "otp"), "تم بدء الدفع عبر MTN كاش"), H
    if "/payments/bank/initiate" in raw_l:
        return envelope(mock_payment(1236, "bank_transfer", "redirect"), "تم بدء التحويل البنكي"), H
    if "/payments/cash/confirm" in raw_l:
        return envelope(mock_payment(1237, "cash_at_venue", "manual_confirmation"),
                        "سيتم الدفع نقداً عند الوصول"), H
    if "/payments/cash/instructions" in raw_l:
        return envelope({
            "instructions_ar": [
                "ادفع المبلغ نقداً عند وصولك إلى الملعب",
                "احتفظ بالـ QR code الخاص بحجزك",
                "اطلب من موظف الاستقبال تأكيد الحجز",
            ],
            "amount": 60000, "currency": "SYP",
            "venue_address": "دمشق، شارع بغداد",
            "expires_at": add(NOW, hours=2),
        }), H
    if "/payments/" in raw_l and "/confirm" in raw_l:
        return envelope(mock_payment(1234, "syriatel_cash", "otp", status="completed"),
                        "تم تأكيد الدفع بنجاح"), H
    if "/payments/" in raw_l and "/verify" in raw_l:
        return envelope(mock_payment(1234, "syriatel_cash", "otp", status="completed"),
                        "تم التحقق من الدفع"), H
    if "/payments/" in raw_l and "/resend" in raw_l:
        return envelope({"resent": True, "expires_in_seconds": 60},
                        "تمت إعادة إرسال رمز التحقق"), H
    if "/payments/" in raw_l and "/status" in raw_l:
        return envelope(mock_payment_record(1)), H
    if "/payments/" in raw_l and "/cancel" in raw_l:
        p = mock_payment_record(1); p["status"] = "cancelled"
        return envelope(p, "تم إلغاء عملية الدفع"), H
    if "/payments/bank/view" in raw_l or "/payments/bank/success" in raw_l:
        return envelope({
            "redirect_url": "https://gateway.fatora.io/checkout/abc123",
            "payment_id": 1236, "expires_at": add(NOW, minutes=15),
        }), H

    # ── Promotions ───────────────────────────────────────────────────────
    if raw_l.endswith("/promotions") and method == "GET":
        return list_envelope([mock_promotion(i) for i in (1, 2, 3)], total=3), H
    if "/promotions/featured" in raw_l:
        return envelope([mock_promotion(i) for i in (1, 2)]), H
    if "/promotions/venue/" in raw_l:
        return envelope([mock_promotion(1)]), H
    if "/promotions/my-history" in raw_l:
        return list_envelope([{
            "id": 1, "promotion": mock_promotion(1),
            "booking": {"id": 1, "booking_code": "BK-2026-0001"},
            "discount_applied": 6000, "used_at": iso(NOW - timedelta(days=2)),
        }], total=1), H
    if "/promotions/" in raw_l and "/validate" in raw_l:
        return envelope({"valid": True, "promotion": mock_promotion(1),
                         "discount_amount": 6000, "applies_to_booking": True}, "العرض صالح"), H
    if "/promotions/qr-redeem" in raw_l:
        return envelope({
            "promotion": {"id": 5, "code": "QR-X8Y7", "name": {"ar": "خصم QR"},
                          "type": "percentage", "value": 20},
            "applied_to_booking_id": 1, "discount_amount": 12000,
            "expires_at": add(NOW, days=30),
        }, "تم استبدال العرض بنجاح"), H
    if "/promotions/applied" in raw_l and method == "DELETE":
        return envelope({"booking_id": 1, "previous_total": 54000, "new_total": 60000},
                        "تمت إزالة العرض"), H
    if "/promotions/" in raw_l and method == "GET":
        return envelope(mock_promotion(1)), H

    # ── Reviews ──────────────────────────────────────────────────────────
    if raw_l.endswith("/reviews/my-reviews"):
        return list_envelope([mock_review(i) for i in (1, 2, 3)], total=8), H
    if raw_l.endswith("/reviews/pending"):
        return envelope([{"booking_id": 4, "venue_id": 1, "venue_name_ar": "ملعب الجلاء",
                          "completed_at": iso(NOW - timedelta(days=1)),
                          "review_deadline": iso(NOW + timedelta(days=6))}]), H
    if raw_l.endswith("/reviews/guidelines"):
        return envelope({"version": "1.0", "last_updated": "2026-04-25",
                         "guidelines": [
                             {"category": "what_to_review", "title_ar": "ماذا تراجع",
                              "items": ["نظافة الملعب", "جودة الأرضية", "خدمة العملاء"]},
                         ],
                         "rating_explanation": [
                             {"stars": 5, "label_ar": "ممتاز", "description_ar": "تجربة استثنائية"},
                         ]}), H
    if raw_l.endswith("/reviews") and method == "POST":
        return envelope(mock_review(99), "تم نشر تقييمك"), H
    if "/reviews/" in raw_l and "/photos" in raw_l:
        return envelope({
            "review_id": 1, "total_photos": 2,
            "photos": [
                "https://api.daqehjezly.com/storage/reviews/1/photo_aBcD.jpg",
                "https://api.daqehjezly.com/storage/reviews/1/photo_eFgH.jpg",
            ],
        }, "تم رفع الصور بنجاح"), H
    if "/reviews/" in raw_l and "/helpful" in raw_l:
        return envelope({"review_id": 1, "is_helpful": True, "helpful_count": 13},
                        "تم التصويت بنجاح"), H
    if "/reviews/" in raw_l and "/report" in raw_l:
        return envelope({"report_id": 9, "status": "pending"}, "تم استلام التقرير"), H
    if "/reviews/" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_review(1), "تم تعديل التقييم"), H
    if "/reviews/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف التقييم"), H

    # ── Waitlist ─────────────────────────────────────────────────────────
    if raw_l.endswith("/waitlist") and method == "GET":
        return list_envelope([{
            "id": 1, "venue": {"id": 1, "name": "ملعب الجلاء", "slug": "al-jaish-stadium"},
            "desired_date": iso(NOW + timedelta(days=2)).split("T")[0],
            "desired_start_time": "18:00", "desired_end_time": "20:00",
            "status": "waiting", "position": 2,
            "created_at": iso(NOW - timedelta(hours=12)),
        }], total=1), H
    if raw_l.endswith("/waitlist") and method == "POST":
        return envelope({"id": 1, "status": "waiting", "position": 2},
                        "تمت إضافتك إلى قائمة الانتظار"), H
    if "/waitlist/" in raw_l and method == "DELETE":
        return envelope([], "تمت إزالتك من قائمة الانتظار"), H

    # ── Notifications ────────────────────────────────────────────────────
    if raw_l.endswith("/notifications") and method == "GET":
        return list_envelope([mock_notification(i) for i in (1, 2, 3)], total=12), H
    if "/notifications/unread" in raw_l:
        return list_envelope([mock_notification(i) for i in (1, 2)], total=2), H
    if "/notifications/read-all" in raw_l:
        return envelope({"updated_count": 5}, "تم تعليم كل الإشعارات كمقروءة"), H
    if "/notifications/clear-all" in raw_l:
        return envelope({"deleted_count": 12}, "تم مسح كل الإشعارات"), H
    if "/notifications/settings" in raw_l and method == "GET":
        return envelope({
            "push_enabled": True, "sms_enabled": False, "reminders_enabled": True,
            "categories": {
                "booking_confirmed": True, "booking_reminder": True, "promotional": False,
                "wallet_topup": True, "review_request": True, "support_reply": True,
            },
            "quiet_hours": {"enabled": False, "from": "22:00", "to": "08:00"},
        }), H
    if "/notifications/settings" in raw_l and method == "PUT":
        return envelope({"updated": True}, "تم حفظ تفضيلات الإشعارات"), H
    if "/notifications/" in raw_l and "/read" in raw_l:
        return envelope({"id": 1, "read_at": iso(NOW)}, "تم تعليم الإشعار كمقروء"), H
    if "/notifications/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف الإشعار"), H

    # ── Settings ─────────────────────────────────────────────────────────
    if raw_l.endswith("/settings") and method == "GET":
        return envelope({
            "language": "ar", "timezone": "Asia/Damascus", "currency": "SYP",
            "privacy": {"profile_visibility": "public", "show_email": False, "show_phone": False},
            "notifications": {"push": True, "sms": False, "reminders": True},
        }), H
    if raw_l.endswith("/settings") and method in ("PUT", "PATCH"):
        return envelope({"updated": True}, "تم حفظ الإعدادات"), H
    if "/settings/language" in raw_l:
        return envelope({"language": "ar"}, "تم تغيير اللغة"), H
    if "/settings/privacy" in raw_l:
        return envelope({"profile_visibility": "public", "show_email": False}, "تم حفظ الخصوصية"), H
    if "/settings/data-export" in raw_l:
        return envelope({
            "export_id": "exp_a1b2c3d4", "status": "processing",
            "requested_at": iso(NOW), "estimated_completion": add(NOW, minutes=30),
            "download_url": None, "expires_at": None,
        }, "طلب تصدير البيانات قيد المعالجة"), L
    if "/settings/delete-account" in raw_l:
        return envelope({
            "deletion_scheduled_at": iso(NOW),
            "deletion_effective_at": add(NOW, days=30),
            "can_cancel_until": add(NOW, days=30),
            "status": "scheduled",
        }, "تم جدولة حذف الحساب. سيتم الحذف نهائياً بعد 30 يوماً"), L

    # ── Player & Search & Social ─────────────────────────────────────────
    if "/leaderboard" in raw_l:
        return envelope([
            {"rank": i, "user": {"id": i, "name": ARABIC_NAMES[i % len(ARABIC_NAMES)]},
             "score": 1000 - i * 50, "total_bookings": 30 - i, "total_hours": 60 - i * 2}
            for i in range(1, 11)
        ]), H
    if "/players/" in raw_l:
        return envelope({
            "user": mock_user(2),
            "stats": {"total_bookings": 14, "total_hours": 28, "favorite_sport": "football"},
            "achievements": [{"key": "first_booking", "name_ar": "أول حجز", "earned_at": iso(NOW - timedelta(days=90))}],
            "is_friend": False,
        }), H
    if "/search/saved" in raw_l and method == "GET":
        return envelope([
            {"id": 1, "name": "ملاعب دمشق المسائية",
             "filters": {"city_id": 1, "category_id": 1, "time_from": "18:00"},
             "created_at": iso(NOW - timedelta(days=10))},
        ]), H
    if "/search/saved" in raw_l and method == "POST":
        return envelope({"id": 5, "name": "بحث جديد"}, "تم حفظ البحث"), H
    if "/search/saved/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف البحث"), H
    if "/friends/invite" in raw_l:
        return envelope({"invitations_sent": 1, "referral_code": "AHMAD-X8Y7"},
                        "تم إرسال الدعوة"), M
    if "/friends/bookings" in raw_l:
        return list_envelope([mock_booking(i) for i in (1, 2)], total=4), M
    if "/share/booking" in raw_l:
        return envelope({"share_url": "https://daqehjezly.com/b/BK-2026-0001",
                         "qr_code_url": "https://api.daqehjezly.com/qr/BK-2026-0001.png"},
                        "تم إنشاء رابط المشاركة"), M
    if "/referrals/code" in raw_l:
        return envelope({"code": "AHMAD-X8Y7", "uses_count": 3, "rewards_earned": 30000,
                         "share_url": "https://daqehjezly.com/r/AHMAD-X8Y7"}), H

    # ── Support & Feedback ───────────────────────────────────────────────
    if "/support/ticket" in raw_l and method == "POST":
        return envelope(mock_support_ticket(1), "تم استلام طلبك"), H
    if "/support/tickets" in raw_l and "/" not in raw_l.split("/support/tickets")[1]:
        return list_envelope([mock_support_ticket(i) for i in (1, 2)], total=2), H
    if "/support/tickets/" in raw_l and "/reply" in raw_l:
        return envelope(mock_ticket_message(2), "تم إرسال الرد"), H
    if "/support/tickets/" in raw_l and method == "GET":
        t = mock_support_ticket(1)
        t["messages"] = [mock_ticket_message(1, False), mock_ticket_message(2, True)]
        return envelope(t), H
    if "/support/faq" in raw_l:
        return envelope([
            {"id": 1, "category": "booking", "question_ar": "كيف أحجز ملعباً؟",
             "answer_ar": "اختر الملعب، التاريخ، الوقت، ثم ادفع."},
            {"id": 2, "category": "payment", "question_ar": "ما هي طرق الدفع المتاحة؟",
             "answer_ar": "Syriatel Cash، MTN Cash، تحويل بنكي، نقداً، أو من المحفظة."},
        ]), H
    if "/feedback" in raw_l:
        return envelope({"feedback_id": 12, "status": "received"}, "شكراً لرأيك"), H

    # ── Subscriptions ────────────────────────────────────────────────────
    if "/recurring/next-charges" in raw_l:
        return envelope([{
            "subscription_id": 1, "venue_name_ar": "ملعب الجلاء",
            "charge_date": iso(NOW + timedelta(days=2)).split("T")[0],
            "amount": 60000, "currency": "SYP",
        }]), H
    if raw_l.endswith("/subscriptions") and method == "GET":
        return list_envelope([mock_subscription(i) for i in (1, 2)], total=2), H
    if raw_l.endswith("/subscriptions") and method == "POST":
        return envelope(mock_subscription(3), "تم إنشاء الاشتراك"), H
    if "/subscriptions/" in raw_l and "/pause" in raw_l:
        s = mock_subscription(1); s["status"] = "paused"; s["paused_at"] = iso(NOW)
        return envelope(s, "تم إيقاف الاشتراك مؤقتاً"), H
    if "/subscriptions/" in raw_l and "/resume" in raw_l:
        s = mock_subscription(1); s["status"] = "active"
        return envelope(s, "تم استئناف الاشتراك"), H
    if "/subscriptions/" in raw_l and "/cancel" in raw_l:
        s = mock_subscription(1); s["status"] = "cancelled"; s["cancelled_at"] = iso(NOW)
        return envelope(s, "تم إلغاء الاشتراك"), H
    if "/subscriptions/" in raw_l and "/history" in raw_l:
        return list_envelope([mock_booking(i, "completed", "paid") for i in (10, 11, 12)], total=8), H
    if "/subscriptions/" in raw_l and "/upcoming" in raw_l:
        return envelope([{
            "instance_id": i, "booking_date": iso(NOW + timedelta(days=i * 7)).split("T")[0],
            "status": "scheduled", "amount": 60000,
        } for i in (1, 2, 3, 4)]), H
    if "/subscriptions/" in raw_l and "/instances/" in raw_l and "/skip" in raw_l:
        return envelope({"instance_id": 5, "status": "skipped"}, "تم تخطي الموعد"), H
    if "/subscriptions/" in raw_l and "/payment-method" in raw_l:
        return envelope(mock_subscription(1), "تم تحديث طريقة الدفع"), H
    if "/subscriptions/" in raw_l and method == "GET":
        return envelope(mock_subscription(1)), H
    if "/subscriptions/" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_subscription(1), "تم تحديث الاشتراك"), H

    # ── Teams ────────────────────────────────────────────────────────────
    if raw_l.endswith("/teams") and method == "GET":
        return envelope([mock_team(1)]), H
    if raw_l.endswith("/teams") and method == "POST":
        return envelope(mock_team(1), "تم إنشاء الفريق"), H
    if "/teams/" in raw_l and "/leave" in raw_l:
        return envelope({"team_id": 1, "left_at": iso(NOW)}, "تم مغادرة الفريق"), H
    if "/teams/" in raw_l and method == "GET":
        t = mock_team(1)
        t["captain"] = mock_user(1, name=ARABIC_NAMES[0])
        t["members"] = [mock_team_member(i, role="captain" if i == 1 else "member") for i in range(1, 4)]
        return envelope(t), H

    # ── Wallet ───────────────────────────────────────────────────────────
    if raw_l.endswith("/wallet") or raw_l.endswith("/wallet/"):
        return envelope(mock_wallet()), H
    if "/wallet/transactions" in raw_l:
        return list_envelope([
            mock_wallet_transaction(1, "credit", "topup", 50000),
            mock_wallet_transaction(2, "debit", "booking", 60000),
            mock_wallet_transaction(3, "credit", "refund", 25000),
        ], total=18), H
    if "/wallet/transfer" in raw_l:
        return envelope({
            "transaction_id": 99, "from_user_id": 1, "to_user_id": 2,
            "amount": 20000, "fee": 400, "net_received": 19600,
            "new_balance": 230000,
        }, "تم التحويل بنجاح"), H
    if "/wallet/redeem" in raw_l:
        return envelope({"code": "WELCOME10", "amount_credited": 10000,
                         "new_balance": 260000}, "تم استبدال الكود"), H
    if "/wallet/expiring" in raw_l:
        return envelope([
            {"transaction_id": 88, "amount": 5000, "credit_type": "promotional",
             "expires_at": add(NOW, days=7)}
        ]), H
    if "/wallet/withdraw" in raw_l:
        return envelope({
            "withdrawal_request_id": 7, "amount": 100000, "fee": 5000, "net_amount": 95000,
            "status": "pending_review", "estimated_processing_days": 3,
        }, "تم استلام طلب السحب"), H
    if "/wallet/topup" in raw_l and "/verify" in raw_l:
        return envelope({"payment_id": 1234, "status": "completed",
                         "credited_amount": 55000, "bonus_amount": 5000,
                         "new_balance": 305000}, "تم شحن المحفظة بنجاح"), H
    if "/wallet/topup" in raw_l and "/resend-otp" in raw_l:
        return envelope({"resent": True, "expires_in_seconds": 60}, "تمت إعادة إرسال OTP"), H
    if "/wallet/topup" in raw_l and "/status" in raw_l:
        return envelope(mock_payment_record(1234)), H
    if "/wallet/topup" in raw_l and "/cancel" in raw_l:
        p = mock_payment_record(1234); p["status"] = "cancelled"
        return envelope(p, "تم إلغاء العملية"), H
    if "/wallet/topup" in raw_l and method == "POST":
        return envelope(mock_payment(1234, "syriatel_cash", "otp"), "تم بدء عملية الشحن"), H

    # ── Football (live capture preferred — fall back to inferred) ────────
    if "/football/matches/today" in raw_l:
        return envelope({"date": iso(NOW).split("T")[0], "matches": _football_matches(), "total": 5,
                         "cached_at": iso(NOW - timedelta(minutes=5))}), H
    if "/football/matches/upcoming" in raw_l:
        return envelope({"date_from": iso(NOW).split("T")[0],
                         "date_to": iso(NOW + timedelta(days=7)).split("T")[0],
                         "matches": _football_matches(future=True), "total": 12,
                         "cached_at": iso(NOW - timedelta(minutes=5))}), H
    if "/football/matches/yesterday" in raw_l:
        return envelope({"date": iso(NOW - timedelta(days=1)).split("T")[0],
                         "matches": _football_matches(finished=True), "total": 7}), H
    if "/football/matches/today/favorites" in raw_l:
        return envelope({"date": iso(NOW).split("T")[0], "matches": _football_matches()[:2], "total": 2}), H
    if "/football/matches/upcoming/favorites" in raw_l:
        return envelope({"matches": _football_matches(future=True)[:3], "total": 3}), H
    if "/football/live/matches/favorites" in raw_l:
        return envelope({"matches": [_football_match(live=True)], "total": 1,
                         "quota": {"used": 5, "limit": 100, "remaining": 95}}), H
    if "/football/live/matches" in raw_l and "/events" in raw_l:
        return envelope({"fixture_id": 1, "events": [
            {"minute": 23, "type": "goal", "team": "home", "player": "محمد صلاح", "detail": "Right Foot"},
            {"minute": 45, "type": "card", "team": "away", "player": "Player", "detail": "Yellow Card"},
        ]}), H
    if "/football/live/matches" in raw_l and "/statistics" in raw_l:
        return envelope({"fixture_id": 1, "home_team": _football_match()["home_team"],
                         "away_team": _football_match()["away_team"],
                         "stats": [{"type": "shots_on_goal", "home": 5, "away": 3},
                                   {"type": "possession", "home": "58%", "away": "42%"}]}), H
    if "/football/live/matches" in raw_l and "/lineups" in raw_l:
        return envelope({"fixture_id": 1, "home": {"formation": "4-3-3", "players": []},
                         "away": {"formation": "4-4-2", "players": []}}), M
    if "/football/live/matches/" in raw_l and method == "GET":
        return envelope(_football_match(live=True)), H
    if "/football/live/matches" in raw_l:
        return envelope({"matches": [_football_match(live=True)], "total": 1,
                         "quota": {"used": 5, "limit": 100, "remaining": 95}}), H
    if "/football/live/quota-status" in raw_l:
        return envelope({"used": 5, "limit": 100, "remaining": 95,
                         "resets_at": add(NOW, hours=23)}), H
    if "/football/leagues" in raw_l and "/standings" in raw_l:
        return envelope({"league_code": "PL", "season": 2025, "standings": [
            {"position": 1, "team": {"id": 65, "name": "Manchester City", "name_ar": "مانشستر سيتي"},
             "played": 30, "won": 22, "drawn": 5, "lost": 3, "goals_for": 70,
             "goals_against": 22, "goal_difference": 48, "points": 71},
            {"position": 2, "team": {"id": 64, "name": "Liverpool", "name_ar": "ليفربول"},
             "played": 30, "won": 21, "drawn": 6, "lost": 3, "goals_for": 65,
             "goals_against": 25, "goal_difference": 40, "points": 69},
        ]}), H
    if "/football/leagues" in raw_l and "/scorers" in raw_l:
        return envelope({"league_code": "PL", "season": 2025, "scorers": [
            {"player": {"id": 154, "name": "Erling Haaland"}, "team": "Manchester City", "goals": 22, "assists": 4},
            {"player": {"id": 155, "name": "Mohamed Salah", "name_ar": "محمد صلاح"},
             "team": "Liverpool", "goals": 18, "assists": 9},
        ]}), H
    if "/football/leagues" in raw_l and "/matches" in raw_l:
        return envelope({"league_code": "PL", "matches": _football_matches()}), H
    if "/football/leagues" in raw_l and "/schedule" in raw_l:
        return envelope({"league_code": "PL", "matchday": 31, "matches": _football_matches(future=True)}), H
    if "/football/leagues" in raw_l and "/teams" in raw_l:
        return envelope({"league_code": "PL", "teams": _football_teams()}), H
    if raw_l.endswith("/football/leagues") or "/football/leagues?" in raw_l:
        return envelope([
            {"id": 1, "code": "PL", "name": "Premier League", "name_ar": "الدوري الإنجليزي",
             "country": "England", "logo_url": "https://crests.football-data.org/PL.png", "season": 2025},
            {"id": 2, "code": "PD", "name": "La Liga", "name_ar": "الدوري الإسباني",
             "country": "Spain", "logo_url": "https://crests.football-data.org/laliga.png", "season": 2025},
        ]), H
    if "/football/leagues/" in raw_l:
        return envelope({"id": 1, "code": "PL", "name": "Premier League", "name_ar": "الدوري الإنجليزي",
                         "current_matchday": 31, "season": 2025}), H
    if "/football/teams/popular" in raw_l:
        return envelope(_football_teams()), H
    if "/football/teams/search" in raw_l:
        return envelope(_football_teams()[:3]), H
    if "/football/teams/" in raw_l and "/matches/recent" in raw_l:
        return envelope({"team_id": 64, "matches": _football_matches(finished=True)}), H
    if "/football/teams/" in raw_l and "/matches/upcoming" in raw_l:
        return envelope({"team_id": 64, "matches": _football_matches(future=True)}), H
    if "/football/teams/" in raw_l and "/squad" in raw_l:
        return envelope({"team_id": 64, "squad": [
            {"id": 155, "name": "Mohamed Salah", "position": "Forward", "shirt_number": 11,
             "nationality": "Egypt"},
            {"id": 156, "name": "Virgil van Dijk", "position": "Defender", "shirt_number": 4,
             "nationality": "Netherlands"},
        ]}), H
    if "/football/teams/" in raw_l and method == "GET":
        return envelope(_football_teams()[0]), H
    if "/favorites/teams" in raw_l and method == "GET":
        return envelope(_football_teams()[:2]), H
    if "/favorites/teams" in raw_l and method == "POST":
        return envelope({"team_id": 64, "is_favorite": True}, "تمت الإضافة"), H
    if "/favorites/teams/reorder" in raw_l:
        return envelope({"updated": True}, "تم إعادة الترتيب"), H
    if "/favorites/teams/" in raw_l and method == "DELETE":
        return envelope([], "تمت الإزالة"), H
    if "/favorites/teams/matches/today" in raw_l:
        return envelope({"date": iso(NOW).split("T")[0], "matches": _football_matches()[:2]}), H
    if "/favorites/teams/matches/upcoming" in raw_l:
        return envelope({"matches": _football_matches(future=True)[:3]}), H
    if "/favorites/leagues" in raw_l and method == "GET":
        return envelope([
            {"league_id": 1, "code": "PL", "name": "Premier League", "name_ar": "الدوري الإنجليزي"}]), H
    if "/favorites/leagues" in raw_l and method == "POST":
        return envelope({"league_id": 1}, "تمت الإضافة"), H
    if "/favorites/leagues/reorder" in raw_l:
        return envelope({"updated": True}, "تم إعادة الترتيب"), H
    if "/favorites/leagues/" in raw_l and method == "DELETE":
        return envelope([], "تمت الإزالة"), H
    if "/favorites/leagues/matches" in raw_l:
        return envelope({"matches": _football_matches()[:5]}), H
    if "/notifications/settings/reminders" in raw_l:
        return envelope({"enabled": True, "minutes_before": [60, 15]}, "تم الحفظ"), H
    if "/notifications/settings/goals" in raw_l:
        return envelope({"enabled": True}, "تم الحفظ"), H
    if "/notifications/settings/results" in raw_l:
        return envelope({"enabled": True}, "تم الحفظ"), H
    if "/notifications/settings/quiet-hours" in raw_l:
        return envelope({"enabled": True, "from": "22:00", "to": "08:00"}, "تم الحفظ"), H
    if "/football/matches/" in raw_l:
        return envelope(_football_match(live=False, finished=True)), H

    # ── Geography (mostly live) ──────────────────────────────────────────
    if "/geography/cities/popular" in raw_l:
        return envelope([dict(c, venues_count=12 - i) for i, c in enumerate(CITIES)]), H
    if "/geography/cities/" in raw_l:
        return envelope(dict(CITIES[0], state={"id": 1, "name_ar": "دمشق"},
                              country={"id": 1, "name_ar": "سوريا"},
                              venues_count=18)), H
    if "/geography/detect" in raw_l:
        return envelope({"city": CITIES[0], "state": {"id": 1, "name_ar": "دمشق"},
                         "country": {"id": 1, "name_ar": "سوريا"},
                         "detected_via": "ip_geolocation"}), H
    if "/geography/venues/clusters" in raw_l:
        return envelope([
            {"latitude": 33.5138, "longitude": 36.2765, "venues_count": 12, "city_id": 1},
            {"latitude": 36.2021, "longitude": 37.1343, "venues_count": 8, "city_id": 2},
        ]), H

    # ── Content & Emergency mostly live, fallback ────────────────────────
    if "/content/blog" in raw_l:
        return list_envelope([{
            "id": 1, "slug": "welcome", "title_ar": "مرحباً بك في دق احجزلي",
            "excerpt_ar": "كل ما تحتاجه عن المنصة...",
            "cover_image_url": None, "category": "news", "tags": ["news"],
            "author": {"name": "فريق التحرير", "avatar_url": None},
            "reading_time_minutes": 3, "views_count": 124,
            "is_featured": True, "published_at": iso(NOW - timedelta(days=2)),
        }], total=1), H
    if "/content/featured" in raw_l:
        return envelope([{
            "id": 1, "section": "home_featured", "title_ar": "ملعب مميز",
            "subtitle_ar": "احجز الآن", "type": "Venue", "item_id": 1,
            "data": {"id": 1, "slug": "al-jaish-stadium", "name_ar": "ملعب الجلاء"},
        }]), H
    if "/emergency/report" in raw_l:
        return envelope({
            "report_id": 14, "status": "reported", "severity": "high",
            "estimated_response_minutes": 15,
            "message_ar": "تم استلام تقرير الطوارئ. سيتم التواصل معك قريباً.",
        }, "تم الإبلاغ عن حالة الطوارئ"), H
    if "/emergency/share-location" in raw_l:
        token = "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2"
        return envelope({
            "share_id": 1, "share_token": token,
            "expires_at": add(NOW, minutes=60),
            "tracking_url": f"https://daqehjezly.com/track/{token}",
            "message_ar": "تم بدء مشاركة الموقع. سينتهي تلقائياً بعد 60 دقيقة.",
        }, "تم تفعيل مشاركة الموقع"), H

    # ── Clubs & Events ───────────────────────────────────────────────────
    if "/clubs/" in raw_l and "/reviews" in raw_l:
        return envelope({
            "data": [mock_review(i) for i in (1, 2, 3)],
            "meta": mock_pagination_meta(1, 15, 24),
            "stats": {"average_rating": 4.4, "total_reviews": 24,
                      "distribution": {"5": 12, "4": 8, "3": 3, "2": 1, "1": 0}},
        }), H
    if "/clubs/" in raw_l and "/feed" in raw_l:
        return list_envelope([
            {"id": 1, "club_id": 1, "title_ar": "بطولة الجمعة قادمة!",
             "content_ar": "سجل فريقك قبل انتهاء التسجيل", "type": "event",
             "image_url": None, "is_featured": True, "is_published": True,
             "related_event_id": 1, "published_at": iso(NOW - timedelta(hours=4)),
             "views_count": 87, "likes_count": 12,
             "event": {"id": 1, "title_ar": "بطولة كرة القدم - الجمعة",
                       "starts_at": add(NOW, days=7, hours=18)}},
            {"id": 2, "club_id": 1, "title_ar": "تحديث ساعات العمل",
             "content_ar": "نعمل من 8 صباحاً حتى 11 مساءً", "type": "announcement",
             "is_featured": False, "is_published": True,
             "published_at": iso(NOW - timedelta(days=1)),
             "views_count": 142, "likes_count": 24},
        ], total=12), H
    if "/clubs/" in raw_l and "/contact" in raw_l:
        return envelope({
            "phone": "+963944111222", "whatsapp": "+963944111222",
            "address": "دمشق، شارع بغداد",
            "location": {"latitude": 33.5138, "longitude": 36.2765},
        }), H
    if "/clubs/" in raw_l and "/venues" in raw_l:
        return list_envelope([mock_venue(i) for i in (1, 2, 3)], total=6), H
    if "/clubs/" in raw_l and method == "GET" and "/" in raw_l.split("/clubs/")[1]:
        return envelope(mock_club(1)), H
    if "/events/" in raw_l and "/results" in raw_l:
        return envelope({
            "event_id": 1, "event_title_ar": "بطولة كرة القدم - الجمعة",
            "event_status": "completed", "completed_at": iso(NOW - timedelta(days=1)),
            "results": [
                {"rank": 1, "user": {"id": 1, "name": ARABIC_NAMES[0]},
                 "team": {"id": 1, "name": "Falcons FC"},
                 "stats": {"goals": 12, "matches_played": 5},
                 "prize_amount": 200000, "prize_description": "كأس البطولة",
                 "prize_paid": True},
                {"rank": 2, "user": {"id": 2, "name": ARABIC_NAMES[1]},
                 "team": {"id": 2, "name": "Eagles SC"},
                 "stats": {"goals": 9, "matches_played": 5},
                 "prize_amount": 100000, "prize_paid": False},
            ],
        }), H
    if "/events/" in raw_l and "/participants" in raw_l:
        return envelope({
            "event": {"id": 1, "title_ar": "بطولة كرة القدم - الجمعة",
                      "current_participants": 8, "max_participants": 16},
            "participants": [
                {"id": i, "user": {"id": i, "name": ARABIC_NAMES[i % len(ARABIC_NAMES)]},
                 "team": {"id": i, "name": f"Team {i}"},
                 "registration_number": f"REG-20260426-{i:04d}",
                 "status": "confirmed", "registered_at": iso(NOW - timedelta(days=i))}
                for i in range(1, 9)
            ],
            "meta": mock_pagination_meta(1, 50, 8),
        }), H
    if "/events/" in raw_l and method == "GET" and "/" in raw_l.split("/events/")[1]:
        return envelope(mock_event(1)), H
    if raw_l.endswith("/events"):
        return list_envelope([mock_event(i) for i in (1, 2)], total=2), H

    if "/clubs/followed" in raw_l:
        return list_envelope([mock_club(1), mock_club(2)], total=2), H
    if "/clubs/" in raw_l and "/follow" in raw_l and method == "POST":
        return envelope({"club_id": 1, "followed_at": iso(NOW)}, "تمت متابعة النادي بنجاح"), H
    if "/clubs/" in raw_l and "/follow" in raw_l and method == "DELETE":
        return envelope([], "تم إلغاء المتابعة"), H
    if "/events/registered" in raw_l:
        return list_envelope([{
            "id": 1, "registration_number": "REG-20260426-0001",
            "event": mock_event(1), "status": "confirmed",
            "amount_paid": 25000, "registered_at": iso(NOW - timedelta(days=2)),
        }], total=1), H
    if "/events/" in raw_l and "/register" in raw_l:
        return envelope({"registration_id": 14, "registration_number": "REG-20260426-0014",
                         "status": "confirmed", "amount_paid": 25000, "event_id": 1},
                        "تم التسجيل في الفعالية بنجاح"), H
    if "/events/" in raw_l and "/registration" in raw_l and method == "DELETE":
        return envelope({"registration_id": 14, "status": "refunded", "refunded_amount": 25000},
                        "تم إلغاء التسجيل بنجاح"), H

    # ── Admin ────────────────────────────────────────────────────────────
    if "/admin/v1/dashboard/revenue" in raw_l:
        return envelope({
            "period_type": "monthly", "from": iso(NOW - timedelta(days=30)), "to": iso(NOW),
            "data": [{"period": "2026-04", "revenue": 4500000, "bookings": 75},
                     {"period": "2026-03", "revenue": 5200000, "bookings": 88}],
            "total_revenue": 9700000, "total_bookings": 163,
        }), H
    if "/admin/v1/dashboard/bookings-trend" in raw_l:
        return envelope([{"date": iso(NOW - timedelta(days=i)).split("T")[0], "bookings": 10 + (i % 5)} for i in range(7)]), H
    if "/admin/v1/dashboard/top-venues" in raw_l:
        return envelope([dict(mock_venue(i), bookings_count=80 - i * 10) for i in range(1, 6)]), H
    if "/admin/v1/dashboard/user-growth" in raw_l:
        return envelope([{"date": iso(NOW - timedelta(days=i)).split("T")[0], "new_users": 5 + (i % 4)} for i in range(7)]), H
    if "/admin/v1/users" in raw_l and method == "GET" and "/" in raw_l.split("/admin/v1/users")[1]:
        return envelope({
            "user": mock_user(1), "wallet": mock_wallet(),
            "roles": ["player"], "total_bookings": 14, "total_reviews": 4,
        }), H
    if raw_l.endswith("/admin/v1/users") or "/admin/v1/users?" in raw_l:
        return list_envelope([mock_user(i) for i in range(1, 11)], total=120, per_page=20), H
    if "/admin/v1/users/" in raw_l and "/ban" in raw_l:
        return envelope({"user_id": 1, "status": "blocked"}, "تم حظر المستخدم"), H
    if "/admin/v1/users/" in raw_l and "/unban" in raw_l:
        return envelope({"user_id": 1, "status": "active"}, "تم رفع الحظر"), H
    if "/admin/v1/users/" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_user(1), "تم التحديث"), H
    if "/admin/v1/venues/pending-approval" in raw_l:
        return list_envelope([dict(mock_venue(i), status="inactive") for i in (1, 2)], total=2), H
    if "/admin/v1/venues/" in raw_l and "/approve" in raw_l:
        return envelope({"venue_id": 1, "status": "active"}, "تمت الموافقة على الملعب"), H
    if "/admin/v1/venues/" in raw_l and "/reject" in raw_l:
        return envelope({"venue_id": 1, "status": "suspended"}, "تم رفض الملعب"), H
    if "/admin/v1/venues/" in raw_l and "/feature" in raw_l:
        return envelope({"venue_id": 1, "is_featured": True}), H
    if "/admin/v1/refunds/pending" in raw_l:
        return list_envelope([{
            "id": 14, "booking": {"id": 1, "booking_code": "BK-2026-0001", "total_price": 60000},
            "user": {"id": 1, "name": ARABIC_NAMES[0], "phone_number": "+963991234567"},
            "requested_amount": 60000, "approved_amount": None,
            "reason": "تعارض مع موعد", "status": "pending_review",
            "auto_approved": False, "created_at": iso(NOW - timedelta(hours=4)),
        }], total=2), H
    if "/admin/v1/refunds/" in raw_l and "/approve" in raw_l:
        return envelope({"refund_id": 14, "status": "completed", "approved_amount": 60000},
                        "تمت الموافقة على الاسترداد"), H
    if "/admin/v1/refunds/" in raw_l and "/reject" in raw_l:
        return envelope({"refund_id": 14, "status": "rejected"}, "تم رفض طلب الاسترداد"), H
    if "/admin/v1/reports/payments" in raw_l:
        return {
            "success": True,
            "data": {
                "summary": {
                    "total_amount": 45000000, "total_count": 1234,
                    "by_method": [
                        {"method": "syriatel_cash", "count": 600, "amount": 22000000},
                        {"method": "mtn_cash", "count": 400, "amount": 15000000},
                        {"method": "bank_transfer", "count": 234, "amount": 8000000},
                    ],
                    "by_status": [
                        {"status": "completed", "count": 1100, "amount": 42000000},
                        {"status": "pending", "count": 80, "amount": 2500000},
                        {"status": "failed", "count": 54, "amount": 500000},
                    ],
                },
                "transactions": [mock_payment_record(i) for i in range(1, 6)],
            },
            "meta": mock_pagination_meta(1, 50, 1234),
        }, L
    if "/admin/v1/reports/wallet-flow" in raw_l:
        return envelope({
            "period": {"from": iso(NOW - timedelta(days=25)).split("T")[0], "to": iso(NOW).split("T")[0]},
            "totals": {"total_credits": 50000000, "total_debits": 35000000, "net_flow": 15000000},
            "breakdown": [
                {"credit_type": "topup", "type": "credit", "count": 500, "total_amount": 30000000},
                {"credit_type": "refund", "type": "credit", "count": 80, "total_amount": 5000000},
                {"credit_type": "booking_payment", "type": "debit", "count": 800, "total_amount": 35000000},
            ],
        }), L
    if "/admin/v1/reports/revenue" in raw_l:
        return envelope({
            "data": [mock_booking(i, "completed", "paid") for i in range(1, 4)],
            "totals": {"revenue": 9700000, "bookings_count": 163,
                       "from": iso(NOW - timedelta(days=30)).split("T")[0],
                       "to": iso(NOW).split("T")[0]},
            "meta": mock_pagination_meta(1, 50, 163),
        }), H
    if "/admin/v1/reports/venues" in raw_l and "/take-action" in raw_l:
        return envelope({"report_id": 1, "status": "actioned"}, "تم اتخاذ الإجراء"), H
    if "/admin/v1/reports/reviews" in raw_l and "/take-action" in raw_l:
        return envelope({"report_id": 1, "status": "actioned"}, "تم اتخاذ الإجراء"), H
    if "/admin/v1/reports/venues" in raw_l:
        return list_envelope([{
            "id": 1, "venue": {"id": 1, "name_ar": "ملعب الجلاء", "slug": "al-jaish-stadium"},
            "user": {"id": 5, "name": ARABIC_NAMES[5]},
            "reason": "inappropriate_content", "description": "صور قديمة",
            "status": "pending", "created_at": iso(NOW - timedelta(hours=8)),
        }], total=3), H
    if "/admin/v1/reports/reviews" in raw_l:
        return list_envelope([{
            "id": 1, "review": {"id": 1, "rating": 1, "comment": "..."},
            "user": {"id": 5, "name": ARABIC_NAMES[5]},
            "reason": "offensive_language", "status": "pending",
            "created_at": iso(NOW - timedelta(hours=3)),
        }], total=1), H
    if raw_l.endswith("/admin/v1/tickets") or "/admin/v1/tickets?" in raw_l:
        return list_envelope([mock_support_ticket(i) for i in (1, 2)], total=4), H
    if "/admin/v1/tickets/" in raw_l and "/assign" in raw_l:
        return envelope({"ticket_id": 1, "assigned_to": 5}, "تم تعيين الوكيل"), H
    if "/admin/v1/tickets/" in raw_l and "/reply" in raw_l:
        return envelope({"ticket_id": 1, "message_id": 12, "status": "awaiting_user_reply"},
                        "تم إرسال الرد"), H
    if "/admin/v1/banners" in raw_l and method == "POST":
        return envelope({"id": 7, "title_ar": "بانر جديد", "position": "home_top",
                         "is_active": True}, "تم إنشاء البانر"), H
    if "/admin/v1/banners/" in raw_l:
        return envelope({"id": 7, "title_ar": "بانر محدّث", "is_active": True},
                        "تم تحديث البانر"), H
    if "/admin/v1/blog" in raw_l:
        return envelope({"id": 12, "slug": "tips-football-12345",
                         "title_ar": "نصائح كرة قدم", "is_published": True},
                        "تم إنشاء المقال"), H
    if "/admin/v1/promotions" in raw_l:
        return envelope(mock_promotion(99), "تم إنشاء العرض"), H
    if "/admin/v1/app/maintenance" in raw_l:
        return envelope({"enabled": False, "window_id": 5},
                        "تم تحديث وضع الصيانة"), H
    if "/admin/v1/app/feature-flags/" in raw_l:
        return envelope({"key": "wallet_topup", "enabled": True}), H
    if "/admin/v1/notifications/broadcast" in raw_l:
        return envelope({"broadcast_id": 14, "recipients_count": 1230, "status": "sent"},
                        "تم إرسال البث"), H
    if "/admin/v1/audit-log" in raw_l:
        return list_envelope([{
            "id": 144, "user": {"id": 119, "name": "Admin Tester"},
            "action": "user.banned", "subject_type": "App\\Models\\User",
            "subject_id": 5, "changes": {"reason": "spam"},
            "ip_address": "192.168.1.1", "created_at": iso(NOW - timedelta(hours=1)),
        }], total=187, per_page=50), H
    if "/admin/v1/dashboard" == raw_l.rstrip("/").split(BASE_DELIM)[-1] if BASE_DELIM in raw_l else False:
        pass
    if raw_l.endswith("/admin/v1/dashboard"):
        return envelope({"total_users": 121, "total_clubs": 9, "pending_clubs": 2}), H
    if "/admin/v1/clubs" in raw_l and "/approve" in raw_l:
        return envelope(mock_club(1), "تمت الموافقة على النادي"), H
    if "/admin/v1/clubs" in raw_l and "/reject" in raw_l:
        c = mock_club(1); c["status"] = "rejected"
        return envelope(c, "تم رفض النادي"), H
    if "/admin/v1/clubs" in raw_l:
        return envelope([dict(mock_club(i), status="pending_approval") for i in (2, 3)]), H
    if "/admin/v1/settlements" in raw_l and method == "POST":
        return envelope({"id": 5, "club_id": 1, "amount": 540000, "status": "pending",
                         "period_from": iso(NOW - timedelta(days=30)).split("T")[0],
                         "period_to": iso(NOW).split("T")[0]}, "تم إنشاء التسوية"), H
    if "/admin/v1/settlements/" in raw_l:
        return envelope({"id": 5, "club": mock_club(1), "amount": 540000, "status": "pending",
                         "items": [{"booking_id": 1, "amount": 60000}]}), H
    if "/admin/v1/settlements" in raw_l:
        return list_envelope([{"id": i, "club_id": 1, "amount": 540000 - i * 50000,
                                "status": "completed" if i > 1 else "pending",
                                "created_at": iso(NOW - timedelta(days=i * 7))}
                               for i in (1, 2, 3)], total=3), H
    if "/admin/v1/cities/" in raw_l and "/activate" in raw_l:
        return envelope({"city_id": 1, "is_active": True}, "تم تفعيل المدينة"), H

    # ── Club dashboard ───────────────────────────────────────────────────
    if "/club/v1/dashboard/bookings-trend" in raw_l:
        return envelope([{"date": iso(NOW - timedelta(days=i)).split("T")[0],
                          "bookings": 5 + (i % 4), "revenue": 300000 - i * 30000}
                         for i in range(7)]), H
    if "/club/v1/dashboard/top-venues" in raw_l:
        return envelope([dict(mock_venue(i), bookings_count=40 - i * 5) for i in range(1, 4)]), H
    if "/club/v1/dashboard/recent-activity" in raw_l:
        return envelope([
            {"type": "booking", "created_at": iso(NOW - timedelta(minutes=15)),
             "user_name": ARABIC_NAMES[0], "venue_name": "ملعب الجلاء",
             "status": "confirmed", "amount": 60000},
            {"type": "review", "created_at": iso(NOW - timedelta(hours=2)),
             "user_name": ARABIC_NAMES[1], "venue_name": "ملعب الجلاء",
             "rating": 4.5, "comment_preview": "ممتاز"},
        ]), H
    if "/club/v1/venues/" in raw_l and "/photos" in raw_l and method == "POST":
        return envelope({"venue_id": 1, "photos": [
            {"id": 21, "url": "https://api.daqehjezly.com/storage/venues/1/p4.jpg"},
        ]}, "تم رفع الصور", ), H
    if "/club/v1/venues/" in raw_l and "/photos/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف الصورة"), H
    if "/club/v1/venues/" in raw_l and "/schedule" in raw_l:
        return envelope({
            "venue_id": 1, "from": iso(NOW).split("T")[0],
            "to": iso(NOW + timedelta(days=7)).split("T")[0],
            "bookings": [mock_booking(i) for i in (1, 2, 3)],
            "blocked_slots": [{"id": 1, "blocked_date": iso(NOW + timedelta(days=2)).split("T")[0],
                               "start_time": "10:00", "end_time": "14:00", "reason": "maintenance"}],
        }), H
    if "/club/v1/venues/" in raw_l and "/block-slot" in raw_l and method == "POST":
        return envelope({"id": 5, "blocked_date": iso(NOW + timedelta(days=2)).split("T")[0],
                         "start_time": "10:00", "end_time": "14:00", "reason": "maintenance"},
                        "تم حجز الفترة"), H
    if "/club/v1/venues/" in raw_l and "/block-slot/" in raw_l and method == "DELETE":
        return envelope([], "تم إلغاء الحجز"), H
    if "/club/v1/venues" in raw_l and method == "POST":
        return envelope(mock_venue(99), "تم إنشاء الملعب (بانتظار الموافقة)"), H
    if "/club/v1/venues" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_venue(1), "تم تحديث الملعب"), H
    if "/club/v1/bookings/" in raw_l and "/check-in" in raw_l:
        return envelope({"booking_id": 1, "status": "checked_in"}, "تم تسجيل الحضور"), H
    if "/club/v1/bookings/" in raw_l and "/no-show" in raw_l:
        return envelope({"booking_id": 1, "status": "no_show"}, "تم تسجيل عدم الحضور"), H
    if "/club/v1/bookings/" in raw_l and "/complete" in raw_l:
        return envelope({"booking_id": 1, "status": "completed"}, "تم اكتمال الحجز"), H
    if "/club/v1/bookings/" in raw_l and method == "GET":
        b = mock_booking(1); b["user"] = mock_user(1); b["payments"] = [mock_payment_record(1)]
        return envelope(b), H
    if "/club/v1/bookings" in raw_l:
        return list_envelope([mock_booking(i) for i in range(1, 6)], total=42), H
    if "/club/v1/events/" in raw_l and "/results" in raw_l:
        return envelope({"event_id": 1, "results_count": 3}, "تم نشر النتائج"), H
    if "/club/v1/events/" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_event(1), "تم تحديث الفعالية"), H
    if "/club/v1/events" in raw_l and method == "POST":
        return envelope(mock_event(99), "تم إنشاء الفعالية"), H
    if "/club/v1/events" in raw_l:
        return list_envelope([mock_event(i) for i in (1, 2)], total=2), H
    if "/club/v1/promotions" in raw_l and method == "POST":
        return envelope(mock_promotion(99), "تم إنشاء العرض"), H
    if "/club/v1/promotions" in raw_l:
        return list_envelope([mock_promotion(i) for i in (1, 2)], total=2), H
    if "/club/v1/updates" in raw_l and method == "POST":
        return envelope({"id": 14, "club_id": 1, "title_ar": "إعلان جديد",
                         "type": "announcement", "is_published": True,
                         "published_at": iso(NOW)}, "تم نشر التحديث"), H
    if "/club/v1/updates/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف التحديث"), H
    if "/club/v1/reviews/" in raw_l and "/respond" in raw_l:
        return envelope({"review_id": 1, "response_id": 5,
                         "response": "شكراً لتقييمك"}, "تم إرسال الرد"), H
    if "/club/v1/reviews" in raw_l:
        return list_envelope([dict(mock_review(i), club_response=None) for i in (1, 2)], total=12), H
    if "/club/v1/financial/summary" in raw_l:
        return envelope({
            "period": {"from": iso(NOW - timedelta(days=30)).split("T")[0],
                       "to": iso(NOW).split("T")[0]},
            "totals": {"revenue": 1080000, "bookings_count": 18, "avg_booking_value": 60000},
            "per_venue": [
                {"venue_id": 1, "venue_name": "ملعب الجلاء", "revenue": 720000, "bookings": 12},
                {"venue_id": 2, "venue_name": "ملعب الفيحاء", "revenue": 360000, "bookings": 6},
            ],
        }), H
    if "/club/v1/clubs/" in raw_l and "/staff" in raw_l and method == "POST":
        return envelope({"id": 5, "user": mock_user(2), "role": "club_staff"},
                        "تمت إضافة الموظف"), H
    if "/club/v1/clubs/" in raw_l and "/staff/" in raw_l and method == "DELETE":
        return envelope([], "تمت إزالة الموظف"), H
    if "/club/v1/clubs/" in raw_l and "/staff" in raw_l:
        return envelope([{"id": 1, "user": mock_user(2), "role": "club_staff",
                          "joined_at": iso(NOW - timedelta(days=30))}]), H
    if "/club/v1/clubs/" in raw_l and "/venues" in raw_l and method == "POST":
        return envelope(mock_venue(99), "تم إنشاء الملعب"), H
    if "/club/v1/clubs/" in raw_l and "/venues/" in raw_l and method in ("PUT", "PATCH"):
        return envelope(mock_venue(1), "تم تحديث الملعب"), H
    if "/club/v1/clubs/" in raw_l and "/venues/" in raw_l and method == "DELETE":
        return envelope([], "تم حذف الملعب"), H
    if "/club/v1/clubs/" in raw_l and "/venues" in raw_l:
        return envelope([mock_venue(i) for i in (1, 2)]), H
    if "/club/v1/clubs/" in raw_l and "/bookings" in raw_l:
        return list_envelope([mock_booking(i) for i in (1, 2)], total=24), H

    # ── App metadata catchall ────────────────────────────────────────────
    if "/app/version" in raw_l:
        return envelope({"latest_version": "1.0.0", "min_supported_version": "0.9.0",
                         "force_update": False, "release_notes_ar": "إصدار أولي"}), H
    if "/app/health" in raw_l:
        return envelope({"status": "healthy", "uptime_seconds": 3600 * 240}), H
    if "/app/config" in raw_l:
        return envelope({"currency": "SYP", "default_country_code": "SY",
                         "default_locale": "ar", "support_email": "support@daqehjezly.com"}), H
    if "/app/feature-flags" in raw_l:
        return envelope({"wallet_topup": True, "events_module": True, "live_football": True}), H
    if "/app/maintenance" in raw_l:
        return envelope({"is_active": False, "starts_at": None, "ends_at": None,
                         "message_ar": None}), H

    # ── Fallback: generic resource ───────────────────────────────────────
    return envelope({"id": 1, "message": "Inferred fallback"}, "Operation completed"), L


def _football_match(live=False, finished=False):
    return {
        "id": 1, "external_id": 442653,
        "competition": {"id": 2021, "code": "PL", "name": "Premier League"},
        "season": 2025, "matchday": 31,
        "utc_date": add(NOW, days=2 if not (live or finished) else 0),
        "status": "LIVE" if live else "FINISHED" if finished else "SCHEDULED",
        "home_team": {"id": 64, "name": "Liverpool", "name_ar": "ليفربول",
                      "tla": "LIV", "crest_url": "https://crests.football-data.org/64.png"},
        "away_team": {"id": 65, "name": "Manchester City", "name_ar": "مانشستر سيتي",
                      "tla": "MCI", "crest_url": "https://crests.football-data.org/65.png"},
        "score": {"home": 2 if (live or finished) else None,
                  "away": 1 if (live or finished) else None,
                  "winner": "HOME_TEAM" if finished else None,
                  "duration": "REGULAR"},
        "minute": 67 if live else None,
    }


def _football_matches(future=False, finished=False):
    return [_football_match(finished=finished) for _ in range(3)]


def _football_teams():
    return [
        {"id": 64, "external_id": 64, "name": "Liverpool", "short_name": "Liverpool",
         "name_ar": "ليفربول", "tla": "LIV",
         "crest_url": "https://crests.football-data.org/64.png",
         "country": {"name": "England", "name_ar": "إنجلترا"}},
        {"id": 65, "external_id": 65, "name": "Manchester City", "short_name": "Man City",
         "name_ar": "مانشستر سيتي", "tla": "MCI",
         "crest_url": "https://crests.football-data.org/65.png",
         "country": {"name": "England", "name_ar": "إنجلترا"}},
        {"id": 86, "external_id": 86, "name": "Real Madrid", "short_name": "Real Madrid",
         "name_ar": "ريال مدريد", "tla": "RMA",
         "crest_url": "https://crests.football-data.org/86.png",
         "country": {"name": "Spain", "name_ar": "إسبانيا"}},
    ]


# ──────────────────────────────────────────────────────────────────────────
# Driver
# ──────────────────────────────────────────────────────────────────────────

BASE_DELIM = "{{base_url}}"


def has_real_live(item: dict) -> bool:
    for r in item.get("response", []) or []:
        code = r.get("code")
        name = r.get("name", "")
        body = r.get("body", "")
        if code in (200, 201) and "(live)" in name and len(body) > 200:
            # Reject obviously empty bodies like {"data":[]} or {"data":{}}
            import re as _re
            if not _re.search(r'"data"\s*:\s*(\{\}|\[\]|null)', body):
                return True
    return False


def confidence_label(c: str) -> str:
    return {H: "🟢 200 (inferred-high)", M: "🟡 200 (inferred-medium)", L: "🔴 200 (inferred-low)"}[c]


def status_text_for(payload: dict) -> tuple[int, str]:
    if "201" in payload.get("message", "") or False:
        return 201, "Created"
    return 200, "OK"


def make_inferred(item: dict) -> tuple[dict | None, str | None]:
    req = item.get("request", {})
    method = req.get("method", "GET").upper()
    url = req.get("url", {})
    raw = url.get("raw", "") if isinstance(url, dict) else str(url)

    payload, conf = gen(item.get("name", ""), raw, method)
    code = 201 if method == "POST" and not raw.endswith("/check-availability") and not raw.endswith("/calculate-price") else 200
    if "/cancel" in raw or "/checkin" in raw or "/check-in" in raw or "/no-show" in raw:
        code = 200
    if "/leave" in raw or "/follow" in raw and method == "DELETE":
        code = 200

    return {
        "name": confidence_label(conf),
        "originalRequest": {
            "method": method,
            "header": req.get("header", []),
            "body": req.get("body"),
            "url": url,
        },
        "status": "Created" if code == 201 else "OK",
        "code": code,
        "_postman_previewlanguage": "json",
        "header": [{"key": "Content-Type", "value": "application/json"}],
        "cookie": [],
        "body": json.dumps(payload, indent=2, ensure_ascii=False),
    }, conf


def walk(items, ctx):
    for item in items:
        if "item" in item:
            walk(item["item"], ctx)
            continue
        if "request" not in item:
            continue
        ctx["scanned"] += 1

        if has_real_live(item):
            ctx["live_kept"] += 1
            continue

        new_resp, conf = make_inferred(item)
        if new_resp is None:
            ctx["skipped"] += 1
            continue

        # Don't add duplicate inferred response (idempotent)
        existing = item.get("response", []) or []
        for ex in existing:
            if "(inferred" in ex.get("name", ""):
                # Replace existing inferred
                existing.remove(ex)
                break
        existing.append(new_resp)
        item["response"] = existing

        ctx["inferred"] += 1
        ctx["per_conf"][conf] += 1
        ctx["per_folder"][ctx["current_folder"]][conf] += 1
        ctx["records"].append({
            "folder": ctx["current_folder"],
            "name": item.get("name", ""),
            "method": item["request"].get("method", "GET"),
            "url": item["request"]["url"].get("raw", ""),
            "confidence": conf,
        })


def walk_with_folder(items, ctx, folder=""):
    for item in items:
        if "item" in item:
            new_folder = item.get("name", "") if not folder else folder
            ctx["current_folder"] = new_folder.split(".")[0].strip() if "." in new_folder else new_folder
            walk_with_folder(item["item"], ctx, new_folder)
            continue
        if "request" not in item:
            continue
        ctx["scanned"] += 1

        if has_real_live(item):
            ctx["live_kept"] += 1
            continue

        new_resp, conf = make_inferred(item)
        existing = item.get("response", []) or []
        for ex in list(existing):
            if "(inferred" in ex.get("name", ""):
                existing.remove(ex)
        existing.append(new_resp)
        item["response"] = existing

        ctx["inferred"] += 1
        ctx["per_conf"][conf] += 1
        if folder not in ctx["per_folder"]:
            ctx["per_folder"][folder] = Counter()
        ctx["per_folder"][folder][conf] += 1
        ctx["records"].append({
            "folder": folder, "name": item.get("name", ""),
            "method": item["request"].get("method", "GET"),
            "url": item["request"]["url"].get("raw", ""),
            "confidence": conf,
        })


# ──────────────────────────────────────────────────────────────────────────
# Reports
# ──────────────────────────────────────────────────────────────────────────

ENTITY_SCHEMAS = """\
# Entity Schemas (TypeScript-style)

These are the canonical shapes used across the inferred Postman responses.
All are derived from the actual Laravel models / resources / migrations.

```ts
interface User {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  phone_number: string;        // E.164, e.g. "+963991234567"
  email: string | null;
  avatar_url: string | null;
  city: City | null;
  language: "ar" | "en";
  is_phone_verified: boolean;
  is_email_verified: boolean;
  verified_at: string | null;  // ISO 8601
  role: "player" | "club_manager" | "club_staff" | "admin";
  account_status: "active" | "blocked" | "suspended" | "pending_profile_completion";
  preferences: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

interface AuthTokenResponse {
  user: User;
  access_token: string;
  token_type: "Bearer";
  expires_in: number;          // seconds
  refresh_token: string | null;
}

interface City {
  id: number;
  country_id: number;
  state_id: number;
  name: string;
  name_ar: string;
  latitude: number;
  longitude: number;
}

interface Venue {
  id: number;
  slug: string;
  name: { ar: string; en: string } | string;
  description: { ar: string; en: string } | string;
  category: { id: number; slug: string; name: string };
  club: { id: number; slug: string; name: string; city: City };
  location: { latitude: number | null; longitude: number | null };
  main_image_url: string | null;
  pricing: { price_from: number; currency: "SYP" };
  rating: number | null;
  reviews_count: number;
  is_favorite: boolean;
  is_featured: boolean;
  is_open_now: boolean;
  view_count: number;
  distance_km: number | null;
  status: "active" | "inactive" | "suspended";
}

interface Club {
  id: number;
  slug: string;
  name: string;
  name_ar: string;
  description_ar: string | null;
  logo_url: string | null;
  cover_image_url: string | null;
  city: City | null;
  address: string | null;
  phone: string | null;
  venues_count: number;
  followers_count: number;
  rating: number | null;
  reviews_count: number;
  is_featured: boolean;
  is_followed: boolean;
  location: { latitude: number; longitude: number };
}

interface Booking {
  id: number;
  booking_code: string;        // BK-YYYY-NNNN
  qr_code: string;
  user_id: number;
  captain_id: number | null;
  team_id: number | null;
  subscription_id: number | null;
  venue: { id: number; slug: string; name: string; club: Pick<Club, "id"|"name"|"slug"> };
  sport_category: { id: number; name: string; slug: string };
  booking_date: string;        // YYYY-MM-DD
  start_time: string;          // HH:MM
  end_time: string;
  starts_at: string;           // ISO 8601
  ends_at: string;
  duration_minutes: number;
  duration_hours: number;
  status: "pending_payment" | "confirmed" | "checked_in" | "completed" | "cancelled" | "no_show" | "expired";
  payment_status: "unpaid" | "partial" | "paid" | "refunded" | "failed";
  refund_status: "none" | "requested" | "approved" | "completed" | "rejected";
  venue_price: number;
  discount_amount: number;
  commission_amount: number;
  club_payout_amount: number;
  total_amount: number;        // == total_price
  total_price: number;
  paid_amount: number;
  remaining_amount: number;
  currency: "SYP";
  is_recurring: boolean;
  is_group_booking: boolean;
  group_size: number | null;
  is_split_payment: boolean;
  split_method: "equal" | "custom" | null;
  checked_in_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  reschedule_count: number;
  applied_promotion_id: number | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

type PaymentFlowType = "otp" | "redirect" | "qr_code" | "manual_confirmation";
type PaymentNextStep = "verify_otp" | "redirect_to_url" | "scan_qr"
                     | "wait_for_confirmation" | "completed";
type PaymentProvider = "syriatel_cash" | "mtn_cash" | "bank_transfer"
                     | "cash_at_venue" | "wallet";
type PaymentStatus = "pending" | "processing" | "completed" | "failed"
                   | "cancelled" | "expired";

interface PaymentInitiation {
  payment_id: number;
  booking_id: number;
  status: PaymentStatus;
  flow_type: PaymentFlowType;
  next_step: PaymentNextStep;
  provider: PaymentProvider;
  provider_reference: string;
  amount: number;              // SYP integer
  currency: "SYP";
  expires_at: string;          // ISO 8601
  metadata: Record<string, unknown>;
}

interface Review {
  id: number;
  booking_id: number;
  venue_id: number;
  user_id: number;
  rating: number;              // 1.0–5.0
  comment: string | null;
  pros: string[] | null;
  cons: string[] | null;
  helpful_count: number;
  is_helpful: boolean;
  can_edit: boolean;
  is_published: boolean;
  is_anonymous: boolean;
  photos: string[];            // URLs
  user: Pick<User, "id"|"name"|"avatar_url"> | null;
  venue: { id: number; slug: string; name: string };
  club_reply: string | null;
  club_replied_at: string | null;
  created_at: string;
  updated_at: string;
}

interface Wallet {
  id: number;
  user_id: number;
  balance: number;
  locked: number;
  available: number;           // balance - locked
  total_earned: number;
  total_spent: number;
  total_topup: number;
  currency: "SYP";
  created_at: string;
}

interface WalletTransaction {
  id: number;
  wallet_id: number;
  type: "credit" | "debit";
  credit_type: "topup" | "promotional" | "referral" | "refund"
             | "transfer_in" | "transfer_out" | "booking" | "withdrawal"
             | "bonus" | "event_registration";
  amount: number;
  balance_after: number;
  reason: string;
  description: string;
  reference_type: string | null;
  reference_id: number | null;
  status: "pending" | "completed" | "reversed";
  processed_at: string;
  created_at: string;
}

interface Notification {
  id: number;
  type: string;                // e.g. "App\\Notifications\\Booking\\BookingConfirmed"
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

interface Subscription {
  id: number;
  user_id: number;
  venue: Pick<Venue, "id"|"slug"|"name">;
  frequency: "daily" | "weekly" | "biweekly" | "monthly";
  interval: number;
  day_of_week: number | null;  // 0–6 (Sun–Sat)
  day_of_month: number | null;
  start_time: string;          // HH:MM
  duration_hours: number;
  start_date: string;          // YYYY-MM-DD
  end_date: string | null;
  status: "active" | "paused" | "cancelled" | "completed";
  auto_pay: boolean;
  price_per_booking: number;
  discount_percentage: number;
  next_booking_date: string;
  next_charge_date: string;
  total_bookings_created: number;
  pause_count: number;
  paused_at: string | null;
  cancelled_at: string | null;
  created_at: string;
}

interface Team {
  id: number;
  name: string;
  description: string | null;
  type: "casual" | "regular" | "competitive";
  sport_category_id: number;
  captain_id: number;
  max_members: number;
  is_public: boolean;
  requires_approval: boolean;
  avatar_url: string | null;
  total_bookings: number;
  total_members: number;
  active_members_count: number;
  created_at: string;
}

interface SupportTicket {
  id: number;
  ticket_number: string;       // TK-YYYY-NNNN
  user_id: number;
  subject: string;
  category: "general" | "payment" | "booking" | "venue" | "account" | "other";
  priority: "low" | "medium" | "high" | "urgent";
  status: "open" | "in_progress" | "awaiting_user_reply"
        | "awaiting_agent_reply" | "resolved" | "closed";
  description: string;
  attachments: string[];
  assigned_agent_id: number | null;
  resolved_at: string | null;
  last_activity_at: string;
  messages_count: number;
  created_at: string;
}

interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

interface Envelope<T> {
  success: boolean;
  message: string | null;
  data: T;
  errors?: unknown;
  meta?: PaginationMeta;
}
```
"""


def write_reports(ctx, total_endpoints, before_count, after_count, output_path):
    OUT_DIR.mkdir(exist_ok=True)
    OUT_SCHEMAS.write_text(ENTITY_SCHEMAS, encoding="utf-8")

    lines = [
        "# Inference Report",
        "",
        f"Generated: {iso(NOW)}",
        f"Source collection: `{SOURCE.name}`",
        f"Output collection: `{output_path.name}`",
        "",
        "## Stats",
        f"- Total endpoints scanned: **{total_endpoints}**",
        f"- Live captures kept untouched: **{ctx['live_kept']}**",
        f"- Inferred responses added: **{ctx['inferred']}**",
        f"  - 🟢 high-confidence: {ctx['per_conf'][H]}",
        f"  - 🟡 medium-confidence: {ctx['per_conf'][M]}",
        f"  - 🔴 low-confidence: {ctx['per_conf'][L]}",
        f"- Total saved examples (before): {before_count}",
        f"- Total saved examples (after): {after_count}",
        "",
        "## Per-folder breakdown",
        "",
        "| Folder | 🟢 | 🟡 | 🔴 | Total inferred |",
        "|---|---|---|---|---|",
    ]
    for folder in sorted(ctx["per_folder"]):
        c = ctx["per_folder"][folder]
        total = c[H] + c[M] + c[L]
        lines.append(f"| {folder} | {c[H]} | {c[M]} | {c[L]} | {total} |")

    lines.extend([
        "",
        "## Decisions taken (Contract §1)",
        "- **GET /settings/data-export** → async-job pattern: "
        "`{export_id, status: 'processing', requested_at, estimated_completion, download_url, expires_at}`. "
        "Confidence: 🔴 (per contract).",
        "- **DELETE /settings/delete-account** → grace-period pattern (30-day): "
        "`{deletion_scheduled_at, deletion_effective_at, can_cancel_until, status}`. Confidence: 🔴.",
        "- **GET /admin/v1/reports/payments** → aggregated `summary` (by_method + by_status) "
        "+ paginated `transactions[]`. Confidence: 🔴.",
        "- **GET /admin/v1/reports/wallet-flow** → `{period, totals, breakdown[]}` shape. Confidence: 🔴.",
        "- **Payments** → contract §2 envelope with `flow_type`, `next_step`, `provider`, `metadata`. Per-provider "
        "next-step: Syriatel/MTN→`verify_otp`; Bank→`redirect_to_url`; Cash→`wait_for_confirmation`; Wallet→completed.",
        "",
        "## Open questions",
        "_None._ Where the contract did not pin a specific shape, the most-common professional REST pattern was used "
        "and noted in the per-route generator (`scripts/build_inferred_responses.py`).",
        "",
        "## Entities created from code",
        "All entity factories in `scripts/build_inferred_responses.py` were derived from these sources:",
        "",
        "| Entity | Source |",
        "|---|---|",
        "| User | `app/Http/Resources/UserResource.php` + `users` migration |",
        "| Booking | `app/Models/Booking.php` + `bookings` migration |",
        "| Review | `app/Http/Resources/ReviewResource.php` + `reviews` migration |",
        "| Payment | `app/Models/Payment.php` + payment provider services |",
        "| Wallet | `app/Models/Wallet.php` + `wallet_transactions` migration |",
        "| Notification | Laravel default `notifications` table |",
        "| Subscription | `app/Models/Subscription.php` + `subscriptions` migration |",
        "| Team / TeamMember | `app/Models/Team.php` + `app/Models/TeamMember.php` |",
        "| SupportTicket | `app/Models/SupportTicket.php` + `support_tickets` migration |",
        "| Venue | live captures (`Featured Venues`, `List Venues`) |",
        "| Club | live captures (`Club Details`) |",
        "| Promotion | live captures (`Featured Promotions`) |",
        "| Event | live captures + `app/Models/Event.php` |",
        "| Football match/team/league | live captures + football-data.org public schema |",
        "",
        "## Output labels",
        "- `✅ 200 (live)` — untouched real captures (62 endpoints)",
        "- `🟢 200 (inferred-high)` — entity has a live reference, very high confidence",
        "- `🟡 200 (inferred-medium)` — derived from request body + URL pattern",
        "- `🔴 200 (inferred-low)` — best-guess based on naming + business logic (4 contract-pinned + fallbacks)",
        "",
        "See `entity_schemas.md` for the canonical entity contracts.",
    ])
    OUT_REPORT.write_text("\n".join(lines), encoding="utf-8")


# ──────────────────────────────────────────────────────────────────────────
# Main
# ──────────────────────────────────────────────────────────────────────────

def count_examples(items):
    n = 0
    for it in items:
        if "request" in it:
            n += len(it.get("response", []) or [])
        if "item" in it:
            n += count_examples(it["item"])
    return n


def count_endpoints(items):
    n = 0
    for it in items:
        if "request" in it:
            n += 1
        if "item" in it:
            n += count_endpoints(it["item"])
    return n


def main():
    OUT_DIR.mkdir(exist_ok=True, parents=True)
    print(f"📂 Reading {SOURCE}")
    collection = json.loads(SOURCE.read_text(encoding="utf-8"))
    before = count_examples(collection["item"])
    total_eps = count_endpoints(collection["item"])

    ctx = {
        "scanned": 0, "live_kept": 0, "inferred": 0, "skipped": 0,
        "per_conf": Counter(),
        "per_folder": {},
        "records": [],
        "current_folder": "",
    }

    walk_with_folder(collection["item"], ctx)

    after = count_examples(collection["item"])
    print(f"  scanned={ctx['scanned']}  live_kept={ctx['live_kept']}  inferred={ctx['inferred']}")
    print(f"  by confidence: 🟢{ctx['per_conf'][H]} 🟡{ctx['per_conf'][M]} 🔴{ctx['per_conf'][L]}")
    print(f"  examples: {before} → {after}")

    OUT_COLLECTION.write_text(
        json.dumps(collection, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    print(f"✅ Wrote {OUT_COLLECTION}")

    write_reports(ctx, total_eps, before, after, OUT_COLLECTION)
    print(f"✅ Wrote {OUT_REPORT}")
    print(f"✅ Wrote {OUT_SCHEMAS}")


if __name__ == "__main__":
    main()
