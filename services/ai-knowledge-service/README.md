# AI Knowledge Service

This FastAPI service includes a deterministic graph demo, permission-aware keyword search, provider-backed suggestions, and a human-reviewed concept curation workflow. It is not yet a semantic index or production hybrid search implementation.

## Concept curation

Run `python3.13 -m alembic upgrade head` against the configured AI MySQL database before using the curation API. Through Gateway, propose a concept with `POST /api/v1/ai/curation/concepts` using `source_type` (`question` or `document`), `source_id`, `name`, and `description`. `GET /api/v1/ai/curation/concepts?source_type=...&source_id=...` returns approved concepts for a source the caller can still read. Reviewers with `ai.curation.review` can list `/api/v1/ai/curation/pending`, then POST `{"decision":"approve"}` or `{"decision":"reject"}` to `/api/v1/ai/curation/concepts/{id}/review`. POST `/api/v1/ai/curation/concepts/{id}/sync` writes an approved concept to Neo4j; it is safe to retry after a Neo4j failure.

Learning is queried before creating, reviewing, listing, or syncing a concept. The stored source hash prevents publishing a proposal against changed source content. Sync is explicit so a failed Neo4j write does not silently look complete. Existing approved graph nodes must be reconciled when their Learning source is withdrawn; until that workflow is built, do not expose Neo4j directly to clients. All public reads stay behind Gateway and Learning authorization.

## What is included

- `GET /api/v1/health` and `GET /api/v1/graph/demo`.
- SQLAlchemy model and Alembic migration for `demo_graph_seed_runs`, which records only demo seed metadata in `ai_knowledge_db`.
- Idempotent Neo4j seed at `scripts/seed_neo4j.cypher`, linked to the seeded Identity UUIDs.
- A Python 3.12 Dockerfile and pinned runtime requirements.

## Local setup

Copy `.env.example` to `.env`, then run Alembic after MySQL is available:

`alembic upgrade head`

Apply the Cypher file in Neo4j Browser or `cypher-shell`. Run the API with:

`uvicorn app.main:app --reload --port 8090`
