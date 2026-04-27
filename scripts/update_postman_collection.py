#!/usr/bin/env python3
"""Update Postman Collection with Phase 11 (Wallet top-up) and Phase 12 (Football) endpoints.

Idempotent: re-running will not create duplicates.
"""
import json
import shutil
from datetime import datetime
from pathlib import Path

COLLECTION_PATH = Path('docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json')


# --------------------- helpers ---------------------

def _build_url(path, query_params=None):
    parts = path.split('/')
    url = {
        'raw': '{{base_url}}/' + path,
        'host': ['{{base_url}}'],
        'path': parts,
    }
    if query_params:
        url['query'] = query_params
        url['raw'] += '?' + '&'.join(f"{p['key']}={p['value']}" for p in query_params)
    return url


def get_request(name, path, *, auth_optional=False, query=None, description=None):
    req = {'method': 'GET', 'header': [], 'url': _build_url(path, query)}
    if auth_optional:
        req['auth'] = {'type': 'noauth'}
    if description:
        req['description'] = description
    return {'name': name, 'request': req}


def json_body_request(name, method, path, body, *, description=None):
    req = {
        'method': method,
        'header': [{'key': 'Content-Type', 'value': 'application/json'}],
        'body': {'mode': 'raw', 'raw': json.dumps(body, indent=2, ensure_ascii=False)},
        'url': _build_url(path),
    }
    if description:
        req['description'] = description
    return {'name': name, 'request': req}


def delete_request(name, path):
    return {
        'name': name,
        'request': {'method': 'DELETE', 'header': [], 'url': _build_url(path)},
    }


def post_no_body(name, path):
    return {
        'name': name,
        'request': {'method': 'POST', 'header': [], 'url': _build_url(path)},
    }


# --------------------- Phase 11: Wallet ---------------------

def phase11_topup_initiate():
    return {
        'name': 'Initiate Top-up (Real Payment)',
        'request': {
            'method': 'POST',
            'header': [
                {'key': 'Content-Type', 'value': 'application/json'},
                {'key': 'Accept', 'value': 'application/json'},
            ],
            'body': {
                'mode': 'raw',
                'raw': json.dumps(
                    {'amount': 50000, 'method': 'syriatel', 'phone': '{{test_phone}}'},
                    indent=2,
                ),
            },
            'url': _build_url('wallet/topup'),
            'description': (
                "Initiate wallet top-up via Syriatel/MTN/Bank.\n\n"
                "**Body:**\n- amount: integer (min 5000, max 1000000 SYP)\n"
                "- method: 'syriatel' | 'mtn' | 'bank'\n"
                "- phone: required for syriatel/mtn\n\n"
                "**Bonus tiers:** ≥500K → +10%, ≥200K → +5%, ≥50K → +2%"
            ),
        },
        'event': [
            {
                'listen': 'test',
                'script': {
                    'type': 'text/javascript',
                    'exec': [
                        "if (pm.response.code === 200) {",
                        "  const data = pm.response.json().data;",
                        "  if (data && data.payment_id) {",
                        "    pm.environment.set('wallet_topup_payment_id', data.payment_id);",
                        "  }",
                        "}",
                    ],
                },
            }
        ],
    }


def phase11_endpoints():
    return [
        phase11_topup_initiate(),
        json_body_request(
            'Verify Top-up OTP',
            'POST',
            'wallet/topup/verify',
            {'payment_id': '{{wallet_topup_payment_id}}', 'otp': '{{test_otp}}'},
            description='Verify OTP for Syriatel/MTN top-up. On success, wallet credited (amount + bonus).',
        ),
        json_body_request(
            'Resend Top-up OTP',
            'POST',
            'wallet/topup/resend-otp',
            {'payment_id': '{{wallet_topup_payment_id}}'},
        ),
        get_request(
            'Get Top-up Status',
            'wallet/topup/status/{{wallet_topup_payment_id}}',
            description='Status: pending | completed | failed | cancelled',
        ),
        post_no_body('Cancel Top-up', 'wallet/topup/cancel/{{wallet_topup_payment_id}}'),
    ]


