#!/usr/bin/env python3
"""Read-only local inventory. Never print credential values or mutate services."""
import json
import shutil
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SAFE_KEYS = {"DB_CONNECTION", "DB_HOST", "DB_PORT", "DB_DATABASE", "REDIS_HOST", "REDIS_PORT"}

for binary, args in [("php", ["-v"]), ("composer", ["--version"]), ("node", ["--version"]), ("npm", ["--version"]), ("python3", ["--version"])]:
    executable = shutil.which(binary)
    if not executable:
        print(f"MISSING {binary}")
        continue
    result = subprocess.run([executable, *args], capture_output=True, text=True)
    first = (result.stdout or result.stderr).splitlines()
    print(f"{binary}: {first[0] if first else 'no version output'}")

for service in sorted((ROOT / "services").glob("*-service")):
    print(f"\n[{service.name}]")
    print(f"models={len(list((service / 'app/Models').glob('*.php')))} migrations={len(list((service / 'database/migrations').glob('*.php')))}")
    env = service / ".env"
    print(f"local env: {'present' if env.exists() else 'missing'}")
    if env.exists():
        for line in env.read_text().splitlines():
            key, separator, value = line.partition("=")
            if separator and key.strip() in SAFE_KEYS:
                print(f"{key.strip()}={value.strip()}")
    if (service / "vendor/autoload.php").exists() and shutil.which("composer"):
        result = subprocess.run(["composer", "check-platform-reqs", "--format=json"], cwd=service, capture_output=True, text=True)
        try:
            failures = [row for row in json.loads(result.stdout) if row['status'] != 'success']
            print("platform: " + ("PASS" if result.returncode == 0 else "FAIL"))
            for row in failures:
                print(f"  {row['name']}: installed={row.get('version')}; {row.get('failed_requirement')}")
        except (ValueError, TypeError, KeyError):
            print("platform: could not parse Composer result; inspect manually")
    elif (service / "composer.json").exists():
        print("platform: unavailable (Composer or vendor missing)")
print("\nNo database or service was changed. Health and authenticated DB checks must be run separately.")
