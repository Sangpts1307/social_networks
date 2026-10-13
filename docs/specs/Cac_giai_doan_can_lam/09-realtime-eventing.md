# Realtime and Eventing Specification

## Current State

No RabbitMQ configuration, event schema, producer, consumer, worker, WebSocket server, Laravel broadcasting configuration, Pusher client, Firebase/FCM client, realtime frontend handler, scheduler or queue monitor exists in `social_networks/`.

| Capability | Current status |
| --- | --- |
| Event catalog | `NOT_FOUND` |
| RabbitMQ exchange/queue/DLQ | `NOT_FOUND` |
| Outbox/idempotency implementation | `NOT_FOUND` |
| WebSocket/Pusher transport | `NOT_FOUND` |
| Push delivery/FCM | `NOT_FOUND` |
| Presence/read-receipt protocol | `NOT_FOUND` |

## Documented Target Rules

| Rule | Status | Evidence |
| --- | --- | --- |
| Synchronous service communication uses REST/JSON. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Asynchronous communication uses RabbitMQ. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Events use past-tense names such as `user.created`, `post.published`. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Important events carry `event_id`, `event_type`, `occurred_at`, `producer`, `version`, `payload`. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Consumers must be idempotent. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Outbox Pattern is preferred when real data is implemented. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 6 |
| Chat realtime transport is not source of truth; Chat owns messages. | `DOCUMENTED_NOT_IMPLEMENTED` | Architecture decision section 8; Chat README |
| Pusher or a Gateway WebSocket may be used later. | `UNKNOWN` choice | Architecture decision section 8 |

## Event Catalog

There are no verified events. The architecture document supplies example names only; they are intentionally not declared as contracts here.

| Event | Producer | Consumer | Transport | Payload | Purpose | Status |
| --- | --- | --- | --- | --- | --- |
| `user.profile.updated` | Example: Identity | Example: Social/Chat/Learning | RabbitMQ (documented target) | `UNKNOWN` | Example local read-model sync | `DOCUMENTED_NOT_IMPLEMENTED` example only |
| `user.created` | `UNKNOWN` | `UNKNOWN` | RabbitMQ target | `UNKNOWN` | Naming example | `DOCUMENTED_NOT_IMPLEMENTED` example only |
| `post.published` | `UNKNOWN` | `UNKNOWN` | RabbitMQ target | `UNKNOWN` | Naming example | `DOCUMENTED_NOT_IMPLEMENTED` example only |
| `comment.created` | `UNKNOWN` | `UNKNOWN` | RabbitMQ target | `UNKNOWN` | Naming example | `DOCUMENTED_NOT_IMPLEMENTED` example only |
| `lesson.completed` | `UNKNOWN` | `UNKNOWN` | RabbitMQ target | `UNKNOWN` | Naming example | `DOCUMENTED_NOT_IMPLEMENTED` example only |
| `document.uploaded` | `UNKNOWN` | `UNKNOWN` | RabbitMQ target | `UNKNOWN` | Naming example | `DOCUMENTED_NOT_IMPLEMENTED` example only |

## Realtime Functional Intent

| User-visible need | Intended source | Unresolved design items |
| --- | --- | --- |
| Direct/group chat realtime | Project proposal/workbook | Transport, channel naming, auth, persistence-before-publish, delivery/read states, reconnection, offline catch-up, group semantics. |
| Comment realtime | Older tech documents | Whether required, UX and transport choice. |
| Notification badge/dropdown | Workbook/welcome mock | Producer taxonomy, durable notification model, realtime vs poll, read state and push policy. |
| Mobile push | Mobile README/workbook/older docs | FCM integration, device registration, payload, consent and retry. |
| AI async processing | AI proposal/architecture decision | Source event, canonical payload, consumer, idempotency, retry/DLQ and curation update delivery. |

## Future Event Contract Gate

Every accepted event must have:

1. Event ID/name/version and owning producer.
2. Triggering FR/API/data transaction and outbox behavior.
3. JSON schema under `contracts/events/`.
4. Payload data classification and no secret/unauthorized data.
5. Consumer registry, idempotency key and retry/DLQ policy.
6. Ordering/duplication/expiry/retention expectations.
7. Observability: correlation ID, metrics and failure alert.
8. Contract and integration tests.

## Evidence

- `contracts/events/README.md`
- `Cấu trúc cuối cùng.md`, sections 5, 6 and 8
- `services/chat-service/README.md`; `services/notification-service/README.md`; `services/ai-knowledge-service/README.md`
- `ananylics/Social Networks.xlsx`, rows 31–33 and 53–60
- Repository inventory: no event/realtime source or config found
