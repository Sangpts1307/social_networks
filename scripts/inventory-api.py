#!/usr/bin/env python3
"""Inventory real routes. Does not infer missing endpoints from documentation."""
from pathlib import Path
import json
import re
import subprocess

root = Path(__file__).resolve().parents[1]
out = root / 'docs/api'
out.mkdir(parents=True, exist_ok=True)
services = {}
for domain in ['identity', 'social', 'learning', 'chat', 'notification']:
    result = subprocess.run(['php', 'artisan', 'route:list', '--path=api/v1', '--json'], cwd=root / f'services/{domain}-service', check=True, capture_output=True, text=True)
    services[domain] = json.loads(result.stdout)
gateway = []
for file in sorted((root / 'gateway/api-gateway/src').rglob('*.controller.ts')):
    source = file.read_text()
    controller = re.search(r"@Controller\('([^']*)'\)", source)
    if not controller: continue
    for method, path in re.findall(r"@(Get|Post|Put|Patch|Delete)\('([^']*)'\)", source):
        public = '/api/' + controller[1] + ('/' + path if path else '')
        gateway.append({'method': method.upper(), 'path': re.sub(r':([A-Za-z0-9_]+)', r'{\1}', public), 'source': str(file.relative_to(root))})
keys = [(r['method'], r['path']) for r in gateway]
if len(keys) != len(set(keys)): raise SystemExit('Duplicate Gateway routes found.')
(root / 'docs/api/route-inventory.json').write_text(json.dumps({'generated_from': 'Laravel route:list and NestJS controller decorators', 'services': services, 'gateway': gateway}, ensure_ascii=False, indent=2) + '\n')
lines = ['# Kiểm kê route thực tế', '', 'Sinh bằng `python3 scripts/inventory-api.py`. HEAD được tính cùng GET. Số route không đồng nghĩa đã nghiệm thu nghiệp vụ.', '', '| Thành phần | Số REST operation |', '|---|---:|']
for name, routes in services.items(): lines.append(f'| {name} | {len(routes)} |')
lines += [f'| Gateway | {len(gateway)} |', '', '## Gateway', '', '| Method | Path | Source |', '|---|---|---|']
for route in gateway: lines.append(f"| {route['method']} | `{route['path']}` | `{route['source']}` |")
(out / 'ROUTE_INVENTORY.md').write_text('\n'.join(lines) + '\n')
print(json.dumps({'services': {name: len(routes) for name, routes in services.items()}, 'gateway': len(gateway)}))
