#!/usr/bin/env python3
"""Fill missing local service keys without printing secrets or changing configured keys."""
import argparse
from pathlib import Path
import re
import secrets

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--write', action='store_true', help='Write missing keys into existing ignored .env files')
args = parser.parse_args()
paths = {'gateway': root / 'gateway/api-gateway/.env'}
paths.update({name: root / f'services/{name}-service/.env' for name in ['identity', 'social', 'learning', 'chat', 'notification', 'ai-knowledge']})
contents = {name: path.read_text() if path.exists() else '' for name, path in paths.items()}
values = {}
for name, content in contents.items():
    values[name] = {}
    for line in content.splitlines():
        match = re.match(r'^\s*([A-Z][A-Z0-9_]*)=(.*)$', line)
        if match:
            values[name][match[1]] = match[2].strip().strip('"\'')
changes = {name: {} for name in paths}
pairs = [('SOCIAL_NOTIFICATION_KEY', ['social', 'notification']), ('LEARNING_NOTIFICATION_KEY', ['learning', 'notification']), ('CHAT_NOTIFICATION_KEY', ['chat', 'notification']), ('CHAT_GATEWAY_KEY', ['gateway', 'chat']), ('NOTIFICATION_GATEWAY_KEY', ['gateway', 'notification']),
         ('AI_GATEWAY_KEY', ['gateway', 'ai-knowledge']), ('CHAT_IDENTITY_KEY', ['chat', 'identity']),
         ('SOCIAL_IDENTITY_KEY', ['social', 'identity']), ('NOTIFICATION_IDENTITY_KEY', ['notification', 'identity']),
         ('LEARNING_GATEWAY_KEY', ['gateway', 'learning', 'ai-knowledge'])]
for key, owners in pairs:
    configured = {values[owner].get(key) for owner in owners if values[owner].get(key)}
    if len(configured) > 1:
        raise SystemExit(f'Conflicting {key} in {", ".join(owners)}; existing keys were preserved.')
    secret = next(iter(configured), secrets.token_hex(32))
    if len(secret) < 32:
        raise SystemExit(f'{key} must contain at least 32 characters; existing value was preserved.')
    for owner in owners:
        if not values[owner].get(key): changes[owner][key] = secret
for owner in ['social', 'learning', 'chat']:
    key = owner.upper() + '_SCANNER_KEY'
    if not values[owner].get(key): changes[owner][key] = secrets.token_hex(32)
for owner in ['social', 'chat', 'notification']:
    if not values[owner].get('IDENTITY_SERVICE_URL'): changes[owner]['IDENTITY_SERVICE_URL'] = values['gateway'].get('IDENTITY_SERVICE_URL', 'http://127.0.0.1:8004')
if not values['ai-knowledge'].get('LEARNING_SERVICE_URL'):
    changes['ai-knowledge']['LEARNING_SERVICE_URL'] = values['gateway'].get('LEARNING_SERVICE_URL', 'http://127.0.0.1:8005')
for owner, updates in changes.items():
    if not updates: continue
    if not paths[owner].exists():
        print(f'{owner}: .env is absent; copy .env.example first. No file created.')
        continue
    if args.write:
        content = contents[owner]
        for key, value in updates.items():
            pattern = re.compile(r'^\s*' + re.escape(key) + r'=.*$', re.M)
            if pattern.search(content): content = pattern.sub(f'{key}={value}', content)
            else: content += f'\n{key}={value}\n'
        paths[owner].write_text(content)
    print(f'{owner}: {len(updates)} missing settings {"written" if args.write else "would be written"}; no secrets displayed.')
