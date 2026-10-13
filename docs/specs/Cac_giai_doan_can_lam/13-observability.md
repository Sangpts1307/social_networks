# Observability Specification

## Current State

No application logging configuration, structured log schema, exception tracker, metric collector, trace propagation, health endpoint, queue monitor, dashboard or alert definition exists in the product directory.

| Capability | Current status |
| --- | --- |
| Application/API logs | `NOT_FOUND` |
| Correlation ID implementation | `NOT_FOUND` |
| Error tracking | `NOT_FOUND` |
| Metrics/dashboards | `NOT_FOUND` |
| Distributed tracing | `NOT_FOUND` |
| Queue failure/DLQ monitoring | `NOT_FOUND` |
| Database slow-query monitoring | `NOT_FOUND` |
| AI model/cost/quality observability | `NOT_FOUND` |
| Health checks | `NOT_FOUND` |

## Documented Constraints

- Gateway is intended to own/generate/forward a correlation ID.
- Every service request is documented to carry `correlation_id`.
- RabbitMQ consumer processing must be idempotent.

These are `DOCUMENTED_NOT_IMPLEMENTED` constraints, not live telemetry.

## Proposed Minimum Telemetry Contract

| Signal | Required dimensions/fields | Applies to |
| --- | --- | --- |
| Structured log | timestamp, level, service, environment, release, correlation_id, operation, outcome; never secret/token/raw private document | Gateway and every service/worker |
| HTTP metric | route template, method, status class, latency, error type | Gateway/services |
| Dependency metric | target system, operation, latency, timeout/error | DB, Redis, RabbitMQ, Neo4j, storage, provider |
| Queue metric | queue, event type/version, consumer, lag, retry, DLQ count | RabbitMQ producers/consumers |
| Business audit | actor/action/resource/result/correlation ID | auth/admin/moderation/curation/points |
| AI metric | task/model/prompt version/input size/latency/cost/failure/evaluation version; no raw sensitive payload | AI Knowledge |
| Health check | service readiness and safe dependency status | Gateway/services/workers |

## Alerting Priorities

| Alert condition | Severity target | Current status |
| --- | --- | --- |
| Gateway/service unavailable or sustained 5xx | CRITICAL | `PROPOSED` |
| Authentication anomaly/rate-limit abuse | HIGH | `PROPOSED` |
| Queue lag/DLQ growth or consumer failure | HIGH | `PROPOSED` |
| Failed migrations/backups/restores | CRITICAL | `PROPOSED` |
| Object storage/document processing failures | HIGH | `PROPOSED` |
| Neo4j/AI retrieval failure or unauthorized result signal | HIGH | `PROPOSED` |
| AI cost/timeout/evaluation regression | MEDIUM/HIGH | `PROPOSED` |

## Evidence

- `Cấu trúc cuối cùng.md`, section 6 (correlation ID/idempotency)
- `gateway/api-gateway/README.md`
- Repository inventory: no telemetry code/configuration found
