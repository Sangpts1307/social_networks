# Module Catalog

## Catalog Rules

Module status describes the actual repository, not the intended architecture. “Main entities” below are documented ownership concepts, not verified models or tables. Where an implementation artifact is absent, the value is explicitly `NOT_FOUND`.

## MOD-GATEWAY — API Gateway

| Field | Current finding |
| --- | --- |
| Purpose | Public entry point for Web/Mobile; documented routing, token verification, rate limit, correlation ID, error normalization and realtime edge. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities/tables | None verified; Gateway must not own business data. |
| Routes/controllers/services/config | `NOT_FOUND` |
| Dependencies | Documented NodeJS + NestJS; backend services; Redis may support edge concerns. |
| Known issues/missing pieces | No NestJS project, config, proxy map, auth middleware, healthcheck or test. |
| Evidence | `gateway/api-gateway/README.md`; `Cấu trúc cuối cùng.md` sections 3, 7, 8. |

## MOD-AUTH — Identity and Access

| Field | Current finding |
| --- | --- |
| Purpose | Account, employee profile, department, role, permission and authentication-token ownership. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | Account/user, profile, department, role, permission, session/token (documented concepts). |
| Main tables/models/routes/controllers/jobs/events/policies | `NOT_FOUND` |
| Frontend linkage | Login and profile mock/design references only. |
| Dependencies | Documented MySQL; Gateway; consumers may receive identity events. |
| Known issues/missing pieces | Registration model, password policy, token/session model, RBAC matrix, email verification, reset and device-login behavior are not specified completely. |
| Evidence | `services/identity-service/README.md`; `Cấu trúc cuối cùng.md` sections 4–7; `ananylics/Social Networks.xlsx` “Tổng quan” rows 4–13, 25–27. |

## MOD-SOCIAL — Enterprise Social Network

| Field | Current finding |
| --- | --- |
| Purpose | Posts, media metadata, comments, reactions, upvotes and reputation. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | Post, event-post, media metadata, comment, reaction, saved post, friend/follow relation, report, reputation/upvote (some only requirements-level). |
| Main tables/models/routes/controllers/jobs/events/listeners/policies | `NOT_FOUND` |
| Frontend linkage | Social Feed file exists only as placeholder. Welcome mock labels feed, chat, event, colleagues. |
| Dependencies | Documented MySQL, object storage, RabbitMQ/Notification/AI as possible target flows. |
| Known issues/missing pieces | Data ownership for friend/follow/channel/event/moderation and its policies has not been approved; no pagination/API contract. |
| Evidence | `services/social-service/README.md`; `Cấu trúc cuối cùng.md` section 4; workbook “Tổng quan” rows 29–50. |

## MOD-CHAT — Messaging and Presence

| Field | Current finding |
| --- | --- |
| Purpose | Conversation, membership, message and read state; realtime is transport, MongoDB is source of truth. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | Conversation, conversation member, message, read receipt/state, attachment/presence (attachment/presence not yet specified). |
| Main collections/routes/controllers/services/events | `NOT_FOUND` |
| Frontend linkage | Messenger design file is a placeholder. |
| Dependencies | Documented MongoDB, Gateway, realtime transport, Notification. |
| Known issues/missing pieces | Workbook says direct chat only with friends and “load all history”; group chat is mentioned by proposal but not detailed; realtime transport and retention/pagination are unresolved. |
| Evidence | `services/chat-service/README.md`; `Cấu trúc cuối cùng.md` sections 4, 8; workbook rows 53–60. |

## MOD-NOTIFICATION — Notifications

| Field | Current finding |
| --- | --- |
| Purpose | In-app notifications, push-device registrations and delivery history. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | Notification, device registration, delivery attempt/history (documented concepts). |
| Main tables/routes/jobs/events/listeners | `NOT_FOUND` |
| Frontend linkage | Welcome mock shows unread notification summary; Social workbook describes a notification dropdown. |
| Dependencies | Documented MySQL; possible FCM/Pusher target unverified. |
| Known issues/missing pieces | Taxonomy, payload, recipient preferences, retry and provider choice absent. |
| Evidence | `services/notification-service/README.md`; workbook rows 31–33; `01-welcome.html`. |

## MOD-LEARNING — Learning, Q&A and Library

