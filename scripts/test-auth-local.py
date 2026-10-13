#!/usr/bin/env python3
"""Real MySQL + Identity + Gateway integration; one owned DB/account, always cleaned up."""
import json
import secrets
import os
from pathlib import Path
import signal
import socket
import subprocess
import tempfile
import time
import urllib.request
import urllib.error

ROOT = Path(__file__).resolve().parents[1]
IDENTITY = ROOT / 'services/identity-service'
GATEWAY = ROOT / 'gateway/api-gateway'
WEB = ROOT / 'apps/web'
processes = []
credentials = None
service_key = secrets.token_hex(32)
storage = tempfile.TemporaryDirectory(prefix="identity-auth-storage-")
for folder in ["logs", "framework/cache/data", "framework/sessions", "framework/views"]:
    (Path(storage.name)/folder).mkdir(parents=True, exist_ok=True)

def command(args, cwd=ROOT, env=None):
    result = subprocess.run(args, cwd=cwd, env=env, capture_output=True, text=True)
    if result.returncode:
        # SQL errors may contain payloads: don't dump raw output/credentials.
        raise RuntimeError(f'{args[0]} {args[1]} failed (exit {result.returncode}); inspect locally')
    return result.stdout

def request(path, data=None, token=None, expected=200):
    headers={'Accept':'application/json','Content-Type':'application/json','X-Request-Id':'auth-integration'}
    if token: headers['Authorization']='Bearer '+token
    req=urllib.request.Request('http://127.0.0.1:18080/api/v1'+path,
        data=json.dumps(data).encode() if data is not None else None,headers=headers)
    try: response=urllib.request.urlopen(req,timeout=8)
    except urllib.error.HTTPError as error: response=error
    with response:
        status=response.status
        raw=response.read()
        assert status==expected, f'{path}: expected {expected}, received {status}'
        return json.loads(raw) if raw else None

def start(args,cwd,env,port,health):
    with socket.socket() as probe:probe.bind(('127.0.0.1',port))
    log=tempfile.TemporaryFile()
    process=subprocess.Popen(args,cwd=cwd,env=env,stdout=log,stderr=log,start_new_session=True)
    processes.append((process,log))
    deadline=time.monotonic()+15
    while time.monotonic()<deadline:
        if process.poll() is not None:raise RuntimeError(f'Process on {port} exited')
        try:
            with urllib.request.urlopen(f'http://127.0.0.1:{port}{health}',timeout=1) as response:
                if response.status==200:return
        except OSError:time.sleep(.2)
    raise RuntimeError(f'Process on {port} did not start')

try:
    if (IDENTITY/'bootstrap/cache/config.php').exists():raise RuntimeError('Refusing cached Identity config')
    credentials=json.loads(command(['php','scripts/auth-test-db.php','create']))
    env=dict(os.environ,**credentials,APP_ENV='testing',APP_DEBUG='false',BCRYPT_ROUNDS='4',
        APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',CACHE_STORE='file',PHP_CLI_SERVER_WORKERS='1',LARAVEL_STORAGE_PATH=storage.name,IDENTITY_GATEWAY_KEY=service_key)
    # Separate process boots directly against least-privilege test credentials: no switched schema cache.
    command(['php','artisan','migrate','--force'],IDENTITY,env)
    command(['php','artisan','migrate','--force'],IDENTITY,env)
    command(['php','artisan','db:seed','--force'],IDENTITY,env)
    print('PASS isolated MySQL migrations + repeat migrate + seed',flush=True)
    start(['php','artisan','serve','--host=127.0.0.1','--port=18081','--no-reload'],IDENTITY,env,18081,'/api/v1/health')
    gateway_env=dict(os.environ,PORT='18080',HOST='127.0.0.1',IDENTITY_SERVICE_URL='http://127.0.0.1:18081',IDENTITY_GATEWAY_KEY=service_key)
    start(['node','dist/main.js'],GATEWAY,gateway_env,18080,'/api/health')
    web_env=dict(os.environ,VITE_GATEWAY_PROXY_TARGET='http://127.0.0.1:18080')
    start(['npm','run','dev','--','--host','127.0.0.1','--port','15173','--strictPort'],WEB,web_env,15173,'/')
    with urllib.request.urlopen('http://127.0.0.1:15173/',timeout=8) as response:
        assert response.status == 200 and '<div id="app"></div>' in response.read().decode()
    print('PASS Vue dev server with Gateway proxy target',flush=True)
    request('/me',expected=401)
    request('/me',token='a'*64,expected=401)
    request('/auth/login',{'email':'bad'},expected=422)
    login={'email':'admin@demo.internal','password':'ChangeMe!2026'}
    session=request('/auth/login',login);a=session['access_token']
    assert session['user']['must_change_password'] is True
    b=request('/auth/login',login)['access_token']
    request('/me',token=a,expected=403)
    request('/auth/session',token=a)
    new='New!Password2026'
    request('/auth/password',{'current_password':login['password'],'password':new,'password_confirmation':new},a)
    request('/me',token=a,expected=401);request('/me',token=b,expected=401)
    request('/auth/login',login,expected=401)
    login['password']=new
    a=request('/auth/login',login)['access_token'];b=request('/auth/login',login)['access_token']
    assert request('/me',token=a)['user']['must_change_password'] is False
    request('/auth/logout',{},a,204);request('/me',token=a,expected=401)
    request('/me',token=b)
    c=request('/auth/login',login)['access_token']
    request('/auth/logout-all',{},b,204)
    request('/me',token=b,expected=401);request('/me',token=c,expected=401)
    print('PASS Gateway -> Identity: first password change, protected me, old-password denial, single/all-session logout',flush=True)
    for _ in range(10):request('/auth/login',{'email':'member@demo.internal','password':'wrong'},expected=401)
    request('/auth/login',{'email':'member@demo.internal','password':'ChangeMe!2026'},expected=403)
    print('PASS MySQL: lock after ten failed attempts',flush=True)
    # Stop only our Identity process, then verify fail-closed behavior at Gateway.
    process,_=processes[0];os.killpg(process.pid,signal.SIGTERM);process.wait(timeout=5)
    request('/me',token='a'*64,expected=502)
    print('PASS Gateway fails closed when Identity is unavailable',flush=True)
finally:
    for process,log in reversed(processes):
        if process.poll() is None:
            os.killpg(process.pid,signal.SIGTERM)
            try:process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(process.pid,signal.SIGKILL);process.wait()
        log.close()
    storage.cleanup()
    if credentials:
        result=subprocess.run(['php','scripts/auth-test-db.php','cleanup'],cwd=ROOT,input=credentials['DB_PASSWORD'],capture_output=True,text=True)
        if result.returncode:
            raise RuntimeError('Cleanup failed: identity_auth_test needs inspection; no other database was targeted')
        print(result.stdout.strip(),flush=True)
