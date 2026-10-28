#!/usr/bin/env python3
"""Scoped authorization integration through Identity, Gateway and Learning using isolated MySQL databases."""
import json
import os
from pathlib import Path
import secrets
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
IDENTITY = ROOT / 'services/identity-service'
LEARNING = ROOT / 'services/learning-service'
GATEWAY = ROOT / 'gateway/api-gateway'
def unused_port():
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        return probe.getsockname()[1]

IDENTITY_PORT, LEARNING_PORT, GATEWAY_PORT = unused_port(), unused_port(), unused_port()
WEB = ROOT / 'apps/web'
processes = []
identity_credentials = None
learning_credentials = None
identity_key = secrets.token_hex(32)
learning_key = secrets.token_hex(32)
storage = tempfile.TemporaryDirectory(prefix='learning-foundation-storage-')
for folder in ['logs', 'framework/cache/data', 'framework/sessions', 'framework/views']:
    (Path(storage.name) / folder).mkdir(parents=True, exist_ok=True)


def command(args, cwd=ROOT, env=None):
    result = subprocess.run(args, cwd=cwd, env=env, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(f'{args[0]} {args[1]} failed (exit {result.returncode}); inspect locally')
    return result.stdout


def request(path, data=None, token=None, expected=200, method=None):
    headers = {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Request-Id': 'learning-integration'}
    if token:
        headers['Authorization'] = 'Bearer ' + token
    call = urllib.request.Request(f'http://127.0.0.1:{GATEWAY_PORT}/api/v1' + path,
        data=json.dumps(data).encode() if data is not None else None, headers=headers, method=method)
    try:
        response = urllib.request.urlopen(call, timeout=8)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        raw = response.read()
        assert response.status == expected, f'{path}: expected {expected}, received {response.status}'
        return json.loads(raw) if raw else None


def start(args, cwd, env, port, health):
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', port))
    log = tempfile.TemporaryFile()
    process = subprocess.Popen(args, cwd=cwd, env=env, stdout=log, stderr=log, start_new_session=True)
    processes.append((process, log))
    deadline = time.monotonic() + 15
    while time.monotonic() < deadline:
        if process.poll() is not None:
            raise RuntimeError(f'Process on {port} exited')
        try:
            with urllib.request.urlopen(f'http://127.0.0.1:{port}{health}', timeout=1) as response:
                if response.status == 200:
                    return
        except OSError:
            time.sleep(.2)
    raise RuntimeError(f'Process on {port} did not start')


try:
    if (IDENTITY / 'bootstrap/cache/config.php').exists() or (LEARNING / 'bootstrap/cache/config.php').exists():
        raise RuntimeError('Refusing cached Laravel configuration')
    identity_credentials = json.loads(command(['php', 'scripts/auth-test-db.php', 'create']))
    learning_credentials = json.loads(command(['php', 'scripts/learning-test-db.php', 'create']))
    shared = dict(os.environ, APP_ENV='testing', APP_DEBUG='false', APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        CACHE_STORE='file', PHP_CLI_SERVER_WORKERS='1', LARAVEL_STORAGE_PATH=storage.name)
    identity_env = dict(shared, **identity_credentials, BCRYPT_ROUNDS='4', IDENTITY_GATEWAY_KEY=identity_key)
    learning_env = dict(shared, **learning_credentials, LEARNING_GATEWAY_KEY=learning_key)
    command(['php', 'artisan', 'migrate', '--force'], IDENTITY, identity_env)
    command(['php', 'artisan', 'db:seed', '--force'], IDENTITY, identity_env)
    command(['php', 'artisan', 'db:seed', '--class=ScopedRbacExampleSeeder', '--force'], IDENTITY, identity_env)
    command(['php', 'artisan', 'db:seed', '--class=ScopedRbacExampleSeeder', '--force'], IDENTITY, identity_env)
    command(['php', 'artisan', 'migrate', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'migrate', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'db:seed', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'db:seed', '--force'], LEARNING, learning_env)
    print('PASS isolated MySQL Learning migrations + repeat seed', flush=True)
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', f'--port={IDENTITY_PORT}', '--no-reload'], IDENTITY, identity_env, IDENTITY_PORT, '/api/v1/health')
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', f'--port={LEARNING_PORT}', '--no-reload'], LEARNING, learning_env, LEARNING_PORT, '/api/v1/health')
    gateway_env = dict(os.environ, PORT=str(GATEWAY_PORT), HOST='127.0.0.1', IDENTITY_SERVICE_URL=f'http://127.0.0.1:{IDENTITY_PORT}',
        LEARNING_SERVICE_URL=f'http://127.0.0.1:{LEARNING_PORT}', IDENTITY_GATEWAY_KEY=identity_key, LEARNING_GATEWAY_KEY=learning_key)
    start(['node', 'dist/main.js'], GATEWAY, gateway_env, GATEWAY_PORT, '/api/health')
    admin_login = request('/auth/login', {'email': 'admin@gmail.com', 'password': '12345678'})
    admin = admin_login['access_token']
    users = request('/admin/users', token=admin)['data']
    leader = next(u for u in users if u['email'] == 'team_leader@gmail.com')
    inside = next(u for u in users if u['email'] == 'member@gmail.com')
    outside = next(u for u in users if u['email'] == 'hr_manager@gmail.com')
    units = request('/admin/org-units', token=admin)['data']
    knowledge = next(u['id'] for u in units if u['code'] == 'KNOWLEDGE')
    hq = next(u['id'] for u in units if u['code'] == 'HQ')
    roles = request('/admin/roles', token=admin)['data']
    manager = next(r['id'] for r in roles if r['code'] == 'department_manager')
    original = leader['roles'][0]
    request(f"/admin/users/{leader['id']}/role-assignments/{original['assignment_id']}", token=admin, method='DELETE', expected=204)
    for role_id in [original['id'], manager]:
        request(f"/admin/users/{leader['id']}/role-assignments", {'role_id': role_id, 'scope_type': 'org_unit', 'org_unit_id': knowledge, 'include_descendants': True}, admin, 201)
    login = request('/auth/login', {'email': leader['email'], 'password': '12345678'})
    actor = login['access_token']
    assert len(login['user']['authorization']['grants']) == 2
    visible = request('/admin/users', token=actor)['data']
    assert inside['id'] in [u['id'] for u in visible]
    assert outside['id'] not in [u['id'] for u in visible]
    request(f"/admin/users/{inside['id']}/status", {'status': 'suspended'}, actor, method='PATCH')
    request(f"/admin/users/{outside['id']}/status", {'status': 'suspended'}, actor, 404, method='PATCH')
    print('PASS scoped Identity list/update through Gateway', flush=True)
    topics = request('/learning/topics', token=actor)['data']
    document = request('/admin/learning/documents', {'topic_id': topics[0]['id'], 'title': 'Scoped integration', 'org_unit_id': knowledge}, actor, 201)['data']
    doc_id = document['id']
    request('/admin/learning/documents', {'topic_id': topics[0]['id'], 'title': 'Outside department', 'org_unit_id': hq}, actor, 403)
    request(f'/admin/learning/documents/{doc_id}', {'title': 'Allowed within scope'}, actor, method='PATCH')
    rules = [
        {'principal_type': 'department', 'principal_id': knowledge, 'effect': 'allow', 'access_level': 'view'},
        {'principal_type': 'user', 'principal_id': admin_login['user']['id'], 'effect': 'allow', 'access_level': 'manage'},
    ]
    request(f'/admin/learning/documents/{doc_id}/access-rules', {'rules': rules}, admin, method='PUT')
    assert doc_id in [d['id'] for d in request('/admin/learning/documents', token=actor)['data']]
    request(f'/admin/learning/documents/{doc_id}', {'title': 'View does not imply manage'}, actor, 404, method='PATCH')
    rules.append({'principal_type': 'user', 'principal_id': leader['id'], 'effect': 'deny', 'access_level': 'view'})
    request(f'/admin/learning/documents/{doc_id}/access-rules', {'rules': rules}, admin, method='PUT')
    assert doc_id not in [d['id'] for d in request('/admin/learning/documents', token=actor)['data']]
    print('PASS scoped document creation, restrictive ACL and explicit Deny through Gateway', flush=True)

finally:
    for process, log in reversed(processes):
        if process.poll() is None:
            os.killpg(process.pid, signal.SIGTERM)
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid, signal.SIGKILL)
                process.wait()
        log.close()
    storage.cleanup()
    if learning_credentials:
        result = subprocess.run(['php', 'scripts/learning-test-db.php', 'cleanup'], cwd=ROOT,
            input=learning_credentials['DB_PASSWORD'], capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError('Learning cleanup failed; learning_foundation_test needs inspection; no other database was targeted')
        print(result.stdout.strip(), flush=True)
    if identity_credentials:
        result = subprocess.run(['php', 'scripts/auth-test-db.php', 'cleanup'], cwd=ROOT,
            input=identity_credentials['DB_PASSWORD'], capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError('Identity cleanup failed; identity_auth_test needs inspection; no other database was targeted')
        print(result.stdout.strip(), flush=True)
