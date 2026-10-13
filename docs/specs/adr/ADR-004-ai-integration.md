# ADR-004 AI Integration

## Context

The target has a Python AI Knowledge Service for document/content processing, curation, search and recommendation. Existing documents name FastAPI/Flask, various NLP libraries, multiple LLMs and managed/local providers as alternatives.

## Current State

No AI service runtime, provider, prompts, event payload, evaluation or security control exists. The only Python graph demo is separate mock code.

## Decision / Proposed Decision

**Status: Needs Decision.** Approve an access-controlled, curation-first document-ingestion pilot only after OQ-030, OQ-032 and OQ-034 through OQ-039 are decided.

## Alternatives

- Approved managed LLM/embedding provider.
- Approved self-hosted/private model.
- Rule/NLP-only extraction pilot.
- Defer AI until the document platform is secure and operational.

## Trade-offs

Managed models speed delivery but require data-residency/retention approval. Self-hosting protects control but adds hardware/MLOps complexity. NLP-only is simpler but may give lower extraction quality.

## Consequences

No model provider, prompt, vector system or automatic graph write may be implied from older proposals. Every call must be observable, access-controlled, evaluated and reversible.
