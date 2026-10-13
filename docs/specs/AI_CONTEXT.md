# AI Development Rules

> **Cập nhật 2026-09-16:** ưu tiên [chạy local trực tiếp](../Base_du_an/LOCAL_DEVELOPMENT.md) bằng môi trường đã cài; sau khi local đạt mới [build/run Docker](../Base_du_an/DOCKER_DEVELOPMENT.md). Docker không là điều kiện bắt đầu phát triển local. Ghi bằng chứng hai cách riêng; mô tả Docker-first cũ bên dưới được thay thế bởi quyết định này.

> **Theo dõi công việc từ 2026-09-15:** Đọc [README phase](README.md); cập nhật trạng thái và nhật ký trong file công việc của phase. `NEEDS_REVIEW` yêu cầu đối soát code trước khi chọn trạng thái. Các mô tả hiện trạng cũ không thay thế bằng chứng source.

## Purpose

This file is the IDE-agnostic operating contract for an AI agent or developer working on this project. It is deliberately Markdown-only so the same source of truth works in Codex, Cursor, Claude Code, GitHub Copilot, VS Code and any IDE that can read repository files.

## Current Repository Rule

The current product directory is a documentation/base skeleton. Do not claim that a service, API, migration, test, Docker component or integration exists unless its actual artifact is present and linked in a spec. Static HTML references are not functional implementations.

## Source-of-Truth Precedence

1. A newer explicit owner decision/change request.
2. Actual source code, migration, route/config and tests for **current implemented state**.
3. `Cấu trúc cuối cùng.md` for the existing target architecture decision.
4. `docs/specs/PROJECT_SPEC.md` and linked specs for traceable project knowledge.
5. Existing design/Word/Excel/proposal artifacts for intended requirements only.
6. Inference, clearly labelled `INFERRED` and never promoted to requirement.

If sources conflict, record the conflict; do not silently choose an older proposal over the priority architecture decision.

## Status Vocabulary

Use only `VERIFIED_IMPLEMENTED`, `VERIFIED_PARTIAL`, `DOCUMENTED_NOT_IMPLEMENTED`, `INFERRED`, `UNKNOWN`, and `TODO / SPEC_GAP` for implementation conclusions. Every non-trivial factual claim needs a file/path or other inspectable evidence.

## Before Coding

1. Read [PROJECT_SPEC.md](PROJECT_SPEC.md), [SPEC_WORKFLOW.md](SPEC_WORKFLOW.md), [TRACEABILITY.md](TRACEABILITY.md) and the target module/FR/API/data/security/test specs.
2. Read `Cấu trúc cuối cùng.md` before changing architecture or creating a component.
3. Inspect the real implementation and verify that the spec still matches it.
4. Perform impact analysis covering business behavior, ownership boundary, API, event, database, frontend/mobile, security, test, deployment and observability.
5. Identify every `SPEC_GAP`, contradiction and missing decision. Ask the project owner or create/update `OPEN_QUESTIONS.md`; **do not invent a requirement**.
6. Update the relevant spec/traceability/ADR before code if the accepted change alters a contract, data model, architecture or user-visible behavior.
7. Confirm whether the project owner has ended the documented base-stage restriction on creating tests before creating test folders.

## Mandatory Architecture Constraints

- Web and Mobile call only the NodeJS/NestJS API Gateway.
- Gateway does not own business data or business logic.
- Laravel is the documented core-service stack; Python is the documented AI stack.
- Identity owns authentication, role and permission truth.
- Each service owns an independent database/schema/migration. Never cross-join, cross-FK or reuse another service’s ORM model.
- Use REST/JSON for approved synchronous service communication and RabbitMQ for approved asynchronous contracts.
- Chat’s persistent source of truth is MongoDB; realtime transport is not source of truth.
- MySQL is documented for non-Chat operational data and AI metadata; Neo4j is documented for Knowledge Graph; Redis is ephemeral.
- Binary files go to approved object storage; do not make a database/blob cache their durable source.
- Hybrid Search remains within AI Knowledge Service unless an approved ADR changes it.

## Required Pre-implementation Output

Before editing code, produce this concise record in the task/PR/issue:

```md
Requirement: FR-...
Existing behavior: evidence-backed status and paths
Desired behavior: approved requirement/change request
Impacted components: module, API/event, DB, frontend/mobile, ops
Proposed changes: bounded file/contract/migration changes
Risks and SPEC_GAPs: decisions needed, compatibility/security risks
Test plan: unit, contract, integration/E2E/security coverage
Deployment notes: configuration, migration, workers, rollback/monitoring
```

If any critical item is unknown, stop at the spec gap rather than producing speculative code.

## Implementation Rules

1. Work in the smallest vertically complete, dependency-safe slice.
2. Keep module/service ownership intact and avoid unrelated refactoring.
3. Define request/response/event schemas before their implementation; version shared contracts.
4. Validate all untrusted input server-side; enforce resource authorization in the owning service.
5. Make event consumers idempotent and record async retry/failure behavior.
6. Never place credentials, raw private content, tokens or secrets in code, logs, specs or tests.
7. Apply file and AI data controls described in [10-security-spec.md](Cac_giai_doan_can_lam/10-security-spec.md) and [08-ai-knowledge-spec.md](Cac_giai_doan_can_lam/08-ai-knowledge-spec.md).
8. Do not create test artifacts during the documented base-stage restriction. Once that restriction ends, tests are mandatory for each implemented slice.
9. Do not mark planned/proposed work as implemented.

## After Implementation

Report and update:

1. Files actually changed and why.
2. FR/API/event/database/traceability updates.
3. Tests run and results; explicitly state not-run tests and reason.
4. Migrations, configuration/secrets-by-name, worker/event and deployment/rollback notes.
5. Observability/log/metric/alert additions.
6. Remaining gaps, limitations and follow-up task IDs.

## Cross-IDE Usage

For any AI-capable IDE, use this bootstrap instruction before a task:

```text
Read docs/specs/AI_CONTEXT.md and docs/specs/PROJECT_SPEC.md first.
Then read the relevant FR/module/API/data/security/test specs and inspect actual source.
Do not implement unresolved SPEC_GAPs or invent requirements.
Provide impact analysis before code and update traceability/specs with accepted changes.
```

If a tool supports a repository instruction file (for example an agent rule file, a Copilot instruction file, a Claude instruction file or a Cursor rule), create only a small adapter that points to this document and `PROJECT_SPEC.md`. Do not duplicate or fork rules across tool-specific files; this file stays canonical.

## Required Task Close-out

No task is complete merely because code compiles. It is complete when the approved requirement, owner boundary, contract, data impact, test evidence, security posture, deployment/rollback notes and spec/traceability status are all consistent.
