# Architecture Gap Analysis

## Reading This Matrix

“Intended” comes from existing design/requirement artifacts. “Current” is sourced from the actual `social_networks/` file inventory. A gap is not an implementation defect if the project is intentionally at base stage; it is a planning/dependency gap that must not be hidden by an “implemented” claim.

| Area | Intended | Current | Gap | Severity | Recommendation |
| --- | --- | --- | --- | --- | --- |
| Repository governance | Microservice monorepo with priority architecture decision. | No Git repository metadata in product folder; docs only. | No version-control/process baseline demonstrable. | HIGH | Initialize/confirm VCS ownership and protect architectural decision/spec review process. |
| Frontend | Vue 3 + TypeScript + Tailwind. | README, 2 static mocks, 8 placeholders; no Vue code. | No functional client, routing or API integration. | HIGH | Finalize first screens and Gateway contract, then scaffold only Web when first feature is authorized. |
| Mobile | Android Native Kotlin/Java. | README only. | No project/SDK/config/push integration. | MEDIUM | Defer until Gateway auth/feed/chat contracts are stable. |
| API Gateway | NodeJS/NestJS, only client entry, auth/rate limit/correlation/error normalization. | README only. | No boundary/runtime/security implementation. | CRITICAL | Make Gateway health/auth/proxy/correlation the first runtime foundation. |
| Identity/Auth | Laravel + MySQL, account/profile/RBAC/token. | README + static login visual; no source/schema. | All auth/security business behavior unresolved. | CRITICAL | Resolve OQ-001–007 and implement identity vertical slice first. |
| Social | Post/media/comment/reaction/upvote/reputation. | README + requirement notes; no code/schema. | No data/API/permission/moderation model. | HIGH | Start after identity/Gateway with a narrow post/feed slice. |
| Chat | Laravel + MongoDB, conversation/message/read state/realtime. | README/placeholder only. | No persistence/realtime/retention model. | HIGH | Approve message pagination/realtime transport before scaffolding. |
| EDU/Learning | LMS, document, Q&A, FAQ, progress. | README/requirements/placeholders only. | Feature, data and access contracts incomplete. | HIGH | Start Q&A/document slices after Identity and ownership decisions. |
| Notification | In-app, push devices, delivery history. | README only. | No event/payload/provider/retry model. | HIGH | Define notification taxonomy and durable model before integration. |
| Realtime | Pusher or Gateway WS may be used. | No source/config. | Transport decision and security/reliability model absent. | HIGH | Decide one transport per phase; persistent Chat remains source of truth. |
| Database | Dedicated MySQL service schemas, MongoDB Chat, Neo4j KG. | No DB/container/migration/model. | No physical data design or migration process. | CRITICAL | Approve service-local data models only for first vertical slice. |
| Redis | Cache/rate limit/presence/locks. | No config/use. | Cannot validate caching/edge strategy. | MEDIUM | Add after a measured use case; do not make source of truth. |
| RabbitMQ | Async service events and AI task processing. | No schema/config/worker. | No event contracts/idempotency/outbox. | HIGH | Define first event only with producer/consumer/payload/retry contract. |
| Search | Hybrid Search inside AI Knowledge Service. | No search/index/query. | Provider/vector/ranking/access-filter strategy unresolved. | HIGH | Do not integrate Algolia/pgvector without reconciling priority decision. |
| Neo4j/KG | KG stores curated node/edges. | No product integration; separate mock demo only. | Ontology, curation, provenance/deletion and query model absent. | HIGH | Design curation-first KG pilot after document source/access contracts. |
| Python AI | AI pipeline/search/recommendation. | No Python service; mock unrelated. | All runtime/data/privacy/evaluation controls missing. | HIGH | Implement bounded, observable ingestion experiment only after data/security decisions. |
| Deployment | Docker Compose first, then deploy flow. | Infrastructure README only. | No local runtime/CI/staging/prod plan. | CRITICAL | Establish reproducible local foundation before feature services. |
| Testing | Future tests after base stage. | No tests; base decision says do not create yet. | No test framework/quality gates when implementation begins. | HIGH | End base-stage restriction explicitly then add tests with first scaffold. |
| Monitoring | Correlation ID and operation visibility. | No telemetry. | Failures cannot be seen/diagnosed. | HIGH | Build health/log/correlation standard into first Gateway/service. |

## Document Conflicts Requiring Resolution

| Area | Priority decision | Conflicting/older document | Impact | Required action |
| --- | --- | --- | --- | --- |
| System shape | Microservices required. | `ananylics/system_architecture.md` allows modular monolith. | Architecture direction. | Follow priority decision unless owner changes it. |
| Web | Vue 3 + TypeScript + Tailwind. | Older docs propose React/Next as alternatives. | Scaffold/library choice. | Use Vue target. |
| Mobile | Android Native Kotlin/Java. | Older docs propose Flutter/React Native. | Mobile strategy. | Use Android target unless re-decided. |
| Gateway | NodeJS + NestJS. | Older docs mention Nginx/Kong/Golang options. | Gateway responsibility/tooling. | Use NestJS target; Nginx may later be deployment concern only if approved. |
| Relational DB | MySQL for each relevant service/AI metadata. | Older docs name PostgreSQL/pgvector. | Migration and vector design. | Follow MySQL; resolve vector search approach without pgvector. |
| Search | Hybrid Search owned by AI Knowledge Service currently. | Older docs name Algolia/independent search. | Ownership/dependency. | Do not add Algolia unless ADR changes decision. |
| Realtime | Pusher or Gateway WebSocket undecided. | Older docs imply Pusher. | Vendor/transport. | Resolve OQ-020. |
| AI framework/provider | Python only fixed. | FastAPI/Flask/LangChain/Ollama/OpenAI/Qwen named as options. | API/runtime/privacy. | Select by ADR after requirements. |

## Highest-priority Sequence

1. Confirm product/security decisions in [OPEN_QUESTIONS.md](../OPEN_QUESTIONS.md).
2. Establish source control, local infrastructure and Gateway/Identity vertical slice.
3. Define first OpenAPI/event/data contracts and end the base-stage no-tests restriction for that slice.
4. Add one Social or Learning workflow end-to-end, not all modules at once.
5. Add Chat/Notification after reliable auth/event/realtime decisions.
6. Add AI ingestion/curation only after document authorization and data-security rules work.