# --------------------- Phase 12: Football sub-folders ---------------------

def sub_schedule():
    return {
        'name': '12.1 Schedule & Fixtures',
        'item': [
            get_request("Today's Matches (All)", 'football/matches/today', auth_optional=True),
            get_request("Today's Matches (My Favorites)", 'football/matches/today/favorites'),
            get_request('Upcoming Matches', 'football/matches/upcoming',
                        auth_optional=True, query=[{'key': 'days', 'value': '7'}]),
            get_request('Upcoming Matches (My Favorites)', 'football/matches/upcoming/favorites',
                        query=[{'key': 'days', 'value': '7'}]),
            get_request("Yesterday's Results", 'football/matches/yesterday', auth_optional=True),
            get_request('Match Details', 'football/matches/{{match_external_id}}', auth_optional=True),
        ],
    }


def sub_livescore():
    return {
        'name': '12.2 Live Score Layer',
        'item': [
            get_request('Live Matches (All)', 'football/live/matches', auth_optional=True,
                        description='Cache: 30s. Returns live fixtures with minute, score, events.'),
            get_request('Live Matches (My Favorites)', 'football/live/matches/favorites'),
            get_request('Live Match Details', 'football/live/matches/{{fixture_id}}', auth_optional=True),
            get_request('Live Match Events', 'football/live/matches/{{fixture_id}}/events',
                        auth_optional=True, description='Goals, cards, substitutions.'),
            get_request('Live Match Statistics', 'football/live/matches/{{fixture_id}}/statistics',
                        auth_optional=True),
            get_request('Live Match Lineups', 'football/live/matches/{{fixture_id}}/lineups',
                        auth_optional=True),
            get_request('API Quota Status (Admin)', 'football/live/quota-status',
                        description='Admin only. Remaining API-Sports quota across rotated keys.'),
        ],
    }


def sub_leagues():
    return {
        'name': '12.3 Leagues / Competitions',
        'item': [
            get_request('List Leagues', 'football/leagues', auth_optional=True),
            get_request('League Details', 'football/leagues/{{league_code}}', auth_optional=True),
            get_request('League Standings', 'football/leagues/{{league_code}}/standings',
                        auth_optional=True,
                        description='Cache: 1h. Codes: PL, PD, CL, BL1, SA, FL1, DED, PPL, BSA, ELC.'),
            get_request('League Top Scorers', 'football/leagues/{{league_code}}/scorers',
                        auth_optional=True, query=[{'key': 'limit', 'value': '10'}]),
            get_request('League Matches', 'football/leagues/{{league_code}}/matches', auth_optional=True),
            get_request('League Schedule', 'football/leagues/{{league_code}}/schedule', auth_optional=True),
        ],
    }


def sub_teams():
    return {
        'name': '12.4 Teams',
        'item': [
            get_request('Search Teams', 'football/teams/search', auth_optional=True,
                        query=[{'key': 'q', 'value': 'ريال'}],
                        description='Search by name (Arabic or English).'),
            get_request('Popular Teams', 'football/teams/popular', auth_optional=True),
            get_request('Team Details', 'football/teams/{{team_id}}', auth_optional=True),
            get_request('Team Recent Matches', 'football/teams/{{team_id}}/matches/recent', auth_optional=True),
            get_request('Team Upcoming Matches', 'football/teams/{{team_id}}/matches/upcoming', auth_optional=True),
            get_request('Team Squad', 'football/teams/{{team_id}}/squad', auth_optional=True),
            get_request('Teams by League', 'football/leagues/{{league_code}}/teams', auth_optional=True),
        ],
    }


