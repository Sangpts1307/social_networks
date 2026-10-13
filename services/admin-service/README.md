# Admin Service

Filament 5 Admin Portal for the Social Networks system. It is a Laravel MVC
application using Blade, Livewire and Alpine; it does not use Vue and it does
not connect to another service database.

## Run locally

1. Start `identity-service` at `http://127.0.0.1:8081` and API Gateway at
   `http://127.0.0.1:8080`. Both services must use the same
   `IDENTITY_GATEWAY_KEY`.
2. Keep `IDENTITY_GATEWAY_URL=http://127.0.0.1:8080` in this service `.env`.
3. Run `php artisan serve --host=127.0.0.1 --port=8086`.
4. Open `http://127.0.0.1:8086/admin`.

The portal posts credentials server-to-server to Gateway. The opaque Identity
session token stays in the encrypted Laravel session and is never exposed to
browser JavaScript. If Identity marks an account for a mandatory password
change, that account must complete it through the normal Web app before it can
enter this portal.

## Local demo accounts

After `php artisan migrate:fresh --seed --force` in `identity-service`, all
local demo accounts use password `12345678` and are not required to change it:

| Email | Role |
| --- | --- |
| `admin@gmail.com` | `super_admin` |
| `hr_manager@gmail.com` | `hr_manager` |
| `team_leader@gmail.com` | `team_leader` |
| `qa_expert@gmail.com` | `qa_expert` |
| `member@gmail.com` | `member` |

These credentials are strictly a local development fixture and must never be
used outside a disposable environment.

## Scope in this slice

- Identity dashboard
- User search, status, unlock, force password change, role assignment/removal
- Org-unit create/update, parent and manager assignment
- Role permission matrix

All mutation permissions are enforced again by `identity-service`. Navigation
and buttons only reflect the current permission set.
