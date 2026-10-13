# Identity Service

Laravel 12 service that owns identity, organization structure, profiles, RBAC, account security, audit logs, and transactional outbox/inbox metadata. It is the only service allowed to own user and organization records.

## Included base

- UUID primary keys on all entities.
- Organization/domain, hierarchical org units, users, profiles, memberships, sessions and devices.
- Roles, permissions, scoped user-role assignments, organization settings, audit logs, outbox and processed-event tables.
- Idempotent seed data: one demo organization, two org units, five roles, eleven permissions and three accounts.
- `GET /api/v1/health` as the only implemented API endpoint in this foundation pass.

No business APIs are hidden behind the schema: login, provisioning, profile and RBAC endpoints will be implemented from approved OpenAPI contracts next.

## Database setup

Run the dependency Compose stack in `infrastructure/docker`, then set a host-reachable MySQL connection in `.env` and run:

`php artisan migrate --seed`

The default demo accounts are `admin@demo.internal`, `expert@demo.internal` and `member@demo.internal`; the temporary password is `ChangeMe!2026` and the seed marks it for immediate change. Replace these credentials outside local development.

## Boundaries

- No cross-service database joins or foreign keys.
- External references must be UUID values, with events carried through the outbox/inbox pattern.
- Chat belongs in MongoDB; Knowledge Graph belongs in Neo4j; uploaded files belong in object storage.