def sub_favorite_teams():
    return {
        'name': '12.5 Favorite Teams (Auth)',
        'item': [
            get_request('My Favorite Teams', 'football/favorites/teams'),
            json_body_request(
                'Add Favorite Team',
                'POST',
                'football/favorites/teams',
                {
                    'team_id': '{{team_id}}',
                    'notify_matches': True,
                    'notify_goals': False,
                    'notify_results': True,
                },
            ),
            delete_request('Remove Favorite Team', 'football/favorites/teams/{{team_id}}'),
            json_body_request(
                'Reorder Favorite Teams',
                'PUT',
                'football/favorites/teams/reorder',
                {'team_ids': [3, 1, 2, 4]},
            ),
            get_request('Favorite Teams Today', 'football/favorites/teams/matches/today'),
            get_request('Favorite Teams Upcoming', 'football/favorites/teams/matches/upcoming',
                        query=[{'key': 'days', 'value': '7'}]),
        ],
    }


def sub_followed_leagues():
    return {
        'name': '12.6 Followed Leagues (Auth)',
        'item': [
            get_request('My Followed Leagues', 'football/favorites/leagues'),
            json_body_request(
                'Follow League',
                'POST',
                'football/favorites/leagues',
                {'league_code': 'PL', 'notify_matches': True},
            ),
            delete_request('Unfollow League', 'football/favorites/leagues/{{league_id}}'),
            json_body_request(
                'Reorder Followed Leagues',
                'PUT',
                'football/favorites/leagues/reorder',
                {'league_ids': [3, 1, 2]},
            ),
            get_request('Followed Leagues Matches', 'football/favorites/leagues/matches'),
        ],
    }


def sub_notifications():
    return {
        'name': '12.7 Match Notifications Settings (Auth)',
        'item': [
            get_request('Get Notification Settings', 'football/notifications/settings'),
            json_body_request(
                'Update All Settings',
                'PUT',
                'football/notifications/settings',
                {
                    'match_reminders_enabled': True,
                    'reminder_minutes_before': 60,
                    'second_reminder_enabled': True,
                    'second_reminder_minutes_before': 15,
                    'goal_notifications_enabled': True,
                    'result_notifications_enabled': True,
                    'daily_summary_enabled': True,
                    'daily_summary_time': '09:00',
                    'quiet_hours_enabled': True,
                    'quiet_hours_start': '23:00',
                    'quiet_hours_end': '07:00',
                },
            ),
            json_body_request(
                'Update Reminders',
                'PUT',
                'football/notifications/settings/reminders',
                {'match_reminders_enabled': True, 'reminder_minutes_before': 60},
            ),
            json_body_request(
                'Toggle Goal Notifications',
                'PUT',
                'football/notifications/settings/goals',
                {'goal_notifications_enabled': True},
            ),
            json_body_request(
                'Toggle Result Notifications',
                'PUT',
                'football/notifications/settings/results',
                {'result_notifications_enabled': True, 'daily_summary_enabled': True,
                 'daily_summary_time': '09:00'},
            ),
            json_body_request(
                'Update Quiet Hours',
                'PUT',
                'football/notifications/settings/quiet-hours',
                {'quiet_hours_enabled': True, 'quiet_hours_start': '23:00', 'quiet_hours_end': '07:00'},
            ),
        ],
    }


def football_folder():
    return {
        'name': '12. Football Matches & Favorites',
        'description': (
            'Football matches schedule, livescore, favorites, and notifications.\n\n'
            '**APIs Used:**\n'
            '- football-data.org (schedules, standings, teams)\n'
            '- API-Sports (livescore - 15 second updates)\n\n'
            'Auth-required endpoints inherit the collection-level Bearer token.'
        ),
        'item': [
            sub_schedule(),
            sub_livescore(),
            sub_leagues(),
            sub_teams(),
            sub_favorite_teams(),
            sub_followed_leagues(),
            sub_notifications(),
        ],
    }


# --------------------- merge logic ---------------------

def find_folder(items, predicate):
    for it in items:
        if 'item' in it and predicate(it):
            return it
    return None


def upsert_endpoints_by_name(folder, endpoints):
    """Append endpoints to folder, replacing any existing item with the same name."""
    existing_index = {it.get('name'): i for i, it in enumerate(folder.get('item', []))}
    for ep in endpoints:
        if ep['name'] in existing_index:
            folder['item'][existing_index[ep['name']]] = ep
        else:
            folder['item'].append(ep)


