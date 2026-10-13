#!/usr/bin/env python3
"""Real local Learning + Gateway + Vue integration using isolated MySQL databases only."""
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
    call = urllib.request.Request('http://127.0.0.1:19180/api/v1' + path,
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
    command(['php', 'artisan', 'migrate', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'migrate', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'db:seed', '--force'], LEARNING, learning_env)
    command(['php', 'artisan', 'db:seed', '--force'], LEARNING, learning_env)
    print('PASS isolated MySQL Learning migrations + repeat seed', flush=True)
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', '--port=19181', '--no-reload'], IDENTITY, identity_env, 19181, '/api/v1/health')
    start(['php', 'artisan', 'serve', '--host=127.0.0.1', '--port=19183', '--no-reload'], LEARNING, learning_env, 19183, '/api/v1/health')
    gateway_env = dict(os.environ, PORT='19180', HOST='127.0.0.1', IDENTITY_SERVICE_URL='http://127.0.0.1:19181',
        LEARNING_SERVICE_URL='http://127.0.0.1:19183', IDENTITY_GATEWAY_KEY=identity_key, LEARNING_GATEWAY_KEY=learning_key)
    start(['node', 'dist/main.js'], GATEWAY, gateway_env, 19180, '/api/health')
    web_env = dict(os.environ, VITE_GATEWAY_PROXY_TARGET='http://127.0.0.1:19180')
    start(['npm', 'run', 'dev', '--', '--host', '127.0.0.1', '--port', '19174', '--strictPort'], WEB, web_env, 19174, '/')
    with urllib.request.urlopen('http://127.0.0.1:19174/', timeout=8) as response:
        assert response.status == 200 and '<div id="app"></div>' in response.read().decode()
    print('PASS Vue dev server with Learning Gateway proxy target', flush=True)
    request('/learning/topics', expected=401)
    login = {'email': 'admin@gmail.com', 'password': '12345678'}
    token = request('/auth/login', login)['access_token']
    topics = request('/learning/topics', token=token)
    items = request('/learning/knowledge-items', token=token)
    assert topics['data'][0]['code'] == 'engineering'
    assert items['data'][0]['title'] == 'Khởi đầu với Knowledge Graph'
    assert items['data'][0]['current_version_id']
    print('PASS Gateway session guard -> Learning tenant-scoped taxonomy and published Knowledge Item', flush=True)
    topic = topics['data'][0]
    question = request('/learning/questions', {
        'topic_id': topic['id'], 'category_id': topic['categories'][0]['id'],
        'title': 'Projection graph có cần provenance không?', 'body_markdown': 'Cần source version nào?',
    }, token, 201)['data']
    answer = request(f"/learning/questions/{question['id']}/answers", {
        'body_markdown': 'Mỗi relation phải có source_id và source_version.',
    }, token, 201)['data']
    request(f"/learning/answers/{answer['id']}/votes", {'value': 1}, token, method='PUT')
    detail = request(f"/learning/questions/{question['id']}", token=token)['data']
    assert detail['best_answer_id'] == answer['id'] and detail['answers'][0]['score'] == 1
    print('PASS Gateway Q&A write/read, derived best-answer and provenance-ready source fields', flush=True)
    admin_topic = request('/admin/learning/topics', {'code': 'operations', 'name': 'Vận hành'}, token, 201)['data']
    admin_category = request('/admin/learning/categories', {'topic_id': admin_topic['id'], 'code': 'runbook', 'name': 'Runbook'}, token, 201)['data']
    document = request('/admin/learning/documents', {
        'topic_id': admin_topic['id'], 'category_id': admin_category['id'], 'title': 'Quy trình triển khai', 'summary': 'Phiên bản vận hành',
    }, token, 201)['data']
    course = request('/admin/learning/courses', {'topic_id': admin_topic['id'], 'title': 'Nhập môn vận hành'}, token, 201)['data']
    request(f"/admin/learning/courses/{course['id']}/lifecycle", {'action': 'publish'}, token)
    assert any(item['id'] == course['id'] for item in request('/learning/courses', token=token)['data'])
    request(f"/admin/learning/documents/{document['id']}/lifecycle", {'action': 'publish'}, token)
    published = request('/learning/knowledge-items', token=token)['data']
    assert any(item['id'] == document['id'] for item in published)
    request(f"/admin/learning/questions/{question['id']}/moderation", {'action': 'hide', 'reason_code': 'test'}, token)
    request(f"/learning/questions/{question['id']}", token=token, expected=404)
    hidden = request('/admin/learning/questions?status=hidden', token=token)['data']
    assert any(item['id'] == question['id'] for item in hidden)
    print('PASS Gateway Learning admin taxonomy, document lifecycle and question moderation', flush=True)
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
