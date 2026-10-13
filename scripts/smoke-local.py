#!/usr/bin/env python3
"""Start isolated local processes, verify HTTP, and stop only processes we started."""
import json
import os
import signal
import subprocess
import tempfile
import time
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
APPS = [
    ('gateway', ROOT / 'gateway/api-gateway', ['node', 'dist/main.js'], 18080, '/api/health'),
    ('identity-service', ROOT / 'services/identity-service', ['php', 'artisan', 'serve', '--host=127.0.0.1', '--port=18081', '--no-reload'], 18081, '/api/v1/health'),
    ('web', ROOT / 'apps/web', ['npm', 'run', 'dev', '--', '--host', '127.0.0.1', '--port', '15173', '--strictPort'], 15173, '/'),
]
processes = []
try:
    for name, cwd, command, port, path in APPS:
        # Refuse to reuse an existing listener; do not stop anyone else's server.
        import socket
        with socket.socket() as probe:
            probe.bind(('127.0.0.1', port))
        env = dict(os.environ, PORT=str(port), HOST='127.0.0.1', PHP_CLI_SERVER_WORKERS='1')
        log = tempfile.TemporaryFile()
        process = subprocess.Popen(command, cwd=cwd, env=env, stdout=log, stderr=log, start_new_session=True)
        processes.append((process, log))
        deadline = time.monotonic() + 15
        last = None
        while time.monotonic() < deadline:
            if process.poll() is not None:
                raise RuntimeError(f'{name}: exited {process.returncode}; run the app directly for details')
            try:
                request = urllib.request.Request(f'http://127.0.0.1:{port}{path}', headers={'X-Request-Id': 'foundation-smoke-20260916'})
                with urllib.request.urlopen(request, timeout=1) as response:
                    body = response.read().decode()
                    assert response.status == 200
                    if name == 'web':
                        assert '<div id="app"></div>' in body
                    else:
                        data = json.loads(body)
                        assert data['status'] == 'ok'
                        assert data['service'] == ('api-gateway' if name == 'gateway' else name)
                    if name == 'gateway':
                        assert response.headers['X-Request-Id'] == 'foundation-smoke-20260916'
                print(f'PASS {name}: HTTP 200 on {port}{path}', flush=True)
                break
            except (OSError, ValueError) as error:
                last = type(error).__name__
                time.sleep(0.2)
        else:
            raise RuntimeError(f'{name}: no response within 15s ({last})')
    print('PASS local HTTP smoke; this is not a database/authentication/E2E test.')
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