def upsert_wallet_folder(collection):
    items = collection['item']
    wallet = find_folder(items, lambda f: 'wallet' in f.get('name', '').lower()
                                          or 'محفظة' in f.get('name', ''))
    if not wallet:
        wallet = {
            'name': '10. Wallet & Credits',
            'description': 'Wallet balance, top-up flow (Phase 11), credits redemption, and withdrawals.',
            'item': [],
        }
        items.append(wallet)
        print('  + Created new folder: 10. Wallet & Credits')
    else:
        print(f"  ~ Found existing wallet folder: {wallet['name']}")
    upsert_endpoints_by_name(wallet, phase11_endpoints())


def upsert_football_folder(collection):
    items = collection['item']
    existing = find_folder(items, lambda f: f.get('name', '').startswith('12.')
                                            or 'football' in f.get('name', '').lower())
    new_folder = football_folder()
    if existing:
        existing['name'] = new_folder['name']
        existing['description'] = new_folder['description']
        existing['item'] = new_folder['item']
        print(f"  ~ Replaced existing football folder content")
    else:
        items.append(new_folder)
        print('  + Added new folder: 12. Football Matches & Favorites')


def upsert_variables(collection):
    new_vars = [
        {'key': 'wallet_topup_payment_id', 'value': '', 'type': 'default'},
        {'key': 'fixture_id', 'value': '1234567', 'type': 'default'},
        {'key': 'team_id', 'value': '1', 'type': 'default'},
        {'key': 'team_external_id', 'value': '86', 'type': 'default'},
        {'key': 'league_id', 'value': '1', 'type': 'default'},
        {'key': 'league_code', 'value': 'PL', 'type': 'default'},
        {'key': 'match_external_id', 'value': 'match_12345', 'type': 'default'},
    ]
    collection.setdefault('variable', [])
    existing_keys = {v.get('key') for v in collection['variable']}
    added = 0
    for var in new_vars:
        if var['key'] not in existing_keys:
            collection['variable'].append(var)
            added += 1
    print(f'  + {added} new variables added')


def count_endpoints(items):
    total = 0
    for it in items:
        if 'request' in it:
            total += 1
        if 'item' in it:
            total += count_endpoints(it['item'])
    return total


# --------------------- main ---------------------

def main():
    print(f'📂 Reading {COLLECTION_PATH}...')
    with COLLECTION_PATH.open('r', encoding='utf-8') as f:
        collection = json.load(f)

    before_count = count_endpoints(collection['item'])
    print(f'   Endpoints before: {before_count}')

    backup_path = COLLECTION_PATH.with_name(
        COLLECTION_PATH.stem + f'.backup_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json'
    )
    shutil.copy(COLLECTION_PATH, backup_path)
    print(f'✅ Backup: {backup_path.name}')

    print('🔧 Updating Wallet folder (Phase 11)...')
    upsert_wallet_folder(collection)

    print('🔧 Adding Football folder (Phase 12)...')
    upsert_football_folder(collection)

    print('🔧 Adding variables...')
    upsert_variables(collection)

    collection['info']['description'] = (
        'Complete Postman collection for Daq Ehjezly Mobile API v1.\n\n'
        '**Phases:**\n'
        '- Phases 1-10: Core features (auth, venues, bookings, payments, wallet, etc.)\n'
        '- Phase 11: Wallet top-up payment integration (Syriatel / MTN / Bank)\n'
        '- Phase 12: Football Matches & Favorites + Livescore (43 endpoints)\n\n'
        f'Last updated: {datetime.now().strftime("%Y-%m-%d")}'
    )

    with COLLECTION_PATH.open('w', encoding='utf-8') as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    after_count = count_endpoints(collection['item'])
    print(f'✅ Saved. Endpoints after: {after_count} (delta: +{after_count - before_count})')


if __name__ == '__main__':
    main()
