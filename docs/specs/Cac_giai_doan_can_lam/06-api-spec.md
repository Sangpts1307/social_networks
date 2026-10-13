# API Specification

## Current API Inventory

No route definition, controller, request validator, service handler, OpenAPI file, API Gateway proxy configuration or HTTP test exists in `social_networks/`.

| Measure | Result |
| --- | --- |
| Verified endpoints | 0 |
| Verified request schemas | 0 |
| Verified response schemas | 0 |
| Verified auth/permission middleware | 0 |
| Verified API error format | 0 |
| Verified internal service APIs | 0 |
| Verified OpenAPI contracts | 0 |

Consequently there are no `API-XXX` records with a verified method/path. Creating JSON bodies, status codes or URL paths here would be fabrication.

## Documented API Architecture Rules

| Rule | Status | Evidence |
| --- | --- | --- |
| Web and Mobile call only Gateway | `DOCUMENTED_NOT_IMPLEMENTED` | `Cấu trúc cuối cùng.md` sections 3, 9 |
| Gateway has no business data or business logic | `DOCUMENTED_NOT_IMPLEMENTED` | Gateway README; architecture decision |
| Service-to-service immediate needs use REST/JSON | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Public and internal REST contracts reside in `contracts/openapi` | `DOCUMENTED_NOT_IMPLEMENTED` | `contracts/openapi/README.md` |
| Internal API requires service authentication | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Every request carries `correlation_id` | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |

## Required API Contract Record

Before implementing an accepted functional requirement, create an API record using the following exact structure and put the machine-readable OpenAPI contract under `contracts/openapi/` when code implementation is authorized.

```md
# API-<DOMAIN>-<NNN> — <operation>

## Module
## Status                 # PROPOSED | VERIFIED_IMPLEMENTED | DEPRECATED
## Public or Internal
## Method and Path
## Authentication
## Permission
## Correlation ID
## Controller / Handler
## Request
## Validation
## Success Response
## Errors
## DB Side Effects
## Events / Jobs
## External Services
## Idempotency / Pagination
## Evidence
## Linked FR / Tests / Tasks
## Open Questions
```

The template is a process artifact, not a defined endpoint.

## Functional-to-API Planning Map

| FR | API record status | Reason |
| --- | --- | --- |
| FR-AUTH-001/002, FR-PROFILE-001 | `TODO / SPEC_GAP` | No auth flow contract, token model, path, response/error schema. |
| FR-SOCIAL-001 through 005 | `TODO / SPEC_GAP` | No Social routes, DTOs, permission/audience rules or pagination contract. |
| FR-NOTIF-001 | `TODO / SPEC_GAP` | No notification model/read-state contract. |
| FR-CHAT-001 | `TODO / SPEC_GAP` | No conversation/message/realtime protocol or history paging contract. |
| FR-LEARN-001 through 004, FR-DOC-001 | `TODO / SPEC_GAP` | No Q&A/document/LMS data or access contract. |
| FR-GAME-001 | `TODO / SPEC_GAP` | Ledger/formula/owner and authorization unresolved. |
| FR-AI-001/002 | `TODO / SPEC_GAP` | No ingestion, curation, search or provider boundary contract. |
| FR-MOBILE-001 | `TODO / SPEC_GAP` | Mobile must consume Gateway APIs that do not exist. |

## API Design Constraints for Future Contracts

1. Associate each endpoint with exactly one owning module/service.
2. Define request/response/error schemas from an approved FR and owner data model; do not expose a service database model directly.
3. Specify authorization at resource level, including department/content visibility.
4. Use a stable pagination contract before any list endpoint; six items is a UI load requirement, not automatically an API page size.
5. Define idempotency for create/send/upload/moderation operations where retries are expected.
6. Document all side effects and whether they are synchronous, asynchronous or merely proposed.
7. Place event schemas in `contracts/events`; do not encode asynchronous side effects only in prose.
8. Add contract, service and end-to-end tests when the project leaves the stated base-stage exception for test folders.

## OpenAPI Delivery Gate

An API contract is ready to code only when it has a linked FR, owner, auth/permission rule, data-side-effect model, request validation, success/error schema, pagination/idempotency definition, linked event/job effects and a test plan. At present, no endpoint passes this gate.

## Evidence

- `contracts/openapi/README.md`
- `gateway/api-gateway/README.md`
- `Cấu trúc cuối cùng.md`, sections 3 and 6
- Repository inventory: no source files or OpenAPI documents found
