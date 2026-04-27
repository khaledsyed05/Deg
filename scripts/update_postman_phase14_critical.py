#!/usr/bin/env python3
"""Add Phase 14 (critical risk-mitigation) endpoints to existing folders.

Adds 6 endpoints across 4 existing folders:
- Booking flow: reschedule + refund
- Venues: report
- Reviews/promotions folder: report review
- Support folder (or new): ticket details + reply

Idempotent.
"""
import json
import shutil
from datetime import datetime
from pathlib import Path

COLLECTION_PATH = Path('docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json')


def _url(path):
    return {'raw': '{{base_url}}/' + path, 'host': ['{{base_url}}'], 'path': path.split('/')}


def json_body(name, method, path, body):
    return {
        'name': name,
        'request': {
            'method': method,
            'header': [{'key': 'Content-Type', 'value': 'application/json'}],
            'body': {'mode': 'raw', 'raw': json.dumps(body, indent=2, ensure_ascii=False)},
            'url': _url(path),
        },
    }


def get_req(name, path):
    return {
        'name': name,
        'request': {'method': 'GET', 'header': [], 'url': _url(path)},
    }


def find_folder(items, predicate):
    for it in items:
        if 'item' in it and predicate(it):
            return it
    return None


def upsert_by_name(folder, items):
    existing = {it.get('name'): i for i, it in enumerate(folder.get('item', []))}
    for it in items:
        if it['name'] in existing:
            folder['item'][existing[it['name']]] = it
        else:
            folder['item'].append(it)


def count(items):
    total = 0
    for it in items:
        if 'request' in it:
            total += 1
        if 'item' in it:
            total += count(it['item'])
    return total


def main():
    with COLLECTION_PATH.open('r', encoding='utf-8') as f:
        collection = json.load(f)
    before = count(collection['item'])

    backup = COLLECTION_PATH.with_name(
        COLLECTION_PATH.stem + f'.backup_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json'
    )
    shutil.copy(COLLECTION_PATH, backup)

    items = collection['item']

    # Booking flow
    booking = find_folder(items, lambda f: 'Booking' in f.get('name', '') or 'حجز' in f.get('name', ''))
    if not booking:
        booking = {'name': '03. Booking Flow', 'item': []}
        items.append(booking)
    upsert_by_name(booking, [
        json_body('Reschedule Booking', 'PUT', 'bookings/{{booking_id}}/reschedule', {
            'new_slot_date': '2026-04-30',
            'new_start_time': '18:00',
            'new_end_time': '19:00',
            'reason': 'تعارض مع عمل',
        }),
        json_body('Request Refund', 'POST', 'bookings/{{booking_id}}/refund', {
            'reason': 'تعارض مع موعد طارئ',
            'refund_method': 'wallet',
        }),
    ])

    # Venues
    venues = find_folder(items, lambda f: 'Venue' in f.get('name', '') or 'Discovery' in f.get('name', ''))
    if venues:
        upsert_by_name(venues, [
            json_body('Report Venue', 'POST', 'venues/{{venue_slug}}/report', {
                'reason': 'fake_venue',
                'description': 'الملعب غير موجود',
            }),
        ])

    # Reviews — try to find Review folder, else add to Promotions & Reviews
    reviews = find_folder(items, lambda f: 'Review' in f.get('name', ''))
    if reviews:
        upsert_by_name(reviews, [
            json_body('Report Review', 'POST', 'reviews/{{review_id}}/report', {
                'reason': 'offensive_language',
                'description': 'يحتوي على ألفاظ مسيئة',
            }),
        ])

    # Support
    support = find_folder(items, lambda f: 'Support' in f.get('name', '') or 'Help' in f.get('name', ''))
    if not support:
        # find Notifications & Settings folder which has support nested inside, or create new
        support = {'name': '13. Support & Help', 'item': []}
        items.append(support)
    upsert_by_name(support, [
        get_req('Ticket Details', 'support/tickets/{{ticket_id}}'),
        json_body('Reply to Ticket', 'POST', 'support/tickets/{{ticket_id}}/reply', {
            'message': 'حصلت المشكلة لما حاولت أدفع',
            'attachments': [],
        }),
    ])

    # Variables
    new_vars = [
        {'key': 'booking_id', 'value': '1', 'type': 'default'},
        {'key': 'venue_slug', 'value': 'damascus-sports-arena', 'type': 'default'},
        {'key': 'review_id', 'value': '1', 'type': 'default'},
        {'key': 'ticket_id', 'value': '1', 'type': 'default'},
        {'key': 'refund_request_id', 'value': '', 'type': 'default'},
    ]
    collection.setdefault('variable', [])
    existing_keys = {v.get('key') for v in collection['variable']}
    for v in new_vars:
        if v['key'] not in existing_keys:
            collection['variable'].append(v)

    with COLLECTION_PATH.open('w', encoding='utf-8') as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    after = count(collection['item'])
    print(f'Endpoints: {before} → {after} (+{after - before})')
    print(f'Backup: {backup.name}')


if __name__ == '__main__':
    main()
