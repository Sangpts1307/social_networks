> BẢN LƯU LỊCH SỬ — số phase cũ không còn dùng để điều phối. Dùng [phase tracker](../specs/README.md).

# Implementation Roadmap

## Planning Rules

This roadmap is dependency-based. It does not estimate hours because source, team capacity, hosting and unresolved product decisions do not support credible time estimates. “Affected files” identifies current spec files and future component areas; absent source files are not claimed to exist.

## Phase 0 — Foundation and Decisions

### TASK-FOUND-001 — Resolve blocking product and security decisions

| Field | Detail |
| --- | --- |
| Description | Answer priority OQs for auth/RBAC, data classification, first vertical slice, realtime, document lifecycle and AI boundaries. |
| Depends on | None |
| Affected module/files | All; `OPEN_QUESTIONS.md`, ADRs, relevant FR specs |
| DB/API/frontend impact | Determines all contracts. |
| Tests/deployment impact | Defines quality/release gates. |
| Priority / complexity | CRITICAL / M |

### TASK-FOUND-002 — Confirm VCS and spec governance

| Field | Detail |
| --- | --- |
| Description | Establish the authoritative Git repository, branching/review policy and spec-change ownership. |
| Depends on | TASK-FOUND-001 |
| Affected module/files | Repository root; `SPEC_WORKFLOW.md`, `AI_CONTEXT.md` |
| DB/API/frontend impact | None directly. |
| Tests/deployment impact | Enables traceability and CI. |
| Priority / complexity | HIGH / S |

### TASK-FOUND-003 — Local infrastructure baseline

| Field | Detail |
| --- | --- |
| Description | Implement approved local Docker Compose for documented data dependencies, configuration contract and health checks. |
| Depends on | TASK-FOUND-001 |
| Affected module/files | `infrastructure/docker/` (future real files); `12-deployment-spec.md` |
| DB/API/frontend impact | Creates isolated service data-store availability; no shared schema. |
| Tests/deployment impact | Enables integration tests; define volume/data-reset approach. |
| Priority / complexity | HIGH / L |

## Phase 1 — Core Platform

### TASK-CORE-001 — Scaffold Gateway and observability baseline

| Field | Detail |
| --- | --- |
| Description | Officially scaffold NestJS Gateway; add health check, correlation ID, safe error envelope, service auth boundary and rate-limit policy only after contracts are approved. |
| Depends on | TASK-FOUND-001, TASK-FOUND-002, TASK-FOUND-003 |
| Affected module/files | `gateway/api-gateway/` future source; API/security/observability specs |
| DB/API/frontend impact | Defines public client boundary; no business DB. |
| Tests/deployment impact | Health/middleware/contract tests; gateway build/release. |
| Priority / complexity | CRITICAL / L |

### TASK-CORE-002 — Implement Identity and login vertical slice

| Field | Detail |
| --- | --- |
| Description | Finalize FR-AUTH-001 contract/data/RBAC, scaffold Identity Laravel, implement Gateway path and real Web login integration. |
| Depends on | TASK-CORE-001 and OQ-001–007 resolution |
| Affected module/files | Identity, Gateway, Web future source; `FR-AUTH-001`, API/DB/test specs |
| DB/API/frontend impact | Identity-local migration; public Gateway API; Login page state/validation. |
| Tests/deployment impact | Auth/security/contract/E2E tests; secret/token migration and rollback notes. |
| Priority / complexity | CRITICAL / XL |

### TASK-CORE-003 — Profile and organization/RBAC slice

| Field | Detail |
| --- | --- |
| Description | Implement approved profile/avatar and organization/permission rules, including safe file handling. |
| Depends on | TASK-CORE-002, OQ-006–007 |
| Affected module/files | Identity/Gateway/Web/object storage future source; FR-PROFILE-001 |
| DB/API/frontend impact | Identity-local model/API/Profile screen. |
| Tests/deployment impact | Upload/authz tests, storage config, deletion/rollback notes. |
| Priority / complexity | HIGH / L |

## Phase 2 — Social Network

### TASK-SOCIAL-001 — Post/feed minimal vertical slice

| Field | Detail |
| --- | --- |
| Description | Approve and implement post creation plus cursor-paginated feed under Social ownership. |
| Depends on | TASK-CORE-002, OQ-009–013 |
| Affected module/files | Social/Gateway/Web future source; FR-SOCIAL-001/002 |
| DB/API/frontend impact | Social-local migration/API/Feed/composer. |
| Tests/deployment impact | Authz, upload, pagination and E2E tests; object storage release notes. |
| Priority / complexity | HIGH / XL |

### TASK-SOCIAL-002 — Comments, saves and moderation

| Field | Detail |
| --- | --- |
| Description | Implement only after comment lifecycle, saved-post state and moderation/audit rules are accepted. |
| Depends on | TASK-SOCIAL-001, OQ-014–017 |
| Affected module/files | Social/Gateway/Web/Notification optional; FR-SOCIAL-003/004/005 |
| DB/API/frontend impact | Social-local data/API/admin UI. |
| Tests/deployment impact | Length/order/idempotency/RBAC/audit tests. |
| Priority / complexity | HIGH / L |

## Phase 3 — EDU and Knowledge Sharing

### TASK-LEARN-001 — Q&A vertical slice