| Field | Current finding |
| --- | --- |
| Purpose | Courses, lessons, training material, progress, Q&A and FAQ. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | Course, lesson, learning progress, question, answer, topic, category, document, vote, saved question, user points/coins (some only requirement-level). |
| Main tables/models/routes/controllers/services/jobs/events/policies | `NOT_FOUND` |
| Frontend linkage | Learning Home/Q&A design files are placeholders; welcome mock displays learning entry and course progress. |
| Dependencies | Documented MySQL, object storage, AI Knowledge, Notification. |
| Known issues/missing pieces | Course/lesson workflow, role permissions, document preview/download authorization, Q&A answer lifecycle, coin conversion and moderation have no accepted detailed rule. |
| Evidence | `services/learning-service/README.md`; `Cấu trúc cuối cùng.md` section 4; workbook rows 69–124. |

## MOD-AI — AI Knowledge and Search

| Field | Current finding |
| --- | --- |
| Purpose | AI processing pipeline, metadata/chunks, curation, Knowledge Graph, expert finder, recommendation and hybrid search. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Main entities | AI job, source document/content, text chunk, extraction proposal, knowledge node, relation proposal, curation decision, expertise score, search result (documented concepts). |
| Runtime/API/jobs/prompts/model client/Neo4j query | `NOT_FOUND` |
| Ancillary demo | `test_knowledge_graph/demo_knowledge_graph.py` is `VERIFIED_IMPLEMENTED` only as a standalone NetworkX/PyVis mock-data demo; it is not a product service. |
| Dependencies | Documented Python, MySQL metadata, Neo4j, RabbitMQ, storage; provider/vector-store undecided. |
| Known issues/missing pieces | No ingestion contract, content extraction policy, model/provider, evaluation, prompt versioning, approval workflow or data-protection rule. |
| Evidence | `services/ai-knowledge-service/README.md`; `Cấu trúc cuối cùng.md` sections 4, 8; `Mô tả đề tài.docx`; `knowledge_graph_detail.md`; demo source above. |

## MOD-WEB — Web Client

| Field | Current finding |
| --- | --- |
| Purpose | Browser client for Social, Learning and account workflows. |
| Current status | `VERIFIED_PARTIAL` — static references only; functional web app is not implemented. |
| Pages/components/router/store/API client | `NOT_FOUND` |
| Static references | `01-welcome.html` and `02-login.html` contain complete static markup; remaining listed pages are placeholders. |
| Documented stack | Vue 3, TypeScript, TailwindCSS. |
| Missing pieces | Scaffold, routing, design tokens, accessibility, auth state, API client, validation, tests and build. |
| Evidence | `apps/web/README.md`; `design/exports/html-tailwind/*`. |

## MOD-MOBILE — Android Client

| Field | Current finding |
| --- | --- |
| Purpose | Native Android client, initial documented focus on feed and messenger. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Kotlin/Java/Gradle/UI/network/push source | `NOT_FOUND` |
| Evidence | `apps/mobile/README.md`; workbook rows 126–129; `Cấu trúc cuối cùng.md` sections 3 and 11. |

## MOD-CONTRACTS — REST and Events

| Field | Current finding |
| --- | --- |
| Purpose | Versioned REST contracts and RabbitMQ event contracts. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| OpenAPI/event schemas | `NOT_FOUND`; only directory README files exist. |
| Dependencies | Gateway and all services. |
| Missing pieces | Namespace/versioning rules, schemas, compatibility policy, producer/consumer registry and contract tests. |
| Evidence | `contracts/openapi/README.md`; `contracts/events/README.md`; `Cấu trúc cuối cùng.md` section 6. |

## MOD-INFRA — Local Infrastructure and Delivery

| Field | Current finding |
| --- | --- |
| Purpose | Intended Docker local environment for MySQL, MongoDB, Redis, RabbitMQ, Neo4j and MinIO. |
| Current status | `DOCUMENTED_NOT_IMPLEMENTED` |
| Compose/Dockerfile/nginx/env/CI/monitoring | `NOT_FOUND` |
| Evidence | `infrastructure/docker/README.md`; `Cấu trúc cuối cùng.md` section 11. |

## Cross-module Dependency Rules

1. Identity is the documented source of account, role and permission truth.
2. Each business service owns its own database/schema and migrations.
3. Cross-service relations use UUID references, internal REST or locally maintained read models; no cross-database FK/join.
4. Client traffic must stop at Gateway, never direct service databases/services.
5. Cross-service changes must update the relevant contract, API/event spec, traceability row and tests before implementation.

**Evidence:** `Cấu trúc cuối cùng.md` sections 4–7.
