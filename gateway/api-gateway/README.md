# API Gateway

NestJS gateway is the only intended public entry point for web and mobile clients. It contains no business tables, migrations, or domain models.

Current base exposes `GET /api/health`, enables CORS for the web client, preserves/creates `X-Request-Id`, and centralizes internal service URLs. Identity auth/session routes and protected /api/v1/me now proxy to Identity with service authentication and session introspection.

Run locally after copying `.env.example` to `.env`:

`npm ci`

`npm run start:dev`

The gateway loads `.env` when present; exported environment variables take precedence. It binds to 127.0.0.1 by default; set HOST explicitly for container deployment. See [local setup](../../docs/Base_du_an/LOCAL_DEVELOPMENT.md). Docker packaging follows successful local validation.

Auth endpoints and checks: [implementation guide](../../docs/Base_du_an/AUTH_SESSION_IMPLEMENTATION_2026-09-17.md). Run `npm run check:contracts` for Identity/Gateway OpenAPI validation.
