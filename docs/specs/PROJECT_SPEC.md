# Project Specification

> **Đối soát Foundation 2026-09-16:** đã có source, model/migration, Web/Gateway build và HTTP health local PASS. Các nhận định “chưa có source” bên dưới là snapshot cũ, không còn dùng cho Foundation. Xem [bằng chứng hiện tại](../Base_du_an/FOUNDATION_AUDIT_2026-09-16.md). Chưa xác minh business flow/auth hay migration database; không nâng feature lên VERIFIED_IMPLEMENTED.

## Project Overview

Enterprise Knowledge Platform / NexaHub là nền tảng nội bộ kết hợp mạng xã hội doanh nghiệp, học tập/tri thức, chat, thông báo và AI Knowledge Graph. Product directory `social_networks/` hiện là base skeleton có architecture decision, README service boundaries và static design artifacts; chưa có executable application implementation.

## Business Objectives

1. Cung cấp kênh truyền thông/chia sẻ nội bộ có kiểm soát quyền.
2. Hỗ trợ học tập, tài liệu và hỏi đáp chuyên môn trong doanh nghiệp.
3. Bảo tồn và kết nối tri thức qua Knowledge Graph có human curation.
4. Hỗ trợ Expert Finder, learning recommendation và Hybrid Search sau khi dữ liệu/AI controls đủ an toàn.

**Evidence:** `Mô tả đề tài.docx`; `README.md`; `Cấu trúc cuối cùng.md`.

## Scope and Current Implementation Status

| State | Items |
| --- | --- |
| `VERIFIED_IMPLEMENTED` | Không có product runtime feature. Standalone NetworkX/PyVis graph demo ngoài `social_networks` chỉ là demo mock. |
| `VERIFIED_PARTIAL` | Static Welcome and Login HTML visual references; no functional API/auth/data integration. |
| `DOCUMENTED_NOT_IMPLEMENTED` | Gateway, Identity, Social, Learning, Chat, Notification, AI Knowledge, Web Vue, Android, databases, messaging, contracts, infrastructure. |
| `UNKNOWN` | Exact API/database schema, auth/RBAC, realtime choice, AI provider/vector strategy, deployment, monitoring and most detailed business rules. |

## Actors

| Actor | Evidence-backed capability | Gaps |
| --- | --- | --- |
| Employee | Login/register, profile, social/feed/comment, direct chat, learning/Q&A/library. | Fine-grained permissions and department scope unresolved. |
| Administrator | Moderate abnormal/reported posts; view other points in statistic view; potentially curation. | Role definitions and approval/audit workflow unresolved. |
| Expert/curator | Documented as potential AI curation/expert finder participant. | No role, consent or workflow contract. |
| AI processor | Documented async knowledge processing. | No runtime/job/model/data policy. |

## Applications and Domains

| Application/domain | Owner/Boundary | Status |
| --- | --- | --- |
| Web | Vue 3/TypeScript/Tailwind target | `VERIFIED_PARTIAL` static mocks only |
| Mobile | React Native + Expo target (Android/iOS) | `DOCUMENTED_NOT_IMPLEMENTED` |
| Gateway | NodeJS/NestJS target | `DOCUMENTED_NOT_IMPLEMENTED` |
| Identity/Auth | Laravel + MySQL target | `DOCUMENTED_NOT_IMPLEMENTED` |
| Social | Laravel + MySQL target | `DOCUMENTED_NOT_IMPLEMENTED` |
| Learning | Laravel + MySQL target | `DOCUMENTED_NOT_IMPLEMENTED` |
| Chat | Laravel + MongoDB target | `DOCUMENTED_NOT_IMPLEMENTED` |
| Notification | Laravel + MySQL target | `DOCUMENTED_NOT_IMPLEMENTED` |
| AI Knowledge | Python + MySQL + Neo4j target | `DOCUMENTED_NOT_IMPLEMENTED` |

See [02-module-catalog.md](Cac_giai_doan_can_lam/02-module-catalog.md) for full catalog.

## System Architecture

The target is a microservice monorepo: clients call only Gateway; Gateway calls owners; services own distinct stores; REST handles synchronous interactions; RabbitMQ handles asynchronous events. This is a documented decision, not a runtime observation. See [01-current-system.md](Cac_giai_doan_can_lam/01-current-system.md) and [15-target-architecture.md](Cac_giai_doan_can_lam/15-target-architecture.md).

## Data Architecture

Identity, Social, Learning, Notification and AI metadata own separate MySQL databases/schemas; Chat owns MongoDB; AI Knowledge owns Neo4j graph; Redis is ephemeral; object storage contains binaries. No actual schemas/migrations exist. See [05-database-spec.md](Cac_giai_doan_can_lam/05-database-spec.md).

## API, Frontend, AI and Realtime Architecture

| Area | Current fact | Source of detail |
| --- | --- | --- |
| API | Zero verified routes/contracts. | [06-api-spec.md](Cac_giai_doan_can_lam/06-api-spec.md) |
| Frontend | Two static mocks; no Vue project. | [07-frontend-spec.md](Cac_giai_doan_can_lam/07-frontend-spec.md) |
| AI/KG | Target only; standalone demo not integration. | [08-ai-knowledge-spec.md](Cac_giai_doan_can_lam/08-ai-knowledge-spec.md) |
| Realtime/eventing | Target only; no event schema/transport. | [09-realtime-eventing.md](Cac_giai_doan_can_lam/09-realtime-eventing.md) |

