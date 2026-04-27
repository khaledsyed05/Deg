#!/usr/bin/env python3
"""Add Phase 14 (Geography + App Metadata) folders to the Postman collection.

Idempotent: re-running replaces existing Phase 14 folders rather than duplicating.
"""
import json
import shutil
from datetime import datetime
from pathlib import Path

COLLECTION_PATH = Path('docs/postman/DaqEhjezly_Mobile_API_v1_Complete.postman_collection.json')


def _url(path, query=None):
    parts = path.split('/')
    url = {'raw': '{{base_url}}/' + path, 'host': ['{{base_url}}'], 'path': parts}
    if query:
        url['query'] = query
        url['raw'] += '?' + '&'.join(f"{p['key']}={p['value']}" for p in query)
    return url


def get_req(name, path, *, query=None, headers=None, description=None):
    req = {'method': 'GET', 'header': headers or [], 'url': _url(path, query),
           'auth': {'type': 'noauth'}}
    if description:
        req['description'] = description
    return {'name': name, 'request': req}


def post_json(name, path, body, *, description=None):
    req = {
        'method': 'POST',
        'header': [{'key': 'Content-Type', 'value': 'application/json'}],
        'body': {'mode': 'raw', 'raw': json.dumps(body, indent=2, ensure_ascii=False)},
        'url': _url(path),
        'auth': {'type': 'noauth'},
    }
    if description:
        req['description'] = description
    return {'name': name, 'request': req}


def geography_folder():
    return {
        'name': '13. Geography & Locations',
        'description': 'Country/State/City lookups, nearest-city detection, and venue clustering for map view.',
        'item': [
            get_req('List Countries', 'geography/countries',
                    description='Visible countries only. Cache: 24h.'),
            get_req('States by Country', 'geography/countries/SY/states',
                    description='ISO2 country code in path.'),
            get_req('Cities by State', 'geography/states/1/cities'),
            get_req('Popular Cities', 'geography/cities/popular',
                    query=[{'key': 'limit', 'value': '10'}]),
            get_req('City Details', 'geography/cities/1'),
            post_json('Detect Nearest City', 'geography/detect',
                      {'latitude': 33.5138, 'longitude': 36.2765},
                      description='Returns nearest visible city using Haversine distance.'),
            get_req('Venue Clusters (Map)', 'geography/venues/clusters',
                    query=[
                        {'key': 'lat', 'value': '33.5138'},
                        {'key': 'lng', 'value': '36.2765'},
                        {'key': 'radius', 'value': '50'},
                        {'key': 'zoom', 'value': '12'},
                    ],
                    description='Cluster venue counts by city within radius (km).'),
        ],
    }


def app_metadata_folder():
    return {
        'name': '14. App Metadata & Config',
        'description': 'Force-update check, feature flags, maintenance window, public remote config, health probe.',
        'item': [
            get_req('Version Check', 'app/version',
                    headers=[
                        {'key': 'X-App-Platform', 'value': 'ios'},
                        {'key': 'X-App-Version', 'value': '1.0.0'},
                        {'key': 'X-App-Build', 'value': '100'},
                    ],
                    description='Send X-App-Platform (ios|android), X-App-Version, X-App-Build headers.'),
            {
                'name': 'Feature Flags',
                'request': {
                    'method': 'GET',
                    'header': [],
                    'url': _url('app/feature-flags'),
                    'description': 'Optional auth — logged-in users get personalised flags (user_ids whitelist + percentage rollout).',
                },
            },
            get_req('Maintenance Status', 'app/maintenance'),
            get_req('Public Config', 'app/config',
                    description='Returns only is_public=true entries. Cache: 1h.'),
            get_req('Health Check', 'app/health',
                    description='Reports DB, cache, storage status.'),
        ],
    }


def find_folder(items, predicate):
    for it in items:
        if 'item' in it and predicate(it):
            return it
    return None


def upsert_top_folder(collection, new_folder, prefix):
    items = collection['item']
    existing = find_folder(items, lambda f: f.get('name', '').startswith(prefix))
    if existing:
        existing['name'] = new_folder['name']
        existing['description'] = new_folder['description']
        existing['item'] = new_folder['item']
        print(f"  ~ Replaced existing folder: {new_folder['name']}")
    else:
        items.append(new_folder)
        print(f"  + Added new folder: {new_folder['name']}")


def upsert_variables(collection):
    new_vars = [
        {'key': 'country_iso2', 'value': 'SY', 'type': 'default'},
        {'key': 'state_id', 'value': '1', 'type': 'default'},
        {'key': 'city_id', 'value': '1', 'type': 'default'},
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


def main():
    print(f'📂 Reading {COLLECTION_PATH}...')
    with COLLECTION_PATH.open('r', encoding='utf-8') as f:
        collection = json.load(f)

    before = count_endpoints(collection['item'])
    print(f'   Endpoints before: {before}')

    backup = COLLECTION_PATH.with_name(
        COLLECTION_PATH.stem + f'.backup_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json'
    )
    shutil.copy(COLLECTION_PATH, backup)
    print(f'✅ Backup: {backup.name}')

    print('🔧 Adding Geography folder...')
    upsert_top_folder(collection, geography_folder(), '13.')

    print('🔧 Adding App Metadata folder...')
    upsert_top_folder(collection, app_metadata_folder(), '14.')

    print('🔧 Adding variables...')
    upsert_variables(collection)

    collection['info']['description'] = (
        'Complete Postman collection for Daq Ehjezly Mobile API v1.\n\n'
        '**Phases:**\n'
        '- Phases 1-10: Core features\n'
        '- Phase 11: Wallet top-up\n'
        '- Phase 12: Football matches & favorites\n'
        '- Phase 14: Geography & app metadata\n\n'
        f'Last updated: {datetime.now().strftime("%Y-%m-%d")}'
    )

    with COLLECTION_PATH.open('w', encoding='utf-8') as f:
        json.dump(collection, f, indent=2, ensure_ascii=False)

    after = count_endpoints(collection['item'])
    print(f'✅ Saved. Endpoints after: {after} (delta: +{after - before})')


if __name__ == '__main__':
    main()
