# ADR-003 Realtime Strategy

## Context

Chat and notifications need timely updates. The priority architecture permits Pusher initially or Gateway WebSocket later, while older documents lean toward Pusher. Persistent Chat data remains MongoDB source of truth.

## Current State

No realtime code/config/client/provider account/event contract is present.

## Decision / Proposed Decision

**Status: Needs Decision.** Select one initial transport after OQ-018 through OQ-021 are resolved; keep durable persistence/notification independent from delivery transport.

## Alternatives

- Managed Pusher Channels.
- Gateway-owned WebSocket boundary.
- Polling/no realtime in first MVP.

## Trade-offs

Managed service reduces operational work but adds vendor/data/security constraints. Gateway WebSocket gives more control but requires scaling, authorization and monitoring. Polling reduces scope but does not meet realtime expectations.

## Consequences

Define authorized channels, persist-before-publish behavior, reconnect/duplicate/read semantics, push fallback and observability before code.
