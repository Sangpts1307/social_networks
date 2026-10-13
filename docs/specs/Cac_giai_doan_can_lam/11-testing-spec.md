# Testing Specification

## Existing Tests

| Test layer | Existing result | Status |
| --- | --- | --- |
| Unit | No source or framework configuration found. | `NOT_FOUND` |
| Feature/API | No source or framework configuration found. | `NOT_FOUND` |
| Integration | No source or framework configuration found. | `NOT_FOUND` |
| Contract | No OpenAPI/event schema or contract test found. | `NOT_FOUND` |
| End-to-end | No web/mobile app or E2E configuration found. | `NOT_FOUND` |
| Security/performance | No automation/configuration found. | `NOT_FOUND` |

The architecture decision explicitly says not to create test directories during the current “base” setup stage. This inventory preserves that rule: this document defines a future test gate but does not create test code or folders.

## Coverage Map

| FR | Verified test | Required future tests | Current status |
| --- | --- | --- | --- |
| FR-AUTH-001 Login | None | validation, success/failure, account state, token/session, first/new-device alert, gateway auth | `PROPOSED` |
| FR-AUTH-002 Register | None | eligibility, duplicate, verification code lifecycle, resend/rate limit, success redirect | `PROPOSED` |
| FR-PROFILE-001 | None | ownership, allowed fields, avatar type/size, storage failure | `PROPOSED` |
| FR-HOME-001 | None | auth route guard, loading/empty/error states, summary contracts | `PROPOSED` |
| FR-SOCIAL-001/002/003/004/005 | None | feed pagination, content preview, upload policy, comment length/order, save idempotency, moderation RBAC/audit | `PROPOSED` |
| FR-NOTIF-001 | None | recipient isolation, newest-first paging, read state and retry if adopted | `PROPOSED` |
| FR-CHAT-001 | None | membership, persistence-before-publish, duplicate/reconnect, read state, pagination/retention | `PROPOSED` |
| FR-LEARN-001/002/003/004 | None | taxonomy validation, question/answer/vote access, list/detail pages, saved content, profile scope | `PROPOSED` |
| FR-DOC-001 | None | MIME/size/scan/auth download, metadata/storage consistency, AI ingestion handoff | `PROPOSED` |
| FR-GAME-001 | None | ledger calculation, authorization, reversals/audit | `PROPOSED` |
| FR-AI-001/002 | None | extraction schema, curation states, access filter, evaluation set, deletion sync, injection safety | `PROPOSED` |
| FR-MOBILE-001 | None | Gateway-only traffic, feed/chat flows, push permission/handling | `PROPOSED` |

## Test Strategy by Layer

| Layer | Purpose | Required when implementation starts |
| --- | --- | --- |
| Unit | Domain rules, validators, transformations and state transitions. | Per owning service/module. |
| Service/feature API | Request validation, authz, persistence side effects and error contract. | Every Gateway-exposed operation. |
| Contract | Validate OpenAPI and event schema compatibility between producer/consumer/client. | Every shared REST/event contract. |
| Integration | Database, object storage, RabbitMQ, Neo4j, Redis and external provider adapters. | Each adapter plus failure/retry behavior. |
| E2E | Critical user journeys through client -> Gateway -> service. | Login, post/comment, direct chat, ask/answer, document flow. |
| Security | Authz isolation, upload abuse, XSS/injection, secret/config and AI prompt-injection controls. | Before staging/production release. |
| Performance/resilience | Feed/search/chat pagination, queue backlog, AI timeout and recovery. | Before scale/release claims. |

## Test ID Convention

Use stable IDs linked to the requirement and contract: `TEST-AUTH-001`, `TEST-POST-001`, `TEST-CHAT-001`, `TEST-EDU-001`, `TEST-AI-001`. A test record must name the FR/API/event, setup, expected output, side effects, environment and current result.

## Definition of Done for an Implemented Feature

1. Linked FR, module, API/event and data impact are approved.
2. Automated tests cover the normal path, validation/failure path, resource authorization and key side effects.
3. Contract compatibility is verified for client/service or producer/consumer changes.
4. Required migrations and rollback/backup notes are reviewed.
5. Security and observability requirements are met.
6. CI quality gates pass.
7. Documentation/traceability and deployment checklist are updated.

## Critical Missing Tests

Everything is missing because no application implementation exists. The first test work must begin only after the project owner confirms that the base-stage restriction has ended and the first runnable component is scaffolded. Priority order: Identity/Gateway security, Social post/comment authorization, document-upload security, Chat persistence/read state, and AI access/curation safety.

## Evidence

- Repository inventory: no tests/test config found
- `Cấu trúc cuối cùng.md`, section 10 (base-stage test restriction)
- [03-functional-spec.md](03-functional-spec.md)
