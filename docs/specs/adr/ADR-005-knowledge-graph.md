# ADR-005 Knowledge Graph

## Context

Neo4j is the documented graph store. The intended business value is curated knowledge linking people/expertise/documents/Q&A for expert finding, recommendation and hybrid search.

## Current State

No product Neo4j driver, schema, Cypher, ingestion, curation UI or deletion synchronization exists. Standalone NetworkX/PyVis demo uses hard-coded mock data and does not define production ontology.

## Decision / Proposed Decision

**Status: Needs Decision.** Approve a minimal versioned ontology, source/provenance rules and curation/deletion contract before Neo4j integration. Resolve OQ-036, OQ-037, OQ-039 and OQ-040 first.

## Alternatives

- Minimal curated graph: Person/Expertise/KnowledgeItem and explicit versioned relations.
- Broader enterprise ontology at first release.
- Delay graph projection and deliver conventional document/Q&A search first.

## Trade-offs

A minimal ontology reduces early complexity and makes curation/audit testable. A broad ontology can model more value but increases data quality, migration and governance burden.

## Consequences

Neo4j is not a replacement for service-owned source records. Search/recommendation must retain source links, authorization filtering and deletion/revocation propagation.