| Field | Detail |
| --- | --- |
| Description | Implement topic/category, list, create question and detail/answer semantics after content lifecycle approval. |
| Depends on | TASK-CORE-002, OQ-022–028 |
| Affected module/files | Learning/Gateway/Web future source; FR-LEARN-001/002/003 |
| DB/API/frontend impact | Learning-local migrations/API/Q&A pages. |
| Tests/deployment impact | Topic, access, answer/vote and pagination tests. |
| Priority / complexity | HIGH / XL |

### TASK-LEARN-002 — Document Library and secure upload

| Field | Detail |
| --- | --- |
| Description | Implement document metadata, private binary storage and authorized browse/download, not AI processing yet. |
| Depends on | TASK-CORE-003, TASK-LEARN-001, OQ-030–032 |
| Affected module/files | Learning/Gateway/Web/infrastructure future source; FR-DOC-001 |
| DB/API/frontend impact | Learning-local data/API/Library UI. |
| Tests/deployment impact | File scan/type/access/delete tests; storage backup/lifecycle. |
| Priority / complexity | HIGH / XL |

### TASK-LEARN-003 — Points/reputation ownership and statistics

| Field | Detail |
| --- | --- |
| Description | Decide ledger owner/formula, then implement points/reputation projections and statistic UI. |
| Depends on | TASK-SOCIAL-002, TASK-LEARN-001, OQ-029/OQ-033 |
| Affected module/files | Social/Learning/Notification optional; FR-GAME-001 |
| DB/API/frontend impact | Owner-local ledger/read model/API. |
| Tests/deployment impact | Calculation/reversal/audit contract tests. |
| Priority / complexity | MEDIUM / L |

## Phase 4 — Chat, Notification and Realtime

### TASK-RT-001 — Notification foundation

| Field | Detail |
| --- | --- |
| Description | Define event/payload/read-state/provider policy, then implement durable in-app notification flow. |
| Depends on | TASK-CORE-002, OQ-018 |
| Affected module/files | Notification/Gateway/Web/contracts future source; FR-NOTIF-001 |
| DB/API/frontend impact | Notification-local schema/API; client bell/dropdown. |
| Tests/deployment impact | Recipient isolation, retry/event and observability tests. |
| Priority / complexity | HIGH / L |

### TASK-RT-002 — Direct chat and selected realtime transport

| Field | Detail |
| --- | --- |
| Description | Implement persistent direct chat/read state plus one approved realtime transport. |
| Depends on | TASK-CORE-002, TASK-RT-001, OQ-019–021 |
| Affected module/files | Chat/Gateway/Web/contracts/infrastructure future source; FR-CHAT-001 |
| DB/API/frontend impact | MongoDB collection/API/Messenger UI/channels. |
| Tests/deployment impact | Persistence-before-publish/reconnect/read/pagination tests; worker/transport monitoring. |
| Priority / complexity | HIGH / XL |

## Phase 5 — AI and Knowledge Graph

### TASK-AI-001 — Curation-first ingestion pilot

| Field | Detail |
| --- | --- |
| Description | Implement authorized document ingestion, extraction proposal and human curation; do not release uncontrolled graph writes. |
| Depends on | TASK-LEARN-002, TASK-FOUND-001, OQ-031–039 |
| Affected module/files | AI Knowledge/Learning/contracts/infrastructure future source; FR-AI-001 |
| DB/API/frontend impact | AI metadata/Neo4j projection/curation API/UI. |
| Tests/deployment impact | Parser safety, source auth, model output schema, curation/audit/retry/deletion tests; provider and cost telemetry. |
| Priority / complexity | HIGH / XL |

### TASK-AI-002 — Access-filtered hybrid retrieval and Expert Finder

| Field | Detail |
| --- | --- |
| Description | Add retrieval/evaluation only after curated corpus and access rules are proven. |
| Depends on | TASK-AI-001, TASK-LEARN-003, OQ-034–040 |
| Affected module/files | AI Knowledge/Gateway/Web/Notification future source; FR-AI-002 |
| DB/API/frontend impact | Search/index/graph query/API/Explorer UI. |
| Tests/deployment impact | Authorization, citation, relevance, failure/cost and notification-throttle tests. |
| Priority / complexity | HIGH / XL |

## Phase 6 — Mobile

### TASK-MOBILE-001 — Android auth/feed/chat client

| Field | Detail |
| --- | --- |
| Description | Scaffold Android only after stable Gateway contracts, then implement authentication, Newsfeed and Messenger in increments. |
| Depends on | TASK-SOCIAL-001, TASK-RT-002, OQ-041–042 |
| Affected module/files | `apps/mobile/` future source; Gateway contracts; FR-MOBILE-001 |
| DB/API/frontend impact | No direct DB; uses existing Gateway contracts. |
| Tests/deployment impact | Device auth/network/push/UI test plan and mobile release signing policy. |
| Priority / complexity | MEDIUM / XL |

## Phase 7 — Hardening

### TASK-HARDEN-001 — Production readiness review

| Field | Detail |
| --- | --- |
| Description | Run security, performance, backup/restore, observability, accessibility, contract and incident-readiness review for implemented scope. |
| Depends on | All released slices |
| Affected module/files | All live components; Specs 10–13 |
| DB/API/frontend impact | Verify migration compatibility, API deprecation and client resilience. |
| Tests/deployment impact | CI gates, staging rehearsal, rollback and operational sign-off. |
| Priority / complexity | CRITICAL / XL |
