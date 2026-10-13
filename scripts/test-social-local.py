#!/usr/bin/env python3
"""Real local Identity + Social + Gateway integration using isolated MySQL databases only."""
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
SOCIAL = ROOT / 'services/social-service'
GATEWAY = ROOT / 'gateway/api-gateway'
processes = []
identity_credentials = None
social_credentials = None
identity_key = secrets.token_hex(32)
social_key = secrets.token_hex(32)
storage = tempfile.TemporaryDirectory(prefix='social-foundation-storage-')
for folder in ['logs', 'framework/cache/data', 'framework/sessions', 'framework/views']:
    (Path(storage.name) / folder).mkdir(parents=True, exist_ok=True)


def command(args, cwd=ROOT, env=None):
    result = subprocess.run(args, cwd=cwd, env=env, capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(f'{args[0]} {args[1]} failed (exit {result.returncode}); inspect locally')
    return result.stdout


def request(path, data=None, token=None, expected=200, method=None, extra_headers=None):
    headers = {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Request-Id': 'social-integration'}
    if extra_headers:
        headers.update(extra_headers)
    if token:
        headers['Authorization'] = 'Bearer ' + token
    call = urllib.request.Request('http://127.0.0.1:19280/api/v1' + path,
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
    if (IDENTITY / 'bootstrap/cache/config.php').exists() or (SOCIAL / 'bootstrap/cache/config.php').exists():
        raise RuntimeError('Refusing cached Laravel configuration')
    identity_credentials = json.loads(command(['php', 'scripts/auth-test-db.php', 'create']))
    social_credentials = json.loads(command(['php', 'scripts/social-test-db.php', 'create']))
    shared = dict(os.environ, APP_ENV='testing', APP_DEBUG='false', APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        CACHE_STORE='array', PHP_CLI_SERVER_WORKERS='4', LARAVEL_STORAGE_PATH=storage.name)
    identity_env = dict(shared, **identity_credentials, BCRYPT_ROUNDS='4', IDENTITY_GATEWAY_KEY=identity_key)
    social_env = dict(shared, **social_credentials, SOCIAL_GATEWAY_KEY=social_key)
    result = command(['php', 'artisan', 'test'], SOCIAL, social_env)
    if 'passed' not in result:
        raise RuntimeError('Social test results missing')
    print('PASS Social Feature suite against isolated MySQL', flush=True)
    for folder, environment in [(IDENTITY, identity_env), (SOCIAL, social_env)]:
        command(['php', 'artisan', 'migrate', '--force'], folder, environment)
        command(['php', 'artisan', 'migrate', '--force'], folder, environment)
        command(['php', 'artisan', 'db:seed', '--force'], folder, environment)
        command(['php', 'artisan', 'db:seed', '--force'], folder, environment)
    print('PASS isolated MySQL migrations and repeated seeds', flush=True)
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', '--port=19281', '--no-reload'], IDENTITY, identity_env, 19281, '/api/v1/health')
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', '--port=19282', '--no-reload'], SOCIAL, social_env, 19282, '/api/v1/health')
    gateway_env = dict(os.environ, PORT='19280', HOST='127.0.0.1', IDENTITY_SERVICE_URL='http://127.0.0.1:19281',
        SOCIAL_SERVICE_URL='http://127.0.0.1:19282', IDENTITY_GATEWAY_KEY=identity_key, SOCIAL_GATEWAY_KEY=social_key)
    start(['node', 'dist/main.js'], GATEWAY, gateway_env, 19280, '/api/health')
    request('/social/posts', expected=401)
    login = {'email': 'admin@gmail.com', 'password': '12345678'}
    token = request('/auth/login', login)['access_token']
    channels = request('/social/channels', token=token)['data']
    channel_id = channels[0]['id']
    headers = {'Idempotency-Key': 'social-integration-create', 'X-Authenticated-Organization-Id': '10000000-0000-4000-8000-000000000099', 'X-Authenticated-User-Id': '30000000-0000-4000-8000-000000000099'}
    payload = {'channel_id': channel_id, 'body_markdown': 'Bài viết tích hợp Social API'}
    post = request('/social/posts', payload, token, 201, extra_headers=headers)['data']
    replay = request('/social/posts', payload, token, 201, extra_headers=headers)['data']
    assert post == replay and post['author_user_id'] == '30000000-0000-4000-8000-000000000001'
    request('/social/posts', {**payload, 'body_markdown': 'Changed'}, token, 409, extra_headers=headers)
    print('PASS real session -> Gateway -> Social, spoofed identity headers ignored, create retry deduplicated', flush=True)
    post_id = post['id']
    detail_path = f'/social/posts/{post_id}'
    request(detail_path, {'body_markdown': 'Nội dung đã sửa'}, token, method='PATCH')
    comment = request(detail_path + '/comments', {'body_markdown': 'Bình luận đầu'}, token, 201)['data']
    request(detail_path + '/comments', {'body_markdown': 'Trả lời', 'parent_comment_id': comment['id']}, token, 201)
    request(detail_path + '/reaction', {'reaction_type': 'like'}, token, method='PUT')
    request(detail_path + '/reaction', {'reaction_type': 'love'}, token, method='PUT')
    request(detail_path + '/save', token=token, method='PUT')
    detail = request(detail_path, token=token)['data']
    assert detail['reaction_count'] == 1 and detail['comment_count'] == 2 and detail['is_saved'] is True
    assert detail['published_at'].endswith('Z')
    assert any(x['id'] == post_id for x in request('/social/posts?saved=1', token=token)['data'])
    request('/social/posts?limit=101', token=token, expected=422)
    page = request('/social/posts?limit=1', token=token)
    from urllib.parse import quote
    assert request('/social/posts?limit=1&cursor=' + quote(page['meta']['next_cursor'], safe=''), token=token)['data'][0]['id'] != page['data'][0]['id']
    print('PASS edit/feed/cursor/comments/reactions/private saves with UTC JSON contract', flush=True)
    # Requests sent simultaneously must converge on one resource and one current reaction.
    from concurrent.futures import ThreadPoolExecutor
    with ThreadPoolExecutor(max_workers=4) as pool:
        for round_id in range(5):
            created = list(pool.map(lambda _: request('/social/posts', {'channel_id': channel_id, 'body_markdown': 'Concurrent'}, token, 201, extra_headers={'Idempotency-Key': f'parallel-create-{round_id}'})['data']['id'], range(4)))
            assert len(set(created)) == 1
        list(pool.map(lambda _: request(detail_path + '/reaction', {'reaction_type': 'like'}, token, method='PUT'), range(4)))
    assert request(detail_path, token=token)['data']['reaction_count'] == 1
    print('PASS concurrent MySQL create retry and reaction uniqueness', flush=True)
    request('/social/posts', {'channel_id': channel_id, 'body_markdown': 'ế' * 40000}, token, 201)
    request('/social/posts', {'channel_id': channel_id, 'body_markdown': 'ế' * 50001}, token, 422)
    print('PASS Vietnamese Unicode body beyond 100 KB through Gateway and server length validation', flush=True)
    request(detail_path + '/reports', {'reason_code': 'spam'}, token, 201)
    request(f'/social/admin/posts/{post_id}/moderation', {'action': 'hide', 'reason_code': 'test'}, token)
    request(detail_path, token=token, expected=404)
    assert not any(x['id'] == post_id for x in request('/social/posts?saved=1', token=token)['data'])
    request(f'/social/admin/posts/{post_id}/moderation', {'action': 'restore', 'reason_code': 'reviewed'}, token)
    request(detail_path, token=token)
    request('/auth/logout', token=token, method='POST', expected=204)
    request('/social/posts', token=token, expected=401)
    print('PASS permission-backed moderation, hidden-content denial and revoked-session denial', flush=True)

except Exception:
    log_file = Path(storage.name) / 'logs/laravel.log'
    if log_file.exists():
        text = log_file.read_text(errors='replace')
        for marker in ['Deadlock', 'Lock wait timeout', 'SQLSTATE', 'Connection refused']:
            if marker in text:
                print('Integration diagnostic: ' + marker + ' found in isolated application log', flush=True)
    raise
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
    if social_credentials:
        result = subprocess.run(['php', 'scripts/social-test-db.php', 'cleanup'], cwd=ROOT,
            input=social_credentials['DB_PASSWORD'], capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError('Social cleanup failed; social_foundation_test needs inspection; no other database was targeted')
        print(result.stdout.strip(), flush=True)
    if identity_credentials:
        result = subprocess.run(['php', 'scripts/auth-test-db.php', 'cleanup'], cwd=ROOT,
            input=identity_credentials['DB_PASSWORD'], capture_output=True, text=True)
        if result.returncode:
            raise RuntimeError('Identity cleanup failed; identity_auth_test needs inspection; no other database was targeted')
        print(result.stdout.strip(), flush=True)
