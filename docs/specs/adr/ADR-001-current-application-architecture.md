# ADR-001 Current Application Architecture

## Context

The product directory contains a priority architecture decision named `Cấu trúc cuối cùng.md`. Older documents describe alternatives such as modular monolith, React/Flutter, PostgreSQL/pgvector, Algolia and different gateway stacks.

## Current State

There is no runnable source implementation. The actual repository is a skeleton with documented service directories and README files.

## Decision / Proposed Decision

**Status: Existing.** Preserve the documented microservice monorepo target:

- Vue 3/TypeScript/Tailwind Web and React Native + Expo clients call NodeJS/NestJS Gateway only.
- Laravel owns Identity, Social, Learning, Chat and Notification service implementations; Python owns AI Knowledge.
- MySQL is separate per relevant service; Chat owns MongoDB; AI owns MySQL metadata and Neo4j KG.
- REST is synchronous service communication; RabbitMQ is asynchronous communication.
- Services do not cross-query or cross-FK databases.

## Alternatives

- Modular monolith.
- Different web/mobile/gateway/database/search stacks from older proposals.

## Trade-offs

Microservices impose higher operational/contract cost than a monolith, but the project’s existing priority decision requires them. The implementation roadmap mitigates risk with thin vertical slices and no premature Kubernetes/service mesh/CQRS.

## Consequences

Every new feature must choose one owning service/database, Gateway API boundary and explicit inter-service contract. Older conflicting documents are historical inputs, not authority.