## Security, Testing, Deployment and Monitoring

No live security, automated tests, delivery pipeline or telemetry has been found. The specification defines their release gates without falsely claiming they exist.

- [10-security-spec.md](Cac_giai_doan_can_lam/10-security-spec.md)
- [11-testing-spec.md](Cac_giai_doan_can_lam/11-testing-spec.md)
- [12-deployment-spec.md](Cac_giai_doan_can_lam/12-deployment-spec.md)
- [13-observability.md](Cac_giai_doan_can_lam/13-observability.md)

## Critical Gaps

1. No runnable implementation, schema, contract or deployment baseline.
2. Authentication, RBAC, department/content visibility and data classification are undecided.
3. Social/Learning/Chat detail requirements are incomplete at key lifecycle/permission points.
4. Realtime, notification provider and event contract decisions are unresolved.
5. AI/KG ontology, provider/vector approach, access filtering, curation, evaluation and deletion propagation are unresolved.

See [14-gap-analysis.md](Cac_giai_doan_can_lam/14-gap-analysis.md) and [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Technical Decisions

| Decision | Status | Reference |
| --- | --- | --- |
| Microservice monorepo with documented service boundaries | Existing high-priority decision | [ADR-001](adr/ADR-001-current-application-architecture.md) |
| Auth token/session/role matrix | Needs Decision | [ADR-002](adr/ADR-002-authentication-strategy.md) |
| Realtime transport and push strategy | Needs Decision | [ADR-003](adr/ADR-003-realtime-strategy.md) |
| AI service integration/provider controls | Needs Decision | [ADR-004](adr/ADR-004-ai-integration.md) |
| Knowledge Graph ontology/curation/search | Needs Decision | [ADR-005](adr/ADR-005-knowledge-graph.md) |

## Roadmap and Spec Index

| File | Purpose |
| --- | --- |
| [00-project-inventory.md](Cac_giai_doan_can_lam/00-project-inventory.md) | Filesystem inventory and evidence vocabulary. |
| [01-current-system.md](Cac_giai_doan_can_lam/01-current-system.md) | Current runtime assessment vs documented target. |
| [02-module-catalog.md](Cac_giai_doan_can_lam/02-module-catalog.md) | Modules, ownership and missing implementation. |
| [03-functional-spec.md](Cac_giai_doan_can_lam/03-functional-spec.md) | Evidence-backed functional requirements. |
| [04-user-flows.md](Cac_giai_doan_can_lam/04-user-flows.md) | Target user flows and failure gaps. |
| [05-database-spec.md](Cac_giai_doan_can_lam/05-database-spec.md) | Current data absence and ownership constraints. |
| [06-api-spec.md](Cac_giai_doan_can_lam/06-api-spec.md) | Current API inventory and contract gate. |
| [07-frontend-spec.md](Cac_giai_doan_can_lam/07-frontend-spec.md) | Static UI evidence and future page integration rules. |
| [08-ai-knowledge-spec.md](Cac_giai_doan_can_lam/08-ai-knowledge-spec.md) | AI/KG current assessment and controlled target. |
| [09-realtime-eventing.md](Cac_giai_doan_can_lam/09-realtime-eventing.md) | Event/realtime assessment and contract gate. |
| [10-security-spec.md](Cac_giai_doan_can_lam/10-security-spec.md) | Security current state, risks and gates. |
| [11-testing-spec.md](Cac_giai_doan_can_lam/11-testing-spec.md) | Test strategy and future coverage map. |
| [12-deployment-spec.md](Cac_giai_doan_can_lam/12-deployment-spec.md) | Delivery current state and release record. |
| [13-observability.md](Cac_giai_doan_can_lam/13-observability.md) | Telemetry and alerting baseline. |
| [14-gap-analysis.md](Cac_giai_doan_can_lam/14-gap-analysis.md) | Intended-vs-current gaps and document conflicts. |
| [15-target-architecture.md](Cac_giai_doan_can_lam/15-target-architecture.md) | Constrained proposed delivery architecture. |
| [16-consistency-review.md](Cac_giai_doan_can_lam/16-consistency-review.md) | Structural cross-check and unresolved evidence limits. |
| [TRACEABILITY.md](TRACEABILITY.md) | Requirement-to-implementation map. |
| [ROADMAP.md](ROADMAP.md) | Dependency-based delivery plan. |
| [AI_CONTEXT.md](AI_CONTEXT.md) | IDE-agnostic AI development operating rules. |
| [SPEC_WORKFLOW.md](SPEC_WORKFLOW.md) | Feature/change workflow. |
| [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md) | Decisions that must not be guessed. |

## Operating Rule

For every new requirement: read this master spec -> read module/FR -> inspect actual implementation -> perform impact analysis -> resolve any blocking `SPEC_GAP` -> update spec -> break down task -> implement/test/review/deploy -> update traceability/status. Full process: [SPEC_WORKFLOW.md](SPEC_WORKFLOW.md).
